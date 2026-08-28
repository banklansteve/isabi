<?php

namespace Tests\Feature\Admin;

use App\Models\AdminApproval;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpsGovernanceTest extends TestCase
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
            'slug' => 'gov_role_'.uniqid(),
            'name' => 'Gov test',
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

    private function logJob(User $artisan): WorkLog
    {
        return WorkLog::withoutEvents(function () use ($artisan) {
            $log = new WorkLog;
            $log->forceFill([
                'user_id' => $artisan->id,
                'uid' => 'WL'.strtoupper(Str::random(10)),
                'description' => 'Test job',
                'worked_on' => now()->toDateString(),
                'client_name' => 'Client',
            ])->save();

            return $log->fresh();
        });
    }

    public function test_ops_suspend_requires_super_admin_approval(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $artisan = User::factory()->regularUser()->create();

        $this->actingAs($ops)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertRedirect();

        $this->assertNull($artisan->fresh()->suspended_at);
        $this->assertDatabaseHas('admin_approvals', [
            'action' => 'users.suspend',
            'status' => AdminApproval::STATUS_PENDING,
            'requested_by_user_id' => $ops->id,
        ]);
    }

    public function test_super_admin_can_approve_job_hide(): void
    {
        $ops = $this->opsWith(['admin.content.manage']);
        $super = User::factory()->superAdmin()->create();
        $artisan = User::factory()->regularUser()->create();
        $job = $this->logJob($artisan);

        $approval = AdminApproval::query()->create([
            'uid' => 'APTESTHIDE01',
            'action' => 'jobs.hide',
            'subject_type' => $job->getMorphClass(),
            'subject_id' => $job->id,
            'payload' => [
                'work_log_id' => $job->id,
                'reason' => 'Fake work',
            ],
            'reason' => 'Fake work',
            'status' => AdminApproval::STATUS_PENDING,
            'requested_by_user_id' => $ops->id,
        ]);

        $this->actingAs($super)
            ->from(route('admin.approvals.index'))
            ->post(route('admin.approvals.approve', $approval), ['note' => 'Looks right'])
            ->assertRedirect();

        $this->assertNotNull($job->fresh()->hidden_at);
        $this->assertSame(AdminApproval::STATUS_APPROVED, $approval->fresh()->status);
    }

    public function test_ops_cannot_open_staff_directory(): void
    {
        $ops = User::factory()->operationsAdmin()->create();

        $this->actingAs($ops)
            ->get(route('admin.staff.index'))
            ->assertForbidden();
    }
}
