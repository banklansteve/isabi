<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Patrol\PatrolRunner;
use App\Support\Patrol\ReviewPatrolRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatrolThresholdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_backdating_flags_jobs_older_than_five_days(): void
    {
        $artisan = User::factory()->create();

        $flagged = $this->logJob($artisan, [
            'description' => 'Replaced the kitchen sink and repaired the adjacent pipework carefully.',
            'worked_on' => now()->subDays(6)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ok = $this->logJob($artisan, [
            'description' => 'Fitted a new bathroom tap and checked for leaks under the basin.',
            'worked_on' => now()->subDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runner = app(PatrolRunner::class);

        $this->assertTrue(collect($runner->evaluate($flagged))->pluck('key')->contains('backdating'));
        $this->assertFalse(collect($runner->evaluate($ok))->pluck('key')->contains('backdating'));
    }

    public function test_duplicate_review_text_requires_sixty_characters(): void
    {
        $artisan = User::factory()->create();

        $short = 'Great work overall thank you';
        $long = str_repeat('Excellent tiling finish and tidy cleanup after the bathroom remodel. ', 2);

        $this->assertLessThan(60, mb_strlen($short));
        $this->assertGreaterThanOrEqual(60, mb_strlen($long));

        $this->makeReview($artisan, $long);
        $second = $this->makeReview($artisan, $long);
        $tiny = $this->makeReview($artisan, $short);
        $this->makeReview($artisan, $short);

        $runner = app(ReviewPatrolRunner::class);

        $this->assertTrue(collect($runner->evaluate($second))->pluck('key')->contains('duplicate_or_near_duplicate_text'));
        $this->assertFalse(collect($runner->evaluate($tiny))->pluck('key')->contains('duplicate_or_near_duplicate_text'));
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
                'description' => 'Installed a new consumer unit and labelled every circuit carefully.',
                'worked_on' => now()->subDays(2)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ], $overrides))->save();

            return $log->fresh();
        });
    }

    private function makeReview(User $artisan, string $comment): Review
    {
        $log = $this->logJob($artisan, [
            'description' => 'Completed a full bathroom tiling refresh with neat grout lines.',
            'worked_on' => now()->subDay()->toDateString(),
        ]);

        return Review::withoutEvents(function () use ($artisan, $log, $comment) {
            $review = new Review;
            $review->forceFill([
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $artisan->id,
                'work_log_id' => $log->id,
                'rating' => 5,
                'comment' => $comment,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])->save();

            return $review->fresh();
        });
    }
}
