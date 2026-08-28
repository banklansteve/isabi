<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Support\Patrol\PatrolText;
use App\Support\Patrol\ReviewRule;

class SameClientContactBurstRule implements ReviewRule
{
    public function key(): string
    {
        return 'same_client_contact_burst';
    }

    public function evaluate(Review $review): ?array
    {
        $log = $review->relationLoaded('workLog') ? $review->workLog : $review->workLog()->first();
        $normalized = PatrolText::normalizeWhatsapp($log?->client_whatsapp);
        if (! $normalized) {
            return null;
        }

        $config = config('patrol.review_rules.same_client_contact_burst');
        $minReviews = max(3, (int) ($config['min_reviews'] ?? 4));
        $windowDays = max(1, (int) ($config['window_days'] ?? 5));
        $submitted = $review->submitted_at ?? $review->created_at ?? now();
        $from = $submitted->copy()->subDays($windowDays);
        $to = $submitted->copy()->addDays($windowDays);

        $cluster = Review::query()
            ->where('user_id', $review->user_id)
            ->whereBetween('submitted_at', [$from, $to])
            ->with('workLog:id,client_whatsapp')
            ->get()
            ->filter(
                fn (Review $item) => PatrolText::normalizeWhatsapp($item->workLog?->client_whatsapp) === $normalized,
            );

        if ($cluster->count() < $minReviews) {
            return null;
        }

        $spanDays = max(
            1,
            (int) ceil($cluster->min('submitted_at')->diffInSeconds($cluster->max('submitted_at')) / 86400),
        );

        return [
            'trigger' => sprintf(
                'same client WhatsApp on %d reviews within %d days',
                $cluster->count(),
                $spanDays,
            ),
            'review_count' => $cluster->count(),
            'window_days' => $windowDays,
            'span_days' => $spanDays,
            'threshold' => $minReviews,
            'contact_suffix' => substr($normalized, -4),
            'review_ids' => $cluster->pluck('id')->all(),
        ];
    }
}
