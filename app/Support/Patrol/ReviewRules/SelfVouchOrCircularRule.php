<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Models\User;
use App\Support\Patrol\PatrolText;
use App\Support\Patrol\ReviewRule;

class SelfVouchOrCircularRule implements ReviewRule
{
    public function key(): string
    {
        return 'self_vouch_or_circular';
    }

    public function evaluate(Review $review): ?array
    {
        $artisan = $review->relationLoaded('artisan') ? $review->artisan : $review->artisan()->first();
        if (! $artisan) {
            return null;
        }

        $minChars = max(3, (int) (config('patrol.review_rules.self_vouch_or_circular.min_name_chars') ?? 4));
        $needles = collect([
            $review->referred_by,
            $review->client_display_name,
        ])
            ->map(fn ($value) => PatrolText::normalizeDescription((string) $value))
            ->filter(fn (string $value) => mb_strlen($value) >= $minChars)
            ->unique()
            ->values();

        if ($needles->isEmpty()) {
            return null;
        }

        $identities = $this->artisanIdentities($artisan, $minChars);
        if ($identities === []) {
            return null;
        }

        foreach ($needles as $needle) {
            foreach ($identities as $identity) {
                if ($needle === $identity || str_contains($needle, $identity) || str_contains($identity, $needle)) {
                    return [
                        'trigger' => sprintf(
                            'Reviewer identity “%s” matches the artisan (%s)',
                            $needle,
                            $identity,
                        ),
                        'matched' => $needle,
                        'artisan_identity' => $identity,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function artisanIdentities(User $artisan, int $minChars): array
    {
        return collect([
            $artisan->displayBusinessName(),
            $artisan->business_name,
            $artisan->name,
            trim(($artisan->first_name ?? '').' '.($artisan->last_name ?? '')),
            $artisan->first_name,
        ])
            ->map(fn ($value) => PatrolText::normalizeDescription((string) $value))
            ->filter(fn (string $value) => mb_strlen($value) >= $minChars)
            ->unique()
            ->values()
            ->all();
    }
}
