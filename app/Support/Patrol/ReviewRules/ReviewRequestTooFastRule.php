<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Support\Patrol\ReviewRule;

class ReviewRequestTooFastRule implements ReviewRule
{
    public function key(): string
    {
        return 'review_request_too_fast';
    }

    public function evaluate(Review $review): ?array
    {
        $config = config('patrol.review_rules.review_request_too_fast');
        $maxSeconds = max(30, (int) ($config['max_seconds'] ?? 180));
        $soloSeconds = max(15, (int) ($config['solo_seconds'] ?? 60));
        $repeatMin = max(2, (int) ($config['repeat_min'] ?? 2));
        $repeatHours = max(1, (int) ($config['repeat_window_hours'] ?? 24));

        $submitted = $review->submitted_at ?? $review->created_at ?? now();
        $log = $review->relationLoaded('workLog') ? $review->workLog : $review->workLog()->first();
        if (! $log) {
            return null;
        }

        $anchors = collect([
            $log->review_requested_at,
            $log->created_at,
        ])->filter();

        if ($anchors->isEmpty()) {
            return null;
        }

        $seconds = (int) $anchors
            ->map(fn ($stamp) => max(0, (int) $stamp->diffInSeconds($submitted, false)))
            ->min();

        if ($seconds > $maxSeconds) {
            return null;
        }

        $fastCount = Review::query()
            ->where('user_id', $review->user_id)
            ->where('created_at', '>=', now()->subHours($repeatHours))
            ->with('workLog:id,created_at,review_requested_at')
            ->get()
            ->filter(function (Review $item) use ($maxSeconds) {
                $job = $item->workLog;
                if (! $job) {
                    return false;
                }
                $submittedAt = $item->submitted_at ?? $item->created_at;
                $seconds = collect([$job->review_requested_at, $job->created_at])
                    ->filter()
                    ->map(fn ($stamp) => max(0, (int) $stamp->diffInSeconds($submittedAt, false)))
                    ->min();

                return $seconds !== null && $seconds <= $maxSeconds;
            })
            ->count();

        if ($seconds > $soloSeconds && $fastCount < $repeatMin) {
            return null;
        }

        return [
            'trigger' => sprintf(
                'Review landed %s after the job or invite%s',
                $this->humanSeconds($seconds),
                $fastCount >= $repeatMin ? sprintf(' · %d fast reviews in %d hours', $fastCount, $repeatHours) : '',
            ),
            'seconds' => $seconds,
            'fast_count' => $fastCount,
            'threshold_seconds' => $maxSeconds,
        ];
    }

    private function humanSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' seconds';
        }

        $minutes = max(1, (int) round($seconds / 60));

        return $minutes === 1 ? '1 minute' : $minutes.' minutes';
    }
}
