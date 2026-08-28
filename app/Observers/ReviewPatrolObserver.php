<?php

namespace App\Observers;

use App\Jobs\ScanReviewForPatrol;
use App\Models\Review;

class ReviewPatrolObserver
{
    public function created(Review $review): void
    {
        ScanReviewForPatrol::dispatch($review->id);
    }
}
