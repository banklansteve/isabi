<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;

class ReviewRequestChainingRule implements PatrolRule
{
    public function key(): string
    {
        return 'review_request_chaining';
    }

    public function evaluate(WorkLog $log): ?array
    {
        if (! $log->review_requested_at || ! $log->created_at) {
            return null;
        }

        $config = config('patrol.rules.review_request_chaining');
        $maxGap = max(1, (int) ($config['max_gap_minutes'] ?? 5));
        $minChain = max(2, (int) ($config['min_chain'] ?? 4));
        $windowHours = max(1, (int) ($config['window_hours'] ?? 6));
        $thisGap = (int) ceil($log->created_at->diffInSeconds($log->review_requested_at) / 60);

        if ($thisGap > $maxGap) {
            return null;
        }

        $from = ($log->review_requested_at ?? $log->created_at)->copy()->subHours($windowHours);
        $to = ($log->review_requested_at ?? $log->created_at)->copy()->addHours($windowHours);

        $chain = WorkLog::query()
            ->where('user_id', $log->user_id)
            ->whereNotNull('review_requested_at')
            ->whereBetween('review_requested_at', [$from, $to])
            ->get(['id', 'created_at', 'review_requested_at'])
            ->filter(function (WorkLog $item) use ($maxGap) {
                if (! $item->created_at || ! $item->review_requested_at) {
                    return false;
                }

                $gap = (int) ceil($item->created_at->diffInSeconds($item->review_requested_at) / 60);

                return $gap <= $maxGap;
            });

        if ($chain->count() < $minChain) {
            return null;
        }

        return [
            'trigger' => sprintf(
                'review invite sent %d minutes after logging, repeated %d times in %d hours',
                max(0, $thisGap),
                $chain->count(),
                $windowHours,
            ),
            'gap_minutes' => max(0, $thisGap),
            'max_gap_minutes' => $maxGap,
            'chain_count' => $chain->count(),
            'window_hours' => $windowHours,
            'work_log_ids' => $chain->pluck('id')->all(),
        ];
    }
}
