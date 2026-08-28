<?php

namespace Tests\Feature\Admin;

use App\Enums\StaffStatus;
use App\Models\AdminAuditLog;
use App\Models\StaffRole;
use App\Models\SupportCannedReply;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Staff\StaffPresence;
use App\Support\SupportChat\SupportChatTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OpsInsightsAndSupportWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_can_open_customer_service_inbox_and_claim_unassigned_chat(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $artisan = User::factory()->regularUser()->create();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_NEW,
            'subject' => 'Need help',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Support/Index')
                ->has('tickets')
                ->where('is_super', true)
                ->has('canned'));

        $this->actingAs($admin)
            ->postJson(route('admin.support.claim', $ticket), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('assigned.id', $admin->id);

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'assigned_to_user_id' => $admin->id,
        ]);
    }

    public function test_super_admin_can_reassign_a_chat_to_ops(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $ops = $this->supportStaff();
        $artisan = User::factory()->regularUser()->create();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $admin->id,
            'subject' => 'Handoff',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.support.assign', $ticket), [
                'assigned_to_user_id' => $ops->id,
            ])
            ->assertOk()
            ->assertJsonPath('assigned.id', $ops->id);
    }

    public function test_ops_staff_do_not_see_another_agents_chats(): void
    {
        $ops = $this->supportStaff();
        $other = $this->supportStaff(['email' => 'other-ops@isabi.dev']);
        $artisan = User::factory()->regularUser()->create();

        $mine = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $ops->id,
            'subject' => 'My artisan',
        ]);
        $theirs = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'assigned_to_user_id' => $other->id,
            'subject' => 'Someone elses artisan',
        ]);
        $open = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_NEW,
            'subject' => 'Unclaimed',
        ]);

        $this->actingAs($ops)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Support/Index')
                ->where('is_super', false)
                ->has('tickets', 1)
                ->where('tickets', fn ($tickets) => collect($tickets)->pluck('id')->contains($mine->id)
                    && collect($tickets)->pluck('id')->doesntContain($theirs->id)
                    && collect($tickets)->pluck('id')->doesntContain($open->id)));

        $this->actingAs($ops)
            ->get(route('admin.support.index', ['assigned' => 'unassigned']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('tickets', 1)
                ->where('tickets.0.id', $open->id));

        $this->actingAs($ops)
            ->get(route('admin.support.show', $theirs))
            ->assertForbidden();

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('items', fn ($items) => collect($items)->pluck('title')->doesntContain('Someone elses artisan')));
    }

    public function test_team_templates_are_available_and_ops_cannot_create_them(): void
    {
        SupportChatTemplates::ensure();
        $ops = $this->supportStaff();
        $admin = User::factory()->superAdmin()->create();

        $this->assertTrue(
            SupportCannedReply::query()->where('scope', 'team')->where('is_system', true)->exists()
        );

        $this->actingAs($ops)
            ->post(route('admin.support.canned.store'), [
                'title' => 'Team only',
                'body' => 'Ops should not save this for everyone.',
                'scope' => 'team',
                'moment' => 'open',
            ])
            ->assertSessionHasErrors('scope');

        $this->actingAs($admin)
            ->post(route('admin.support.canned.store'), [
                'title' => 'Review nudge',
                'body' => 'Please send your client the review link from the job.',
                'scope' => 'team',
                'moment' => 'review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_canned_replies', [
            'title' => 'Review nudge',
            'scope' => 'team',
            'moment' => 'review',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support.templates'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Support/Templates'));

        $this->actingAs($ops)
            ->get(route('admin.support.templates'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_all_ops_on_insights_and_ops_only_sees_self(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $ops = $this->supportStaff(['name' => 'Chidi Ops']);
        $other = User::factory()->operationsAdmin()->create(['name' => 'Amaka Ops']);

        AdminAuditLog::query()->create([
            'actor_id' => $ops->id,
            'action' => 'support.resolved',
            'summary' => 'Resolved a chat',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.insights.index', ['range' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Insights/Index')
                ->where('team', true)
                ->has('staff', 2));

        $this->actingAs($ops)
            ->get(route('admin.insights.index', ['range' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Insights/Index')
                ->where('team', false)
                ->has('staff', 1)
                ->where('staff.0.id', $ops->id));

        $this->actingAs($other)
            ->get(route('admin.insights.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('staff', 1)->where('staff.0.id', $other->id));
    }

    public function test_on_duty_idle_over_seven_minutes_is_marked_away(): void
    {
        $this->travelTo(Carbon::parse('2026-08-26 10:00:00', 'Africa/Lagos'));
        $ops = $this->supportStaff();
        $ops->forceFill(['last_seen_at' => now()->subMinutes(8), 'staff_status' => StaffStatus::Active])->save();

        $snapshot = app(StaffPresence::class)->snapshot($ops);

        $this->assertTrue($snapshot['on_duty']);
        $this->assertSame('away', $snapshot['status']);
        $this->assertNotNull($snapshot['away_label']);
    }

    public function test_custom_shift_days_control_on_duty_status(): void
    {
        $this->travelTo(Carbon::parse('2026-08-26 10:00:00', 'Africa/Lagos'));
        $ops = $this->supportStaff([
            'shift_days' => [4, 5],
            'shift_starts_at' => '09:00',
            'shift_ends_at' => '17:00',
            'staff_status' => StaffStatus::Active,
            'last_seen_at' => now(),
        ]);

        $snapshot = app(StaffPresence::class)->snapshot($ops);

        $this->assertFalse($snapshot['on_duty']);
        $this->assertSame('off_duty', $snapshot['status']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function supportStaff(array $overrides = []): User
    {
        $permissions = $overrides['permissions'] ?? ['admin.support.manage'];
        unset($overrides['permissions']);

        $role = StaffRole::query()->firstOrCreate(
            ['slug' => 'customer_support'],
            [
                'name' => 'Customer support',
                'permissions' => $permissions,
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
        );

        $user = User::factory()->operationsAdmin()->create($overrides);
        $user->staffRoles()->syncWithoutDetaching([
            $role->id => [
                'assigned_by_user_id' => $user->id,
                'assigned_at' => now(),
            ],
        ]);

        return $user->fresh(['staffRoles']);
    }
}
