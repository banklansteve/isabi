<?php

namespace Tests\Feature\Admin;

use App\Models\PatrolCase;
use App\Models\Review;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Patrol\ReviewPatrolRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewPatrolCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function withPermissions(array $permissions): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'review_patrol_role_'.uniqid(),
            'name' => 'Review patrol test role',
            'description' => 'Test role.',
            'icon' => 'ti ti-binoculars',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 941,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    private function artisanUser(array $overrides = []): User
    {
        return User::factory()->regularUser()->create(array_merge([
            'business_name' => 'Honest Wiring Co',
            'first_name' => 'Kemi',
            'last_name' => 'Ade',
            'created_at' => now()->subDays(40),
        ], $overrides));
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
                'worked_on' => now()->subDays(4)->toDateString(),
                'client_name' => 'Ada',
                'created_ip' => '102.89.22.14',
                'review_requested_at' => now()->subDays(3),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
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
                'comment' => 'The sockets are solid and the kitchen looks finished properly.',
                'client_display_name' => 'Ada Okonkwo',
                'submitted_ip' => '41.210.18.22',
                'submitted_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ], $overrides))->save();

            return $review->fresh() ?? $review;
        });
    }

    public function test_same_ip_as_job_logger_flags_and_localhost_does_not(): void
    {
        $artisan = $this->artisanUser();
        $matchLog = $this->logJob($artisan, ['created_ip' => '41.210.18.22']);
        $localLog = $this->logJob($artisan, ['created_ip' => '127.0.0.1']);
        $emptyLog = $this->logJob($artisan, ['created_ip' => null]);
        $otherLog = $this->logJob($artisan, ['created_ip' => '102.89.22.14']);

        $match = $this->makeReview($matchLog, [
            'submitted_ip' => '41.210.18.22',
            'comment' => 'Came back the same afternoon and wrote this from the workshop.',
        ]);
        $local = $this->makeReview($localLog, ['submitted_ip' => '127.0.0.1']);
        $empty = $this->makeReview($emptyLog, ['submitted_ip' => null]);
        $other = $this->makeReview($otherLog, ['submitted_ip' => '105.112.4.9']);

        $runner = app(ReviewPatrolRunner::class);

        $flagged = $runner->scanReview($match);
        $this->assertNotNull($flagged);
        $this->assertTrue($flagged->rules()->pluck('rule_key')->contains('same_ip_as_job_logger'));
        $this->assertSame('high', $flagged->severity);
        $this->assertNotNull($match->fresh()->hidden_at);
        $this->assertSame('patrol_auto', $match->fresh()->hidden_reason);
        $this->assertStringContainsString('41.x', (string) $flagged->rules()->where('rule_key', 'same_ip_as_job_logger')->first()?->evidence['trigger']);

        $this->assertTrue(
            $runner->scanReview($local) === null
            || ! $runner->scanReview($local)->rules()->pluck('rule_key')->contains('same_ip_as_job_logger'),
        );
        $this->assertTrue(
            $runner->scanReview($empty) === null
            || ! $runner->scanReview($empty)->rules()->pluck('rule_key')->contains('same_ip_as_job_logger'),
        );
        $this->assertTrue(
            $runner->scanReview($other) === null
            || ! $runner->scanReview($other)->rules()->pluck('rule_key')->contains('same_ip_as_job_logger'),
        );
    }

    public function test_normal_honest_review_does_not_flag(): void
    {
        $artisan = $this->artisanUser();
        $log = $this->logJob($artisan, [
            'created_ip' => '102.89.22.14',
            'created_at' => now()->subDays(6),
            'review_requested_at' => now()->subDays(5),
        ]);
        $review = $this->makeReview($log, [
            'submitted_ip' => '105.112.4.9',
            'comment' => 'He finished the wiring cleanly and explained how to reset the breaker.',
            'submitted_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ]);

        $this->assertNull(app(ReviewPatrolRunner::class)->scanReview($review));
    }

    public function test_bulk_seed_same_created_at_does_not_burst_flag(): void
    {
        $artisan = $this->artisanUser();
        $stamp = now()->subDays(20);
        $review = null;

        for ($i = 0; $i < 6; $i++) {
            $log = $this->logJob($artisan, [
                'created_at' => $stamp,
                'updated_at' => $stamp,
                'created_ip' => '102.89.22.'.$i,
            ]);
            $review = $this->makeReview($log, [
                'comment' => 'Finished the wardrobe install and packed the offcuts on job '.$i.'.',
                'submitted_ip' => '105.112.4.'.$i,
                'submitted_at' => $stamp,
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ]);
        }

        $case = app(ReviewPatrolRunner::class)->scanReview($review->fresh());

        $this->assertTrue(
            $case === null || ! $case->rules()->pluck('rule_key')->contains('burst_reviews'),
            'Seed rows that share one created_at must not flag burst_reviews.',
        );
    }

    public function test_one_review_multiple_rules_creates_one_case(): void
    {
        $artisan = $this->artisanUser(['business_name' => 'Kemi Ade Wiring']);
        $log = $this->logJob($artisan, [
            'created_ip' => '41.210.18.22',
            'created_at' => now()->subMinutes(2),
            'review_requested_at' => now()->subMinute(),
        ]);
        $review = $this->makeReview($log, [
            'submitted_ip' => '41.210.18.22',
            'referred_by' => 'Kemi Ade Wiring',
            'comment' => 'Came back immediately and left this from the same workshop network.',
            'submitted_at' => now(),
            'created_at' => now(),
        ]);

        $case = app(ReviewPatrolRunner::class)->scanReview($review);

        $this->assertNotNull($case);
        $this->assertSame(1, PatrolCase::query()->where('review_id', $review->id)->count());
        $this->assertGreaterThanOrEqual(2, $case->rules()->count());
        $this->assertTrue($case->rules()->pluck('rule_key')->contains('same_ip_as_job_logger'));
        $this->assertTrue($case->isReview());
    }

    public function test_high_severity_auto_hides_and_dismiss_restores(): void
    {
        $artisan = $this->artisanUser();
        $admin = $this->superAdmin();
        $log = $this->logJob($artisan, ['created_ip' => '41.210.18.22']);
        $review = $this->makeReview($log, [
            'submitted_ip' => '41.210.18.22',
            'comment' => 'Left this from the same connection the job was logged on.',
        ]);

        $case = app(ReviewPatrolRunner::class)->scanReview($review);
        $this->assertNotNull($case);
        $this->assertSame('high', $case->severity);
        $this->assertNotNull($case->auto_hidden_at);
        $this->assertNotNull($review->fresh()->hidden_at);

        $this->actingAs($admin)
            ->post(route('admin.patrol.dismiss', $case), [
                'reason' => 'Client was on site with the artisan. Legitimate.',
            ])
            ->assertRedirect();

        $this->assertSame(PatrolCase::STATUS_RESOLVED_DISMISSED, $case->fresh()->status);
        $this->assertNull($review->fresh()->hidden_at);
        $this->assertNull($review->fresh()->removed_at);
    }

    public function test_ops_cannot_remove_review_and_super_can_soft_remove(): void
    {
        $artisan = $this->artisanUser();
        $ops = $this->withPermissions(['patrol.view', 'patrol.investigate']);
        $admin = $this->superAdmin();
        $log = $this->logJob($artisan, ['created_ip' => '41.210.18.22']);
        $review = $this->makeReview($log, [
            'submitted_ip' => '41.210.18.22',
            'comment' => 'Left this from the same connection the job was logged on.',
        ]);
        $case = app(ReviewPatrolRunner::class)->scanReview($review);

        $this->actingAs($ops)
            ->post(route('admin.patrol.remove', $case), [
                'reason' => 'Looks fake.',
            ])
            ->assertForbidden();

        $this->actingAs($ops)
            ->post(route('admin.patrol.hide', $case), [
                'reason' => 'Hide it myself.',
            ])
            ->assertForbidden();

        $this->assertNull($review->fresh()->removed_at);

        $this->actingAs($admin)
            ->post(route('admin.patrol.remove', $case), [
                'reason' => 'Artisan wrote their own review.',
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertNotNull($review->removed_at);
        $this->assertSame('patrol_remove', $review->hidden_reason);
        $this->assertTrue(Review::query()->whereKey($review->id)->exists());
        $this->assertSame(PatrolCase::STATUS_RESOLVED_ACTIONED, $case->fresh()->status);
    }

    public function test_scanner_command_does_not_resolve_review_cases(): void
    {
        $artisan = $this->artisanUser();
        $log = $this->logJob($artisan, ['created_ip' => '41.210.18.22']);
        $this->makeReview($log, [
            'submitted_ip' => '41.210.18.22',
            'comment' => 'Left this from the same connection the job was logged on.',
        ]);

        $this->assertSame(0, PatrolCase::query()->reviews()->count());

        Artisan::call('patrol:scan');

        $this->assertGreaterThan(0, PatrolCase::query()->reviews()->count());
        $this->assertSame(0, PatrolCase::query()->reviews()->whereIn('status', [
            PatrolCase::STATUS_RESOLVED_DISMISSED,
            PatrolCase::STATUS_RESOLVED_ACTIONED,
        ])->count());
        $this->assertStringContainsString('No cases were auto-resolved', Artisan::output());
    }
}
