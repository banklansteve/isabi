<?php

namespace App\Support;

class NumberFormat
{
    /**
     * Compact counts for tiles: 201, 1.5k, 5.1M.
     */
    public static function compact(int|float|null $value): string
    {
        $n = max(0, (int) $value);

        if ($n < 1000) {
            return (string) $n;
        }

        if ($n < 1_000_000) {
            $scaled = $n / 1000;

            return self::trimDecimal($scaled).'k';
        }

        if ($n < 1_000_000_000) {
            $scaled = $n / 1_000_000;

            return self::trimDecimal($scaled).'M';
        }

        $scaled = $n / 1_000_000_000;

        return self::trimDecimal($scaled).'B';
    }

    private static function trimDecimal(float $scaled): string
    {
        if ($scaled >= 100) {
            return (string) (int) round($scaled);
        }

        $rounded = round($scaled, 1);

        if (abs($rounded - (int) $rounded) < 0.05) {
            return (string) (int) $rounded;
        }

        return number_format($rounded, 1, '.', '');
    }

    /**
     * Whole-naira amounts for admin surfaces: ₦4.2M, ₦12,400, ₦0.
     */
    public static function naira(int|float|null $amount, bool $compact = true): string
    {
        $n = (int) $amount;
        $sign = $n < 0 ? '-' : '';
        $n = abs($n);

        if ($compact && $n >= 1_000_000) {
            return $sign.'₦'.self::trimDecimal($n / 1_000_000).'M';
        }

        if ($compact && $n >= 100_000) {
            return $sign.'₦'.self::trimDecimal($n / 1000).'k';
        }

        return $sign.'₦'.number_format($n);
    }

    public static function percentDelta(int|float $current, int|float $previous): ?array
    {
        if ((int) $previous === 0) {
            return $current > 0
                ? ['value' => 100, 'label' => 'New', 'tone' => 'up']
                : null;
        }

        $delta = (($current - $previous) / abs($previous)) * 100;
        $rounded = round($delta);

        return [
            'value' => $rounded,
            'label' => ($rounded > 0 ? '+' : '').$rounded.'%',
            'tone' => $rounded > 0 ? 'up' : ($rounded < 0 ? 'down' : 'neutral'),
        ];
    }
}
