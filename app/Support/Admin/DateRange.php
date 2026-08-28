<?php

namespace App\Support\Admin;

use Illuminate\Support\Carbon;

class DateRange
{
    /**
     * @return list<string>
     */
    public static function presetIds(): array
    {
        return [
            'today',
            'yesterday',
            'this_week',
            'last_3',
            'last_7',
            'last_30',
            'this_month',
            'last_month',
            'custom',
            'all',
        ];
    }

    /**
     * @return array{from: Carbon|null, to: Carbon|null, label: string}
     */
    public static function bounds(string $id, ?string $from = null, ?string $to = null, ?Carbon $now = null): array
    {
        $tz = (string) config('app.display_timezone', 'Africa/Lagos');
        $now = ($now ?? now())->copy()->timezone($tz);
        $id = in_array($id, self::presetIds(), true) ? $id : 'this_week';

        if ($id === 'all') {
            return ['from' => null, 'to' => null, 'label' => 'All time'];
        }

        if ($id === 'custom') {
            $start = $from ? Carbon::parse($from, $tz)->startOfDay() : $now->copy()->startOfDay();
            $end = $to ? Carbon::parse($to, $tz)->endOfDay() : $now->copy()->endOfDay();

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                'from' => $start,
                'to' => $end,
                'label' => $start->toFormattedDateString().' – '.$end->toFormattedDateString(),
            ];
        }

        [$start, $end] = match ($id) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy()->endOfDay(),
            ],
            'last_3' => [
                $now->copy()->subDays(2)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'last_7' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'last_30' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfDay(),
            ],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay(),
            ],
            default => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfDay()],
        };

        return [
            'from' => $start,
            'to' => $end,
            'label' => self::label($id),
        ];
    }

    public static function label(string $id): string
    {
        return match ($id) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This week',
            'last_3' => 'Last 3 days',
            'last_7' => 'Last 7 days',
            'last_30' => 'Last 30 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'custom' => 'Custom',
            'all' => 'All time',
            default => 'This week',
        };
    }

    /**
     * Convert display-timezone bounds to UTC instants for querying stored timestamps.
     *
     * @param  array{from: Carbon|null, to: Carbon|null}  $bounds
     * @return array{from: Carbon|null, to: Carbon|null}
     */
    public static function utc(array $bounds): array
    {
        return [
            'from' => $bounds['from']?->copy()->utc(),
            'to' => $bounds['to']?->copy()->utc(),
        ];
    }
}
