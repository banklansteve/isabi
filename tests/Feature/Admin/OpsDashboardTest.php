<?php

namespace Tests\Feature\Admin;

use App\Models\PatrolCase;
use App\Models\PatrolCaseRule;
use App\Models\StaffRole;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\OpsAttentionFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_still_gets_the_overview_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Overview')
                ->where('restricted', false)
                ->has('kpis'));
    }

    public function test_restricted_staff_see_an_empty_ops_home(): void
    {
        $staff = User::factory()->operationsAdmin()->create();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->where('restricted', true)
                ->where('items', [])
                ->where('shortcuts', []));
    }

    public function test_support_home_includes_tickets_and_hides_patrol(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');
        $artisan = User::factory()->regularUser()->create();

        SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'I cannot log a job',
            'status' => SupportTicket::STATUS_OPEN,
            'created_at' => now()->subHours(4),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->where('restricted', false)
                ->has('items', 1)
                ->where('items.0.title', 'I cannot log a job')
                ->where('items.0.queue', 'Support')
                ->where('items.0.unread', true)
                ->has('shortcuts', 1)
                ->where('shortcuts.0.key', 'support')
                ->where('roles.0.short', 'Support')
                ->where('roles.0.href', route('admin.support.index'))
                ->missing('kpis'));
    }

    public function test_patrol_home_surfaces_high_severity_jobs_first(): void
    {
        $staff = $this->withPermissions(['patrol.view', 'patrol.investigate'], 'patrol', 'Patrol');
        $artisan = User::factory()->regularUser()->create([
            'first_name' => 'Tunde',
            'last_name' => 'Musa',
            'name' => 'Tunde Musa',
        ]);
        $log = $this->logJob($artisan);
        $this->flagJob($log, $artisan, 'high', 'rapid_logging');

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->has('items', 1)
                ->where('items.0.title', 'High-severity job log flagged')
                ->where('items.0.tone', 'high')
                ->where('shortcuts.0.key', 'patrol')
                ->where('roles.0.short', 'Patrol'));
    }

    public function test_combined_roles_merge_one_feed_sorted_by_urgency(): void
    {
        $staff = $this->withPermissions(
            ['admin.support.manage', 'patrol.view'],
            'customer_support',
            'Customer support',
        );
        $moderation = StaffRole::query()->create([
            'slug' => 'moderation',
            'name' => 'Moderation',
            'icon' => 'ti ti-shield',
            'permissions' => ['patrol.view'],
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $staff->staffRoles()->attach($moderation->id, [
            'assigned_by_user_id' => $staff->id,
            'assigned_at' => now(),
        ]);
        $staff = $staff->fresh(['staffRoles']);

        $artisan = User::factory()->regularUser()->create([
            'first_name' => 'Tunde',
            'last_name' => 'Musa',
        ]);
        $this->flagJob($this->logJob($artisan), $artisan, 'high', 'rapid_logging');
        SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'created_at' => now()->subHours(4),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Home')
                ->has('items', 2)
                ->where('items.0.tone', 'high')
                ->where('items.1.queue', 'Support')
                ->where('open_count', 2)
                ->has('shortcuts', 2));
    }

    public function test_support_staff_do_not_receive_finance_shortcuts(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->pluck('key')->contains('credits') === false
                    && collect($shortcuts)->pluck('key')->contains('analytics') === false));
    }

    public function test_marking_attention_read_clears_unread(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');
        SupportTicket::query()->create([
            'user_id' => User::factory()->regularUser()->create()->id,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $item = app(OpsAttentionFeed::class)->home($staff)['items'][0];

        $this->actingAs($staff)
            ->post(route('admin.attention.read'), [
                'key' => $item['key'],
                'signature' => $item['signature'],
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('staff_attention_reads', [
            'user_id' => $staff->id,
            'item_key' => $item['key'],
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page->where('items.0.unread', false)->where('unread_count', 0));
    }

    public function test_opening_a_support_chat_marks_the_task_read(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');
        $ticket = SupportTicket::query()->create([
            'user_id' => User::factory()->regularUser()->create()->id,
            'status' => SupportTicket::STATUS_OPEN,
            'subject' => 'Need help with a review',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', 1)
                ->where('items.0.unread', true));

        $this->actingAs($staff)
            ->get(route('admin.support.show', $ticket))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', 0)
                ->where('items.0.unread', false));
    }

    public function test_ops_can_open_chats_and_messaging_queues(): void
    {
        $staff = $this->withPermissions(
            ['admin.support.manage', 'admin.messaging.manage'],
            'customer_support',
            'Customer support',
        );

        $this->actingAs($staff)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Support/Index'));

        $this->actingAs($staff)
            ->get(route('admin.messaging.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Messaging/Index'));
    }

    public function test_super_admin_cannot_open_ops_tasks(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.tasks'))
            ->assertForbidden();
    }

    public function test_ops_tasks_page_renders(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');

        $this->actingAs($staff)
            ->get(route('admin.tasks'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Ops/Tasks'));
    }

    public function test_ops_staff_can_open_account(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');

        $this->actingAs($staff)
            ->get(route('admin.account'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Account')
                ->where('tab', 'profile'));

        $this->actingAs($staff)
            ->get(route('admin.account', ['tab' => 'password']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Account')
                ->where('tab', 'password'));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function withPermissions(array $permissions, string $slug, string $name): User
    {
        $role = StaffRole::query()->create([
            'slug' => $slug,
            'name' => $name,
            'description' => 'Test role.',
            'icon' => 'ti ti-headset',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
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
                'description' => 'Installed a new consumer unit and labelled every circuit.',
                'worked_on' => now()->toDateString(),
                'client_name' => 'Ada',
            ], $overrides))->save();

            return $log->fresh() ?? $log;
        });
    }

    private function flagJob(WorkLog $log, User $artisan, string $severity, string $ruleKey): PatrolCase
    {
        $case = PatrolCase::query()->create([
            'kind' => PatrolCase::KIND_JOB,
            'work_log_id' => $log->id,
            'user_id' => $artisan->id,
            'status' => PatrolCase::STATUS_NEW,
            'severity' => $severity,
            'flagged_at' => now(),
        ]);

        PatrolCaseRule::query()->create([
            'patrol_case_id' => $case->id,
            'rule_key' => $ruleKey,
            'severity' => $severity,
            'evidence' => ['trigger' => 'Rapid logging'],
            'detected_at' => now(),
        ]);

        return $case->fresh(['rules', 'artisan']) ?? $case;
    }
}
