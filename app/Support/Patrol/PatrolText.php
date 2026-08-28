<?php

namespace App\Support\Patrol;

class PatrolText
{
    public static function normalizeDescription(?string $value): string
    {
        $text = mb_strtolower(trim((string) $value));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    public static function normalizeWhatsapp(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '234'.substr($digits, 1);
        }

        if (str_starts_with($digits, '234') && strlen($digits) > 10) {
            return $digits;
        }

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    public static function similarEnough(string $left, string $right, int $percent): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        similar_text($left, $right, $score);

        return $score >= $percent;
    }
}
