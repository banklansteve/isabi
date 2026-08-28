<?php

namespace App\Support\Patrol;

class PatrolSeverity
{
    public static function rank(string $severity): int
    {
        return (int) (config('patrol.severity_rank.'.$severity) ?? 0);
    }

    /**
     * @param  iterable<string>  $severities
     */
    public static function max(iterable $severities): string
    {
        $winner = 'low';

        foreach ($severities as $severity) {
            if (self::rank((string) $severity) > self::rank($winner)) {
                $winner = (string) $severity;
            }
        }

        return $winner;
    }

    public static function isHigh(string $severity): bool
    {
        return $severity === 'high';
    }

    public static function isLow(string $severity): bool
    {
        return $severity === 'low';
    }
}
