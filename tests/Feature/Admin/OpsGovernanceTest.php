<?php

namespace Tests\Feature\Admin;

use App\Models\AdminApproval;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\Review;
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

    private function artisanUser(): User
    {
        return User::factory()->regularUser()->create([
            'business_name' => 'Demo Artisan',
            'slug' => 'demo-artisan-'.uniqid(),
        ]);
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

    private function makeReview(WorkLog $log): Review
    {
        return Review::withoutEvents(function () use ($log) {
            $review = new Review;
            $review->forceFill([
                'uid' => (string) Str::uuid(),
                'work_log_id' => $log->id,
                'user_id' => $log->user_id,
                'rating' => 5,
                'would_recommend' => true,
                'comment' => 'Great work.',
                'client_display_name' => 'Ada',
                'submitted_at' => now()->subDay(),
            ])->save();

            return $review->fresh();
        });
    }

    public function test_ops_suspend_requires_super_admin_approval(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'info');

        $this->assertNull($artisan->fresh()->suspended_at);
        $this->assertDatabaseHas('admin_approvals', [
            'action' => 'users.suspend',
            'status' => AdminApproval::STATUS_PENDING,
            'requested_by_user_id' => $ops->id,
        ]);
    }

    public function test_ops_bulk_suspend_requires_super_admin_approval(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $first = $this->artisanUser();
        $second = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.bulk-suspend'), [
                'ids' => [$first->id, $second->id],
                'reason' => 'Coordinated spam accounts',
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'info')
            ->assertJsonPath('pending_count', 2);

        $this->assertNull($first->fresh()->suspended_at);
        $this->assertNull($second->fresh()->suspended_at);
        $this->assertSame(2, AdminApproval::query()
            ->where('action', 'users.suspend')
            ->where('status', AdminApproval::STATUS_PENDING)
            ->where('requested_by_user_id', $ops->id)
            ->count());
    }

    public function test_super_admin_can_approve_suspend(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $super = User::factory()->superAdmin()->create();
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.suspend', $artisan), ['reason' => 'Policy breach'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'info');

        $approval = AdminApproval::query()
            ->where('action', 'users.suspend')
            ->where('status', AdminApproval::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($super)
            ->postJson(route('admin.approvals.approve', $approval), ['note' => 'Confirmed'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertNotNull($artisan->fresh()->suspended_at);
        $this->assertSame(AdminApproval::STATUS_APPROVED, $approval->fresh()->status);
    }

    public function test_super_admin_bulk_suspend_is_immediate(): void
    {
        $super = User::factory()->superAdmin()->create();
        $first = $this->artisanUser();
        $second = $this->artisanUser();

        $this->actingAs($super)
            ->postJson(route('admin.users.bulk-suspend'), [
                'ids' => [$first->id, $second->id],
                'reason' => 'Confirmed policy breach',
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success')
            ->assertJsonPath('count', 2);

        $this->assertNotNull($first->fresh()->suspended_at);
        $this->assertNotNull($second->fresh()->suspended_at);
        $this->assertDatabaseMissing('admin_approvals', [
            'action' => 'users.suspend',
            'status' => AdminApproval::STATUS_PENDING,
        ]);
    }

    public function test_ops_can_hide_job_without_approval(): void
    {
        $ops = $this->opsWith(['admin.content.manage']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($ops)
            ->postJson(route('admin.jobs.hide', $job->uid), ['reason' => 'Looks fake'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertNotNull($job->fresh()->hidden_at);
        $this->assertDatabaseMissing('admin_approvals', [
            'action' => 'jobs.hide',
            'status' => AdminApproval::STATUS_PENDING,
        ]);
    }

    public function test_ops_can_hide_review_without_approval(): void
    {
        $ops = $this->opsWith(['admin.content.manage']);
        $review = $this->makeReview($this->logJob($this->artisanUser()));

        $this->actingAs($ops)
            ->postJson(route('admin.reviews.hide', $review), ['reason' => 'Spam review'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertNotNull($review->fresh()->hidden_at);
    }

    public function test_ops_delete_user_requires_super_admin_approval(): void
    {
        $ops = $this->opsWith(['admin.users.view', 'admin.users.manage']);
        $super = User::factory()->superAdmin()->create();
        $artisan = $this->artisanUser();

        $this->actingAs($ops)
            ->postJson(route('admin.users.destroy', $artisan), [
                'reason' => 'Duplicate account confirmed',
                'confirmation' => $artisan->displayBusinessName(),
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'info');

        $this->assertNotNull($artisan->fresh());
        $this->assertDatabaseHas('admin_approvals', [
            'action' => 'users.delete',
            'status' => AdminApproval::STATUS_PENDING,
            'requested_by_user_id' => $ops->id,
        ]);

        $delivery = AnnouncementDelivery::query()
            ->where('user_id', $super->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($delivery);
        $announcement = Announcement::query()->find($delivery->announcement_id);
        $this->assertSame('staff_approval', $announcement->segment['kind'] ?? null);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Overview')
                ->where('admin_inbox.open_count', 1)
                ->where('admin_inbox.priority_groups.0.key', 'approvals'));
    }

    public function test_ops_delete_job_requires_super_admin_approval(): void
    {
        $ops = $this->opsWith(['admin.content.manage']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($ops)
            ->postJson(route('admin.jobs.remove', $job->uid), ['reason' => 'Fabricated work log'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'info');

        $this->assertNull($job->fresh()->removed_at);
        $this->assertDatabaseHas('admin_approvals', [
            'action' => 'jobs.remove',
            'status' => AdminApproval::STATUS_PENDING,
        ]);
    }

    public function test_super_admin_can_approve_job_delete(): void
    {
        $ops = $this->opsWith(['admin.content.manage']);
        $super = User::factory()->superAdmin()->create();
        $job = $this->logJob($this->artisanUser());

        $approval = AdminApproval::query()->create([
            'uid' => 'APTESTDEL01',
            'action' => 'jobs.remove',
            'subject_type' => $job->getMorphClass(),
            'subject_id' => $job->id,
            'payload' => [
                'work_log_id' => $job->id,
                'work_log_uid' => $job->uid,
                'reason' => 'Fake work',
            ],
            'reason' => 'Fake work',
            'status' => AdminApproval::STATUS_PENDING,
            'requested_by_user_id' => $ops->id,
        ]);

        $this->actingAs($super)
            ->postJson(route('admin.approvals.approve', $approval), ['note' => 'Looks right'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertNotNull($job->fresh()->removed_at);
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
