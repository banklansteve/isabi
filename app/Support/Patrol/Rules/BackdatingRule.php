<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;

class BackdatingRule implements PatrolRule
{
    public function key(): string
    {
        return 'backdating';
    }

    public function evaluate(WorkLog $log): ?array
    {
        $config = config('patrol.rules.backdating');
        $allowedDays = max(0, (int) ($config['allowed_days'] ?? 2));
        $repeatMin = max(2, (int) ($config['repeat_min'] ?? 3));
        $repeatWindowDays = max(1, (int) ($config['repeat_window_days'] ?? 30));

        if (! $log->worked_on || ! $log->created_at) {
            return null;
        }

        $days = (int) $log->created_at->startOfDay()->diffInDays($log->worked_on->copy()->startOfDay());
        if ($days <= $allowedDays) {
            return null;
        }

        $from = $log->created_at->copy()->subDays($repeatWindowDays);
        $repeats = WorkLog::query()
            ->where('user_id', $log->user_id)
            ->where('created_at', '>=', $from)
            ->whereNotNull('worked_on')
            ->get(['id', 'worked_on', 'created_at'])
            ->filter(function (WorkLog $item) use ($allowedDays) {
                if (! $item->worked_on || ! $item->created_at) {
                    return false;
                }

                return $item->created_at->startOfDay()->diffInDays($item->worked_on->copy()->startOfDay()) > $allowedDays;
            })
            ->count();

        if ($repeats < 2 && $days <= ($allowedDays + 3)) {
            return null;
        }

        return [
            'trigger' => sprintf(
                'backdated %d days, exceeds %d-day threshold%s',
                $days,
                $allowedDays,
                $repeats >= $repeatMin ? sprintf(' · %d backdated jobs in %d days', $repeats, $repeatWindowDays) : '',
            ),
            'backdated_days' => $days,
            'allowed_days' => $allowedDays,
            'repeat_count' => $repeats,
            'repeat_window_days' => $repeatWindowDays,
        ];
    }
}
