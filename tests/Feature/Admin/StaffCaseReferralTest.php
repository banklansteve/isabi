<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\PatrolCase;
use App\Models\Review;
use App\Models\StaffCaseReferral;
use App\Models\StaffRole;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffCaseReferralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function withPermissions(array $permissions, string $slug = 'referral_role'): User
    {
        $role = StaffRole::query()->create([
            'slug' => $slug.'_'.uniqid(),
            'name' => 'Referral test role',
            'description' => 'Test role.',
            'icon' => 'ti ti-transfer',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 940,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    private function artisanUser(): User
    {
        return User::factory()->regularUser()->create([
            'business_name' => 'Demo Artisan',
            'slug' => 'demo-artisan-'.uniqid(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function logJob(User $artisan, array $overrides = []): WorkLog
    {
        return WorkLog::withoutEvents(function () use ($artisan, $overrides) {
            $log = new WorkLog;
            $log->forceFill(array_merge([
                'user_id' => $artisan->id,
                'uid' => (string) Str::uuid(),
                'description' => 'Finished a kitchen remodel cleanly.',
                'worked_on' => now()->toDateString(),
                'client_name' => 'Ada',
                'service_state' => 'Lagos',
            ], $overrides))->save();

            return $log->fresh() ?? $log;
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeReview(WorkLog $log, array $overrides = []): Review
    {
        return Review::withoutEvents(function () use ($log, $overrides) {
            $review = new Review;
            $review->forceFill(array_merge([
                'uid' => (string) Str::uuid(),
                'work_log_id' => $log->id,
                'user_id' => $log->user_id,
                'rating' => 5,
                'would_recommend' => true,
                'comment' => 'Looks professional and finished on time.',
                'client_display_name' => 'Ada Okonkwo',
                'submitted_at' => now()->subDay(),
                'flagged_at' => now(),
                'flag_reason' => 'Suspected fake review',
            ], $overrides))->save();

            return $review->fresh() ?? $review;
        });
    }

    public function test_ops_can_refer_a_flagged_review_for_moderation(): void
    {
        $referrer = $this->withPermissions(['admin.content.manage'], 'content_a');
        $assignee = $this->withPermissions(['admin.content.manage'], 'content_b');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Looks fake — please moderate before it stays public.',
                'queue' => 'moderation',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('block.assignee_id', $assignee->id);

        $fresh = $review->fresh();
        $this->assertSame($assignee->id, $fresh->assigned_to_user_id);
        $this->assertSame($referrer->id, $fresh->referred_by_user_id);
        $this->assertNotNull($fresh->referred_at);

        $referral = StaffCaseReferral::query()->active()->first();
        $this->assertNotNull($referral);
        $this->assertSame(StaffCaseReferral::SUBJECT_REVIEW, $referral->subject_type);
        $this->assertSame(StaffCaseReferral::QUEUE_MODERATION, $referral->queue);
        $this->assertSame($assignee->id, $referral->assignee_user_id);

        $this->actingAs($assignee)
            ->get(route('admin.assigned.index', ['queue' => 'moderation']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Assigned')
                ->where('queue', 'moderation')
                ->has('items', 1)
                ->where('items.0.subject_type', 'review')
                ->where('counts.moderation', 1));

        $this->actingAs($assignee)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->where('priority_groups.0.key', 'moderation')
                ->where('priority_groups.0.items.0.title', fn ($title) => str_contains((string) $title, 'review')));
    }

    public function test_referring_a_review_notifies_the_assignee(): void
    {
        $referrer = $this->withPermissions(['admin.content.manage'], 'content_a');
        $assignee = $this->withPermissions(['admin.content.manage'], 'content_b');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Please check this review carefully.',
                'queue' => 'moderation',
            ])
            ->assertOk();

        $delivery = AnnouncementDelivery::query()
            ->where('user_id', $assignee->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($delivery);
        $announcement = Announcement::query()->find($delivery->announcement_id);
        $this->assertNotNull($announcement);
        $this->assertStringContainsString('Moderation', (string) $announcement->subject);
    }

    public function test_job_refer_creates_unified_referral_row(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = User::factory()->operationsAdmin()->create(['name' => 'Ops Lead']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->postJson(route('admin.jobs.refer', $job->uid), [
                'assignee_id' => $ops->id,
                'note' => 'Please look at the photos and client contact.',
            ])
            ->assertOk()
            ->assertJsonPath('record.referred', true);

        $this->assertSame($ops->id, $job->fresh()->referred_to_user_id);

        $referral = StaffCaseReferral::query()
            ->where('subject_type', StaffCaseReferral::SUBJECT_JOB)
            ->where('subject_id', $job->id)
            ->active()
            ->first();

        $this->assertNotNull($referral);
        $this->assertSame($ops->id, $referral->assignee_user_id);
        $this->assertSame($super->id, $referral->referred_by_user_id);
    }

    public function test_support_peer_refer_assigns_ticket(): void
    {
        $referrer = $this->withPermissions(['admin.support.manage'], 'support_a');
        $assignee = $this->withPermissions(['admin.support.manage'], 'support_b');
        $artisan = $this->artisanUser();

        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Need help with my review link',
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $referrer->id,
        ]);

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'support',
                'subject_uid' => $ticket->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Please take over — I am going off shift.',
                'queue' => 'support',
            ])
            ->assertOk();

        $this->assertSame($assignee->id, $ticket->fresh()->assigned_to_user_id);

        $referral = StaffCaseReferral::query()->active()->first();
        $this->assertSame(StaffCaseReferral::SUBJECT_SUPPORT, $referral->subject_type);
        $this->assertSame($assignee->id, $referral->assignee_user_id);
    }

    public function test_patrol_peer_refer_sets_assigned_to(): void
    {
        $referrer = $this->withPermissions(['patrol.view', 'patrol.investigate'], 'patrol_a');
        $assignee = $this->withPermissions(['patrol.view', 'patrol.investigate'], 'patrol_b');
        $artisan = $this->artisanUser();
        $job = $this->logJob($artisan);

        $case = PatrolCase::query()->create([
            'kind' => PatrolCase::KIND_JOB,
            'work_log_id' => $job->id,
            'user_id' => $artisan->id,
            'status' => PatrolCase::STATUS_NEW,
            'severity' => PatrolCase::SEVERITY_HIGH,
            'flagged_at' => now(),
        ]);

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'patrol',
                'subject_uid' => (string) $case->id,
                'assignee_id' => $assignee->id,
                'note' => 'High severity — need a second pair of eyes.',
                'queue' => 'patrol',
            ])
            ->assertOk();

        $this->assertSame($assignee->id, $case->fresh()->assigned_to);
    }

    public function test_refer_rejects_an_artisan_assignee(): void
    {
        $ops = $this->withPermissions(['admin.content.manage']);
        $artisan = $this->artisanUser();
        $review = $this->makeReview($this->logJob($artisan));

        $this->actingAs($ops)
            ->from(route('admin.reviews.index'))
            ->post(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $artisan->id,
                'note' => 'Please look at this review.',
                'queue' => 'moderation',
            ])
            ->assertSessionHasErrors('assignee_id');
    }

    public function test_ops_can_escalate_support_ticket_to_super_admin(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->withPermissions(['admin.support.manage'], 'support_escalate');
        $artisan = $this->artisanUser();

        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Billing dispute on annual plan',
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $ops->id,
        ]);

        $this->actingAs($ops)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'support',
                'subject_uid' => $ticket->uid,
                'note' => 'Customer wants a refund outside policy — need a decision.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('block.status', 'Pending');

        $referral = StaffCaseReferral::query()->active()->superEscalations()->first();
        $this->assertNotNull($referral);
        $this->assertSame(StaffCaseReferral::SUBJECT_SUPPORT, $referral->subject_type);
        $this->assertSame($ticket->id, $referral->subject_id);
        $this->assertSame($ops->id, $referral->referred_by_user_id);
        $this->assertSame($super->id, $referral->assignee_user_id);

        $delivery = AnnouncementDelivery::query()
            ->where('user_id', $super->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($delivery);
        $announcement = Announcement::query()->find($delivery->announcement_id);
        $this->assertNotNull($announcement);
        $this->assertStringContainsString('Escalation', (string) $announcement->subject);
        $this->assertSame('staff_escalation', $announcement->segment['kind'] ?? null);
        $this->assertStringContainsString('escalation='.$referral->id, (string) ($announcement->segment['href'] ?? ''));

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Overview')
                ->where('admin_inbox.open_count', fn ($count) => (int) $count >= 1)
                ->where('admin_inbox.priority_groups.0.key', 'escalations')
                ->where('admin_inbox.priority_groups.0.items.0.unread', true));

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->where('escalations.0.status', 'Pending'));

        $this->actingAs($super)
            ->postJson(route('admin.escalations.acknowledge', $referral))
            ->assertOk();

        $this->assertNotNull($referral->fresh()->acknowledged_at);

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('escalations.0.status', 'In review'));
    }

    public function test_ops_can_escalate_user_profile_to_super_admin(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->withPermissions(['admin.users.view'], 'users_escalate');
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'user',
                'subject_uid' => (string) $artisan->id,
                'note' => 'Possible duplicate account — need Super Admin to decide on merge.',
            ])
            ->assertOk()
            ->assertJsonPath('block.status', 'Pending');

        $referral = StaffCaseReferral::query()->active()->superEscalations()->first();
        $this->assertNotNull($referral);
        $this->assertSame(StaffCaseReferral::SUBJECT_USER, $referral->subject_type);
        $this->assertSame($artisan->id, $referral->subject_id);

        $this->actingAs($super)
            ->getJson(route('admin.users.show', $artisan))
            ->assertOk()
            ->assertJsonPath('escalation.status', 'Pending')
            ->assertJsonPath('can_escalate', false);
    }

    public function test_super_admin_can_complete_escalation(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->withPermissions(['admin.support.manage'], 'support_complete');
        $artisan = $this->artisanUser();

        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Account access issue',
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $ops->id,
        ]);

        $this->actingAs($ops)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'support',
                'subject_uid' => $ticket->uid,
                'note' => 'Need policy exception for this artisan.',
            ])
            ->assertOk();

        $referral = StaffCaseReferral::query()->active()->superEscalations()->first();
        $this->assertNotNull($referral);

        $this->actingAs($super)
            ->postJson(route('admin.escalations.complete', $referral))
            ->assertOk()
            ->assertJsonPath('escalation.requester_status', 'Resolved');

        $this->assertSame(StaffCaseReferral::STATUS_COMPLETED, $referral->fresh()->status);

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('escalations.0.status', 'Resolved'));
    }

    public function test_ops_cannot_refer_directly_to_super_admin(): void
    {
        $super = User::factory()->superAdmin()->create();
        $referrer = $this->withPermissions(['admin.content.manage'], 'content_block_sa');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $super->id,
                'note' => 'Please take this as Super Admin.',
                'queue' => 'moderation',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignee_id');
    }

    public function test_super_admin_can_view_ops_workload_take_over_and_resolve(): void
    {
        $super = User::factory()->superAdmin()->create();
        $referrer = $this->withPermissions(['admin.content.manage'], 'content_referrer');
        $assignee = $this->withPermissions(['admin.content.manage'], 'content_assignee');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Looks fake — need a second look.',
                'queue' => 'moderation',
            ])
            ->assertOk();

        $referral = StaffCaseReferral::query()->active()->first();
        $this->assertNotNull($referral);
        $this->assertSame($assignee->id, $referral->assignee_user_id);

        $this->actingAs($super)
            ->get(route('admin.assigned.index', ['staff' => $assignee->id, 'queue' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Assigned')
                ->where('can_manage', true)
                ->where('scope', 'staff')
                ->where('viewing.id', $assignee->id)
                ->where('items.0.id', $referral->id));

        $this->actingAs($super)
            ->get(route('admin.assigned.index', ['scope' => 'all', 'queue' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Assigned')
                ->where('scope', 'all')
                ->has('items', 1)
                ->has('growth_duties', 5));

        $this->actingAs($super)
            ->postJson(route('admin.referrals.take-over', $referral), [
                'note' => 'Taking this over from ops.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $owned = StaffCaseReferral::query()->active()->where('assignee_user_id', $super->id)->first();
        $this->assertNotNull($owned);
        $this->assertSame($review->id, $owned->subject_id);
        $this->assertSame(StaffCaseReferral::SOURCE_TAKEN_OVER, $owned->source);
        $this->assertNotNull($owned->acknowledged_at);

        $this->actingAs($super)
            ->get(route('admin.assigned.index', ['scope' => 'mine', 'queue' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('scope', 'mine')
                ->where('items.0.origin', 'taken_over')
                ->where('mine_breakdown.taken_over', 1));

        $this->actingAs($super)
            ->postJson(route('admin.referrals.complete', $owned))
            ->assertOk();

        $this->assertSame(StaffCaseReferral::STATUS_COMPLETED, $owned->fresh()->status);
    }

    public function test_super_admin_can_reassign_ops_case_to_another_agent(): void
    {
        $super = User::factory()->superAdmin()->create();
        $agentA = $this->withPermissions(['admin.content.manage'], 'agent_a');
        $agentB = $this->withPermissions(['admin.content.manage'], 'agent_b');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($agentA)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $agentB->id,
                'note' => 'Please moderate this review.',
                'queue' => 'moderation',
            ])
            ->assertOk();

        $referral = StaffCaseReferral::query()->active()->first();
        $this->assertNotNull($referral);

        $this->actingAs($super)
            ->postJson(route('admin.referrals.reassign', $referral), [
                'assignee_id' => $agentA->id,
                'note' => 'Moving this back to the original reviewer.',
            ])
            ->assertOk();

        $fresh = StaffCaseReferral::query()->active()->first();
        $this->assertNotNull($fresh);
        $this->assertSame($agentA->id, $fresh->assignee_user_id);
        $this->assertSame($agentA->id, $review->fresh()->assigned_to_user_id);
    }

    public function test_referrer_is_notified_when_assignee_resolves_and_clears_on_open(): void
    {
        $referrer = $this->withPermissions(['admin.content.manage'], 'content_notify_a');
        $assignee = $this->withPermissions(['admin.content.manage'], 'content_notify_b');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Please check this review.',
                'queue' => 'moderation',
            ])
            ->assertOk();

        $referral = StaffCaseReferral::query()->active()->first();
        $this->assertNotNull($referral);

        $this->actingAs($assignee)
            ->postJson(route('admin.referrals.complete', $referral))
            ->assertOk();

        $this->actingAs($referrer)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', fn ($count) => $count >= 1)
                ->where('items', fn ($items) => collect($items)->contains(
                    fn ($item) => ($item['group'] ?? null) === 'referral_updates'
                        && ($item['unread'] ?? false) === true
                        && str_contains((string) ($item['title'] ?? ''), 'Resolved')
                )));

        $this->actingAs($referrer)
            ->get(route('admin.reviews.index', [
                'review' => $review->uid,
                'referral_update' => $referral->id,
            ]))
            ->assertOk();

        $this->actingAs($referrer)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('items', fn ($items) => collect($items)
                    ->where('group', 'referral_updates')
                    ->every(fn ($item) => ($item['unread'] ?? true) === false)));
    }

    public function test_referrer_is_notified_when_super_admin_acknowledges_escalation(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->withPermissions(['admin.support.manage'], 'support_ack_notify');
        $artisan = $this->artisanUser();

        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Need a policy call',
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $ops->id,
        ]);

        $this->actingAs($ops)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'support',
                'subject_uid' => $ticket->uid,
                'note' => 'Need Super Admin guidance.',
            ])
            ->assertOk();

        $referral = StaffCaseReferral::query()->active()->superEscalations()->first();
        $this->assertNotNull($referral);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('notifications.unread_count', fn ($count) => $count >= 1)
                ->where('notifications.items', fn ($items) => collect($items)->contains(
                    fn ($item) => str_contains((string) ($item['title'] ?? ''), 'Escalat')
                        || ($item['attention_key'] ?? null) === 'escalation:'.$referral->id
                )));

        $this->actingAs($super)
            ->postJson(route('admin.escalations.acknowledge', $referral))
            ->assertOk();

        $this->assertNotNull($referral->fresh()->acknowledged_at);

        $delivery = AnnouncementDelivery::query()
            ->where('user_id', $ops->id)
            ->whereHas('announcement', fn ($q) => $q->where('segment->kind', 'staff_referral_update'))
            ->latest('id')
            ->first();

        $this->assertNotNull($delivery);

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('items', fn ($items) => collect($items)->contains(
                    fn ($item) => ($item['group'] ?? null) === 'referral_updates'
                        && ($item['unread'] ?? false) === true
                )));
    }

    public function test_ops_home_no_longer_exposes_escalate_deep_link_banner(): void
    {
        $ops = $this->withPermissions(['patrol.view', 'admin.content.manage'], 'home_no_banner');

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->missing('escalate'));
    }

    public function test_referred_by_me_desk_lists_outbound_referrals(): void
    {
        $referrer = $this->withPermissions(['admin.content.manage'], 'referrer_desk');
        $assignee = $this->withPermissions(['admin.content.manage'], 'assignee_desk');
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Please check authenticity.',
                'queue' => 'moderation',
            ])
            ->assertOk();

        $this->actingAs($referrer)
            ->get(route('admin.assigned.index', ['desk' => 'referred', 'queue' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Assigned')
                ->where('desk', 'referred')
                ->where('counts.referred', fn ($count) => (int) $count >= 1)
                ->where('items', fn ($items) => collect($items)->contains(
                    fn ($item) => ($item['subject_type'] ?? null) === 'review'
                        && (int) ($item['assignee']['id'] ?? 0) === (int) $assignee->id
                )));
    }

    public function test_ops_can_escalate_job_review_and_patrol_from_detail(): void
    {
        $super = User::factory()->superAdmin()->create();
        $content = $this->withPermissions(['admin.content.manage'], 'content_escalate');
        $patrol = $this->withPermissions(['patrol.view', 'patrol.investigate'], 'patrol_escalate');
        $artisan = $this->artisanUser();
        $job = $this->logJob($artisan);
        $review = $this->makeReview($job);
        $case = PatrolCase::query()->create([
            'kind' => PatrolCase::KIND_JOB,
            'work_log_id' => $job->id,
            'user_id' => $artisan->id,
            'status' => PatrolCase::STATUS_NEW,
            'severity' => PatrolCase::SEVERITY_MEDIUM,
            'flagged_at' => now(),
        ]);

        $this->actingAs($content)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'job',
                'subject_uid' => $job->uid,
                'note' => 'Photos look inconsistent with the claimed work.',
            ])
            ->assertOk();

        $this->actingAs($content)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'note' => 'Suspected fabricated client review.',
            ])
            ->assertOk();

        $this->actingAs($patrol)
            ->postJson(route('admin.escalations.store'), [
                'subject_type' => 'patrol',
                'subject_uid' => (string) $case->id,
                'note' => 'Need Super Admin call on severity.',
            ])
            ->assertOk();

        $this->assertSame(3, StaffCaseReferral::query()->active()->superEscalations()->count());
        $this->assertTrue(
            StaffCaseReferral::query()
                ->active()
                ->superEscalations()
                ->where('subject_type', StaffCaseReferral::SUBJECT_JOB)
                ->where('subject_id', $job->id)
                ->where('referred_by_user_id', $content->id)
                ->where('assignee_user_id', $super->id)
                ->exists()
        );
    }
}
