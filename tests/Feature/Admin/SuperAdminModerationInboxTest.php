<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\PatrolCase;
use App\Models\PatrolCaseRule;
use App\Models\Review;
use App\Models\StaffCaseReferral;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminModerationInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function opsWith(array $permissions): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'ops_'.uniqid(),
            'name' => 'Ops',
            'description' => 'Test',
            'icon' => 'ti ti-shield',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 900,
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
            'slug' => 'demo-'.uniqid(),
        ]);
    }

    private function logJob(User $artisan): WorkLog
    {
        return WorkLog::withoutEvents(function () use ($artisan) {
            $log = new WorkLog;
            $log->forceFill([
                'user_id' => $artisan->id,
                'uid' => (string) Str::uuid(),
                'description' => 'Kitchen remodel',
                'worked_on' => now()->toDateString(),
                'client_name' => 'Ada',
            ])->save();

            return $log->fresh() ?? $log;
        });
    }

    public function test_flagged_job_surfaces_in_super_admin_inbox_with_deep_link(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->opsWith(['admin.content.manage']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($ops)
            ->postJson(route('admin.jobs.flag', $job->uid), ['reason' => 'Suspicious timeline'])
            ->assertOk();

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('admin_inbox.open_count', fn ($count) => $count >= 1)
                ->where('admin_inbox.priority_groups', fn ($groups) => collect($groups)->contains(
                    fn ($group) => $group['key'] === 'moderation'
                        && collect($group['items'])->contains(
                            fn ($item) => str_contains((string) $item['href'], 'job='.$job->uid)
                                && str_contains((string) $item['href'], 'tab=flagged')
                        )
                )));
    }

    public function test_flagging_notifies_super_admin_in_app(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->opsWith(['admin.content.manage']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($ops)
            ->postJson(route('admin.jobs.flag', $job->uid), ['reason' => 'Needs a second look'])
            ->assertOk();

        $delivery = AnnouncementDelivery::query()
            ->where('user_id', $super->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($delivery);
        $announcement = Announcement::query()->find($delivery->announcement_id);
        $this->assertSame('staff_flag', $announcement->segment['kind'] ?? null);
        $this->assertStringContainsString('job='.$job->uid, (string) ($announcement->segment['href'] ?? ''));
    }

    public function test_moderation_referral_surfaces_for_super_admin_and_notifies(): void
    {
        $super = User::factory()->superAdmin()->create();
        $referrer = $this->opsWith(['admin.content.manage']);
        $assignee = $this->opsWith(['admin.content.manage']);
        $review = Review::withoutEvents(function () {
            $log = $this->logJob($this->artisanUser());
            $review = new Review;
            $review->forceFill([
                'uid' => (string) Str::uuid(),
                'work_log_id' => $log->id,
                'user_id' => $log->user_id,
                'rating' => 2,
                'comment' => 'Looks fake',
                'submitted_at' => now(),
                'flagged_at' => now(),
            ])->save();

            return $review->fresh() ?? $review;
        });

        $this->actingAs($referrer)
            ->postJson(route('admin.referrals.store'), [
                'subject_type' => 'review',
                'subject_uid' => $review->uid,
                'assignee_id' => $assignee->id,
                'note' => 'Please verify this client exists.',
                'queue' => 'moderation',
            ])
            ->assertOk();

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'cases.referred',
        ]);

        $superDelivery = AnnouncementDelivery::query()
            ->where('user_id', $super->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($superDelivery);
        $announcement = Announcement::query()->find($superDelivery->announcement_id);
        $this->assertSame('staff_referral', $announcement->segment['kind'] ?? null);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('admin_inbox.priority_groups', fn ($groups) => collect($groups)->contains(
                    fn ($group) => $group['key'] === 'moderation'
                        && collect($group['items'])->contains(
                            fn ($item) => str_contains((string) $item['href'], 'review='.$review->uid)
                        )
                )));
    }

    public function test_patrol_auto_flag_surfaces_in_super_admin_inbox(): void
    {
        $super = User::factory()->superAdmin()->create();
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

        PatrolCaseRule::query()->create([
            'patrol_case_id' => $case->id,
            'rule_key' => 'rapid_logging',
            'severity' => PatrolCase::SEVERITY_HIGH,
            'evidence' => ['trigger' => 'Rapid logging'],
            'detected_at' => now(),
        ]);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('admin_inbox.priority_groups', fn ($groups) => collect($groups)->contains(
                    fn ($group) => $group['key'] === 'patrol_jobs'
                        && collect($group['items'])->contains(
                            fn ($item) => str_contains((string) $item['href'], '/admin/patrol/')
                        )
                )));
    }

    public function test_flagged_item_hidden_when_already_in_moderation_referral_queue(): void
    {
        $super = User::factory()->superAdmin()->create();
        $referrer = $this->opsWith(['admin.content.manage']);
        $assignee = $this->opsWith(['admin.content.manage']);
        $job = $this->logJob($this->artisanUser());

        $job->forceFill(['flagged_at' => now(), 'flag_reason' => 'Duplicate'])->save();

        StaffCaseReferral::query()->create([
            'subject_type' => StaffCaseReferral::SUBJECT_JOB,
            'subject_id' => $job->id,
            'assignee_user_id' => $assignee->id,
            'referred_by_user_id' => $referrer->id,
            'note' => 'Check this job',
            'queue' => StaffCaseReferral::QUEUE_MODERATION,
            'status' => StaffCaseReferral::STATUS_ACTIVE,
            'referred_at' => now(),
        ]);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('admin_inbox.priority_groups', fn ($groups) => collect($groups)
                    ->flatMap(fn ($group) => $group['items'] ?? [])
                    ->contains(fn ($item) => ($item['key'] ?? '') === 'flagged:job:'.$job->uid) === false
                    && collect($groups)
                        ->flatMap(fn ($group) => $group['items'] ?? [])
                        ->contains(fn ($item) => str_contains((string) ($item['key'] ?? ''), 'moderation-referral:'))
                ));
    }
}
