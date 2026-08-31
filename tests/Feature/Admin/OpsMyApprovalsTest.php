<?php

namespace Tests\Feature\Admin;

use App\Models\AdminApproval;
use App\Models\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsMyApprovalsTest extends TestCase
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
            'slug' => 'ops_my_approvals_'.uniqid(),
            'name' => 'Ops',
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

    public function test_ops_can_view_their_pending_approval_requests(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'info');

        $this->actingAs($ops)
            ->get(route('admin.my-approvals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Ops/MyApprovals')
                ->where('pending_count', 1)
                ->where('pending.0.action', 'users.suspend')
                ->where('pending.0.status', AdminApproval::STATUS_PENDING)
                ->where('pending.0.subject_label', 'Demo Artisan'));
    }

    public function test_ops_cannot_view_another_staff_members_requests(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $other = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertOk();

        $approval = AdminApproval::query()->firstOrFail();

        $this->actingAs($other)
            ->get(route('admin.my-approvals.show', $approval))
            ->assertForbidden();
    }

    public function test_super_admin_cannot_access_ops_my_approvals_page(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.my-approvals.index'))
            ->assertForbidden();
    }

    public function test_pending_count_is_shared_with_ops_layout(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('my_approvals_pending', 1));
    }

    public function test_approved_request_moves_to_recent_on_my_approvals_page(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $super = User::factory()->superAdmin()->create();
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertOk();

        $approval = AdminApproval::query()->firstOrFail();

        $this->actingAs($super)
            ->postJson(route('admin.approvals.approve', $approval), ['note' => 'Confirmed'])
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('admin.my-approvals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pending_count', 0)
                ->where('recent.0.uid', $approval->uid)
                ->where('recent.0.status', AdminApproval::STATUS_APPROVED));
    }
}
