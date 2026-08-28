<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Support\Patrol\ReviewRule;

class BurstReviewsRule implements ReviewRule
{
    public function key(): string
    {
        return 'burst_reviews';
    }

    public function evaluate(Review $review): ?array
    {
        $config = config('patrol.review_rules.burst_reviews');
        $minReviews = max(2, (int) ($config['min_reviews'] ?? 4));
        $windowMinutes = max(1, (int) ($config['window_minutes'] ?? 20));
        $maxAgeHours = max(1, (int) ($config['max_age_hours'] ?? 48));
        $minDistinctSeconds = max(2, (int) ($config['min_distinct_seconds'] ?? 3));
        $createdAt = $review->created_at ?? $review->submitted_at ?? now();
        $cutoff = now()->subHours($maxAgeHours);

        if ($createdAt->lt($cutoff)) {
            return null;
        }

        $from = $createdAt->copy()->subMinutes($windowMinutes);
        $to = $createdAt->copy()->addMinutes($windowMinutes);

        $cluster = Review::query()
            ->where('user_id', $review->user_id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['id', 'created_at']);

        if ($cluster->count() < $minReviews) {
            return null;
        }

        $newest = $cluster->last()?->created_at;
        if (! $newest || $newest->lt($cutoff)) {
            return null;
        }

        $distinctSeconds = $cluster
            ->map(fn (Review $item) => $item->created_at?->format('Y-m-d H:i:s'))
            ->filter()
            ->unique()
            ->count();

        if ($distinctSeconds < $minDistinctSeconds) {
            return null;
        }

        $spanSeconds = abs((int) $cluster->first()->created_at->diffInSeconds($cluster->last()->created_at));
        $spanMinutes = max(1, (int) ceil($spanSeconds / 60));

        return [
            'trigger' => sprintf(
                '%d reviews landed within %d %s',
                $cluster->count(),
                $spanMinutes,
                $spanMinutes === 1 ? 'minute' : 'minutes',
            ),
            'count' => $cluster->count(),
            'window_minutes' => $windowMinutes,
            'span_minutes' => $spanMinutes,
            'distinct_seconds' => $distinctSeconds,
            'threshold' => $minReviews,
            'review_ids' => $cluster->pluck('id')->all(),
        ];
    }
}
