<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Support\Patrol\PatrolText;
use App\Support\Patrol\ReviewRule;

class GenericMinimalPraiseRule implements ReviewRule
{
    public function key(): string
    {
        return 'generic_minimal_praise';
    }

    public function evaluate(Review $review): ?array
    {
        $config = config('patrol.review_rules.generic_minimal_praise');
        $maxChars = max(4, (int) ($config['max_chars'] ?? 18));
        $minStars = (float) ($config['min_stars'] ?? 5);
        $requireNoPhoto = (bool) ($config['require_no_photo'] ?? true);
        $minVolume = max(2, (int) ($config['min_volume'] ?? 3));
        $volumeDays = max(1, (int) ($config['volume_days'] ?? 14));

        if (! $this->isHollow($review, $maxChars, $minStars, $requireNoPhoto)) {
            return null;
        }

        $volume = Review::query()
            ->where('user_id', $review->user_id)
            ->where('created_at', '>=', now()->subDays($volumeDays))
            ->get()
            ->filter(fn (Review $item) => $this->isHollow($item, $maxChars, $minStars, $requireNoPhoto))
            ->count();

        if ($volume < $minVolume) {
            return null;
        }

        $length = mb_strlen(PatrolText::normalizeDescription($review->comment));

        return [
            'trigger' => sprintf(
                '%d five-star reviews with almost no comment in %d days (this one is %d characters)',
                $volume,
                $volumeDays,
                $length,
            ),
            'length' => $length,
            'volume' => $volume,
            'threshold' => $minVolume,
        ];
    }

    private function isHollow(Review $review, int $maxChars, float $minStars, bool $requireNoPhoto): bool
    {
        if ((float) $review->rating < $minStars) {
            return false;
        }

        if ($requireNoPhoto && filled($review->photo_url ?: $review->photo_path)) {
            return false;
        }

        return mb_strlen(PatrolText::normalizeDescription($review->comment)) <= $maxChars;
    }
}
