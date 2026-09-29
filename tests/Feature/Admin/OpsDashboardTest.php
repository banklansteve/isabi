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
                ->where('items.0.queue', 'Customer support')
                ->where('items.0.unread', true)
                ->has('priority_groups', 1)
                ->has('shortcuts', 2)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->pluck('key')->contains('support'))
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
                ->where('items.0.title', 'Rapid logging')
                ->where('items.0.tone', 'high')
                ->has('priority_groups', 1)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->pluck('key')->contains('patrol_jobs'))
                ->where('roles.0.short', 'Patrol'));
    }

    public function test_combined_roles_merge_one_feed_sorted_by_urgency(): void
    {
        $staff = $this->withPermissions(
            ['admin.support.manage', 'patrol.view'],
            'customer_support',
            'Customer support',
        );
        $moderation = StaffRole::query()->firstOrCreate(
            ['slug' => 'moderation'],
            [
                'name' => 'Moderation',
                'icon' => 'ti ti-shield',
                'permissions' => ['patrol.view'],
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
        );
        $moderation->forceFill([
            'permissions' => ['patrol.view'],
            'is_active' => true,
        ])->save();
        $staff->staffRoles()->syncWithoutDetaching([
            $moderation->id => [
                'assigned_by_user_id' => $staff->id,
                'assigned_at' => now(),
            ],
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
                ->has('priority_groups', 2)
                ->has('items', 2)
                ->where('items.0.tone', 'high')
                ->where('items.1.queue', 'Customer support')
                ->where('open_count', 2)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->pluck('key')->contains('support')
                    && collect($shortcuts)->pluck('key')->contains('patrol_jobs')));
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
            ->assertInertia(fn ($page) => $page
                ->has('items', 0)
                ->where('unread_count', 0)
                ->where('open_count', 0));
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
                ->where('items.0.unread', true)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->firstWhere('key', 'support')['count'] === 1));

        $this->actingAs($staff)
            ->get(route('admin.support.show', $ticket))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', 0)
                ->has('items', 0)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->firstWhere('key', 'support')['count'] === 0));
    }

    public function test_multiple_support_tickets_are_tracked_individually(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');
        $artisan = User::factory()->regularUser()->create();

        $first = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'First chat',
            'status' => SupportTicket::STATUS_OPEN,
            'created_at' => now()->subHours(2),
        ]);
        SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Second chat',
            'status' => SupportTicket::STATUS_OPEN,
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('items', 2)
                ->where('unread_count', 2)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->firstWhere('key', 'support')['count'] === 2));

        $this->actingAs($staff)
            ->get(route('admin.support.show', $first))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', 1)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->firstWhere('key', 'support')['count'] === 1));
    }

    public function test_opening_patrol_queue_clears_patrol_badge(): void
    {
        $staff = $this->withPermissions(['patrol.view', 'patrol.investigate'], 'patrol', 'Patrol');
        $artisan = User::factory()->regularUser()->create([
            'first_name' => 'Tunde',
            'last_name' => 'Musa',
        ]);
        $this->flagJob($this->logJob($artisan), $artisan, 'high', 'rapid_logging');

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', 1)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->firstWhere('key', 'patrol_jobs')['count'] === 1));

        $this->actingAs($staff)
            ->get(route('admin.patrol.jobs'))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unread_count', 0)
                ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->firstWhere('key', 'patrol_jobs')['count'] === 0));
    }

    public function test_opening_reengagement_clears_reengage_badge(): void
    {
        $staff = $this->withPermissions(
            ['ops.reengagement.manage', 'admin.users.view'],
            'growth_reengage_test',
            'Growth re-engage test',
        );

        User::factory()->regularUser()->create([
            'last_login_at' => now()->subDays(45),
            'created_at' => now()->subDays(60),
            'email_verified_at' => now()->subDays(60),
            'suspended_at' => null,
        ]);

        $home = app(OpsAttentionFeed::class)->home($staff);
        $shortcut = collect($home['shortcuts'])->firstWhere('key', 'reengagement');

        $this->assertNotNull($shortcut);
        $this->assertSame(1, (int) $shortcut['count']);

        $this->actingAs($staff)
            ->get(route('admin.reengagement.index'))
            ->assertOk();

        $after = app(OpsAttentionFeed::class)->home($staff->fresh(['staffRoles']));
        $cleared = collect($after['shortcuts'])->firstWhere('key', 'reengagement');

        $this->assertNotNull($cleared);
        $this->assertSame(0, (int) $cleared['count']);
    }

    public function test_ops_can_open_support_queue(): void
    {
        $staff = $this->withPermissions(
            ['admin.support.manage'],
            'customer_support',
            'Customer support',
        );

        $this->actingAs($staff)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Support/Index'));
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

    public function test_ops_tasks_excludes_asap_and_ops_messages(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');
        $peer = User::factory()->operationsAdmin()->create(['name' => 'Ada George']);
        $artisan = User::factory()->regularUser()->create();

        SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Billing help',
            'status' => SupportTicket::STATUS_OPEN,
            'created_at' => now()->subHours(2),
        ]);

        $this->actingAs($peer)->get(route('admin.asap.index'))->assertOk();
        $asap = \App\Models\StaffConversation::query()
            ->where('type', \App\Models\StaffConversation::TYPE_ASAP)
            ->firstOrFail();
        $this->actingAs($peer)
            ->postJson(route('admin.asap.store', $asap), ['body' => 'Team ping'])
            ->assertOk();

        $this->actingAs($peer)
            ->post(route('admin.asap.direct'), ['user_id' => $staff->id])
            ->assertRedirect();
        $dm = \App\Models\StaffConversation::query()
            ->where('type', \App\Models\StaffConversation::TYPE_DIRECT)
            ->latest('id')
            ->firstOrFail();
        $this->actingAs($peer)
            ->postJson(route('admin.asap.store', $dm), ['body' => 'Hi there'])
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.tasks'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/Tasks')
                ->has('items', 1)
                ->where('items.0.queue', 'Customer support')
                ->where('items', fn ($items) => collect($items)->every(
                    fn ($item) => ! in_array($item['group'] ?? '', ['asap', 'ops_chat'], true)
                )));
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

    public function test_ops_staff_can_update_account_name(): void
    {
        $staff = $this->withPermissions(['admin.support.manage'], 'customer_support', 'Customer support');

        $this->actingAs($staff)
            ->patchJson(route('admin.account.profile'), [
                'first_name' => 'Ada',
                'last_name' => 'Okonkwo',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Name updated')
            ->assertJsonPath('account.name', 'Ada Okonkwo');

        $staff->refresh();
        $this->assertSame('Ada', $staff->first_name);
        $this->assertSame('Okonkwo', $staff->last_name);
        $this->assertSame('Ada Okonkwo', $staff->name);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function withPermissions(array $permissions, string $slug, string $name): User
    {
        $role = StaffRole::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => 'Test role.',
                'icon' => 'ti ti-headset',
                'permissions' => $permissions,
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
        );

        $role->forceFill([
            'name' => $name,
            'permissions' => $permissions,
            'is_active' => true,
        ])->save();

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->syncWithoutDetaching([
            $role->id => [
                'assigned_by_user_id' => $user->id,
                'assigned_at' => now(),
            ],
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
