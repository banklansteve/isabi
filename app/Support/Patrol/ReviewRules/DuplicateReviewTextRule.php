<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Support\Patrol\PatrolText;
use App\Support\Patrol\ReviewRule;

class DuplicateReviewTextRule implements ReviewRule
{
    public function key(): string
    {
        return 'duplicate_or_near_duplicate_text';
    }

    public function evaluate(Review $review): ?array
    {
        $config = config('patrol.review_rules.duplicate_or_near_duplicate_text');
        $minChars = max(20, (int) ($config['min_chars'] ?? 36));
        $similarity = max(80, (int) ($config['similarity_percent'] ?? 90));
        $sameMin = max(2, (int) ($config['same_artisan_min'] ?? 2));
        $crossMin = max(2, (int) ($config['cross_artisan_min'] ?? 3));
        $days = max(7, (int) ($config['lookback_days'] ?? 90));
        $needle = PatrolText::normalizeDescription($review->comment);

        if (mb_strlen($needle) < $minChars) {
            return null;
        }

        $window = now()->subDays($days);

        $same = Review::query()
            ->where('user_id', $review->user_id)
            ->where('created_at', '>=', $window)
            ->get(['id', 'comment', 'user_id']);

        $sameMatches = $same->filter(
            fn (Review $item) => PatrolText::similarEnough(
                $needle,
                PatrolText::normalizeDescription($item->comment),
                $similarity,
            ),
        );

        if ($sameMatches->count() >= $sameMin) {
            return [
                'trigger' => sprintf(
                    'Near-identical review text on %d of this artisan’s jobs',
                    $sameMatches->count(),
                ),
                'scope' => 'same_artisan',
                'match_count' => $sameMatches->count(),
                'sample' => mb_substr($needle, 0, 80),
                'review_ids' => $sameMatches->pluck('id')->all(),
            ];
        }

        $others = Review::query()
            ->where('user_id', '!=', $review->user_id)
            ->where('created_at', '>=', $window)
            ->latest('id')
            ->limit(400)
            ->get(['id', 'comment', 'user_id']);

        $crossMatches = $others->filter(
            fn (Review $item) => PatrolText::similarEnough(
                $needle,
                PatrolText::normalizeDescription($item->comment),
                $similarity,
            ),
        );

        $artisanCount = $crossMatches->pluck('user_id')->unique()->count() + 1;

        if ($artisanCount >= $crossMin) {
            return [
                'trigger' => sprintf('Same long review text used across %d artisans', $artisanCount),
                'scope' => 'cross_artisan',
                'artisan_count' => $artisanCount,
                'sample' => mb_substr($needle, 0, 80),
                'review_ids' => $crossMatches->pluck('id')->prepend($review->id)->unique()->values()->all(),
            ];
        }

        return null;
    }
}
