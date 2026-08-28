<?php

namespace App\Support\Patrol;

use App\Models\Review;

interface ReviewRule
{
    public function key(): string;

    /**
     * @return array<string, mixed>|null
     */
    public function evaluate(Review $review): ?array;
}
