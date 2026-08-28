<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;

class RapidLoggingRule implements PatrolRule
{
    public function key(): string
    {
        return 'rapid_logging';
    }

    public function evaluate(WorkLog $log): ?array
    {
        $config = config('patrol.rules.rapid_logging');
        $minLogs = max(2, (int) ($config['min_logs'] ?? 5));
        $windowMinutes = max(1, (int) ($config['window_minutes'] ?? 15));
        $maxAgeHours = max(1, (int) ($config['max_age_hours'] ?? 48));
        $minDistinctSeconds = max(2, (int) ($config['min_distinct_seconds'] ?? 3));
        $createdAt = $log->created_at ?? now();
        $cutoff = now()->subHours($maxAgeHours);

        if ($createdAt->lt($cutoff)) {
            return null;
        }

        $from = $createdAt->copy()->subMinutes($windowMinutes);
        $to = $createdAt->copy()->addMinutes($windowMinutes);

        $cluster = WorkLog::query()
            ->where('user_id', $log->user_id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['id', 'created_at']);

        if ($cluster->count() < $minLogs) {
            return null;
        }

        $newest = $cluster->last()?->created_at;
        if (! $newest || $newest->lt($cutoff)) {
            return null;
        }

        $distinctSeconds = $cluster
            ->map(fn (WorkLog $item) => $item->created_at?->format('Y-m-d H:i:s'))
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
                '%d jobs logged within %d %s',
                $cluster->count(),
                $spanMinutes,
                $spanMinutes === 1 ? 'minute' : 'minutes',
            ),
            'count' => $cluster->count(),
            'window_minutes' => $windowMinutes,
            'span_minutes' => $spanMinutes,
            'distinct_seconds' => $distinctSeconds,
            'threshold' => $minLogs,
            'work_log_ids' => $cluster->pluck('id')->all(),
        ];
    }
}
