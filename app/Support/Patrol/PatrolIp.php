<?php

namespace App\Support\Patrol;

use App\Models\User;

class PatrolIp
{
    /**
     * @var list<string>
     */
    private const IGNORED = [
        '',
        '127.0.0.1',
        '::1',
        '0.0.0.0',
        'localhost',
        '::ffff:127.0.0.1',
    ];

    public static function normalize(?string $ip): ?string
    {
        $value = strtolower(trim((string) $ip));

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '::ffff:') && substr_count($value, '.') === 3) {
            $value = substr($value, 7);
        }

        return $value;
    }

    public static function isIgnored(?string $ip): bool
    {
        $value = self::normalize($ip);

        return $value === null || in_array($value, self::IGNORED, true);
    }

    public static function equals(?string $left, ?string $right): bool
    {
        $a = self::normalize($left);
        $b = self::normalize($right);

        if ($a === null || $b === null || self::isIgnored($a) || self::isIgnored($b)) {
            return false;
        }

        return $a === $b;
    }

    public static function mask(?string $ip): ?string
    {
        $value = self::normalize($ip);

        if ($value === null) {
            return null;
        }

        if (str_contains($value, ':')) {
            $parts = explode(':', $value);

            return ($parts[0] ?? 'x').':x';
        }

        $parts = explode('.', $value);

        return ($parts[0] ?? 'x').'.x';
    }

    public static function rememberLogin(?User $user, ?string $ip): void
    {
        if (! $user || self::isIgnored($ip)) {
            return;
        }

        $user->forceFill([
            'last_login_ip' => self::normalize($ip),
        ])->save();
    }
}
