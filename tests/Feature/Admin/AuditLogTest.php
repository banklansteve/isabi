<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_operations_staff_cannot_open_the_audit_log(): void
    {
        $staff = User::factory()->operationsAdmin()->create();

        $this->actingAs($staff)
            ->get(route('admin.audit.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_today_by_default_in_readable_form(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'first_name' => 'Ada',
            'last_name' => 'Super',
            'email' => 'ada@kraftrack.test',
        ]);
        $ops = User::factory()->operationsAdmin()->create([
            'first_name' => 'Chidi',
            'last_name' => 'Ops',
            'email' => 'chidi.ops@kraftrack.test',
        ]);

        AdminAuditLog::query()->create([
            'actor_id' => $ops->id,
            'action' => 'staff.disabled',
            'summary' => "{$ops->name} disabled a teammate: end of contract.",
            'old_values' => ['staff_status' => 'active'],
            'new_values' => ['staff_status' => 'suspended', 'reason' => 'end of contract'],
            'created_at' => now(),
        ]);

        AdminAuditLog::query()->create([
            'actor_id' => $ops->id,
            'action' => 'jobs.flagged',
            'summary' => "{$ops->name} flagged a job last month.",
            'old_values' => ['flagged_at' => null],
            'new_values' => ['flagged_at' => now()->subMonth()->toIso8601String(), 'reason' => 'spam'],
            'created_at' => now()->subMonth(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Audit/Index')
                ->where('filters.from', now(config('app.display_timezone'))->toDateString())
                ->where('filters.to', now(config('app.display_timezone'))->toDateString())
                ->has('logs.data', 1)
                ->where('logs.data.0.action_label', 'Disabled staff access')
                ->where('logs.data.0.actor.email', 'chidi.ops@kraftrack.test')
                ->where('logs.data.0.changes.0.label', 'Access status')
                ->where('logs.data.0.changes.0.from', 'Active')
                ->where('logs.data.0.changes.0.to', 'Disabled')
                ->where('logs.data.0.when', fn ($when) => is_string($when) && $when !== '' && ! str_contains($when, '{'))
                ->missing('logs.data.0.old_values')
                ->missing('logs.data.0.new_values'));
    }

    public function test_super_admin_can_search_operations_staff_by_name_and_email(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $chidi = User::factory()->operationsAdmin()->create([
            'first_name' => 'Chidi',
            'last_name' => 'Ops',
            'email' => 'chidi.ops@kraftrack.test',
        ]);
        $amaka = User::factory()->operationsAdmin()->create([
            'first_name' => 'Amaka',
            'last_name' => 'Ops',
            'email' => 'amaka.ops@kraftrack.test',
        ]);

        AdminAuditLog::query()->create([
            'actor_id' => $chidi->id,
            'action' => 'staff.messaged',
            'summary' => "{$chidi->name} messaged a teammate.",
            'created_at' => now(),
        ]);

        AdminAuditLog::query()->create([
            'actor_id' => $amaka->id,
            'action' => 'users.suspended',
            'summary' => "{$amaka->name} suspended an artisan.",
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['q' => 'chidi.ops@kraftrack.test']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.actor.email', 'chidi.ops@kraftrack.test'));

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['q' => 'Amaka']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.actor.name', 'Amaka Ops'));
    }

    public function test_date_filter_limits_results(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $ops = User::factory()->operationsAdmin()->create();
        $tz = config('app.display_timezone');

        AdminAuditLog::query()->create([
            'actor_id' => $ops->id,
            'action' => 'support.resolved',
            'summary' => 'Resolved a ticket.',
            'created_at' => now($tz)->subDays(3),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit.index', [
                'from' => now($tz)->toDateString(),
                'to' => now($tz)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('logs.data', 0));

        $this->actingAs($admin)
            ->get(route('admin.audit.index', [
                'from' => now($tz)->subDays(7)->toDateString(),
                'to' => now($tz)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.action_label', 'Closed a support chat'));
    }
}
