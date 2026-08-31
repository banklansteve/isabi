<?php

namespace Tests\Feature\Admin;

use App\Models\PatrolCase;
use App\Models\PatrolCaseRule;
use App\Models\Review;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModerationDeskTest extends TestCase
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
            'slug' => 'desk_ops_'.uniqid(),
            'name' => 'Desk ops',
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
            'business_name' => 'Desk Artisan',
            'slug' => 'desk-'.uniqid(),
        ]);
    }

    public function test_ops_can_open_moderation_desk_preview(): void
    {
        $ops = $this->opsWith(['admin.content.manage', 'patrol.view', 'admin.users.view']);
        $artisan = $this->artisanUser();

        WorkLog::withoutEvents(function () use ($artisan) {
            $log = new WorkLog;
            $log->forceFill([
                'user_id' => $artisan->id,
                'uid' => (string) Str::uuid(),
                'description' => 'Suspicious timeline',
                'worked_on' => now()->toDateString(),
                'flagged_at' => now(),
                'flag_reason' => 'Backdated entries',
            ])->save();
        });

        PatrolCase::query()->create([
            'kind' => PatrolCase::KIND_JOB,
            'work_log_id' => WorkLog::query()->first()->id,
            'user_id' => $artisan->id,
            'status' => PatrolCase::STATUS_NEW,
            'severity' => PatrolCase::SEVERITY_HIGH,
            'flagged_at' => now(),
        ]);

        $this->actingAs($ops)
            ->get(route('admin.moderation-desk.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ModerationDesk/Index')
                ->where('stats.all', fn ($count) => $count >= 2)
                ->where('items', fn ($items) => collect($items)->contains(
                    fn ($item) => ($item['kind'] ?? '') === 'jobs' || ($item['kind'] ?? '') === 'patrol',
                )));
    }

    public function test_moderation_desk_filters_by_kind(): void
    {
        $ops = $this->opsWith(['admin.content.manage']);
        $artisan = $this->artisanUser();

        Review::withoutEvents(function () use ($artisan) {
            $log = new WorkLog;
            $log->forceFill([
                'user_id' => $artisan->id,
                'uid' => (string) Str::uuid(),
                'description' => 'Job',
                'worked_on' => now()->toDateString(),
            ])->save();

            $review = new Review;
            $review->forceFill([
                'uid' => (string) Str::uuid(),
                'work_log_id' => $log->id,
                'user_id' => $artisan->id,
                'rating' => 2,
                'comment' => 'Looks fake',
                'submitted_at' => now(),
                'flagged_at' => now(),
            ])->save();
        });

        $this->actingAs($ops)
            ->get(route('admin.moderation-desk.index', ['filter' => 'reviews']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filter', 'reviews')
                ->where('items', fn ($items) => collect($items)->every(
                    fn ($item) => ($item['kind'] ?? '') === 'reviews',
                )));
    }
}
