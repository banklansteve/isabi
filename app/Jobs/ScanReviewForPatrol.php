<?php

namespace App\Jobs;

use App\Models\Review;
use App\Support\Patrol\ReviewPatrolRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScanReviewForPatrol implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reviewId) {}

    public function handle(ReviewPatrolRunner $runner): void
    {
        $review = Review::query()->with(['workLog', 'artisan'])->find($this->reviewId);
        if (! $review) {
            return;
        }

        $runner->scanReview($review);
    }
}
