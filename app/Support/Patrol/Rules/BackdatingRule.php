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
        $allowedDays = max(0, (int) ($config['allowed_days'] ?? 5));
        $repeatMin = max(2, (int) ($config['repeat_min'] ?? 3));
        $repeatWindowDays = max(1, (int) ($config['repeat_window_days'] ?? 30));

        if (! $log->worked_on || ! $log->created_at) {
            return null;
        }

        $workedDay = $log->worked_on->copy()->startOfDay();
        $createdDay = $log->created_at->copy()->startOfDay();

        // Only flag true backdating (job date before the day it was logged).
        if ($workedDay->gte($createdDay)) {
            return null;
        }

        $days = (int) $workedDay->diffInDays($createdDay);

        // Strict: any job dated more than allowed_days before it was logged is flagged.
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

                $workedDay = $item->worked_on->copy()->startOfDay();
                $createdDay = $item->created_at->copy()->startOfDay();

                if ($workedDay->gte($createdDay)) {
                    return false;
                }

                return (int) $workedDay->diffInDays($createdDay) > $allowedDays;
            })
            ->count();

        return [
            'trigger' => sprintf(
                'backdated %d days (limit %d)%s',
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
