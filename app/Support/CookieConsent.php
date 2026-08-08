<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class CookieConsent
{
    public const COOKIE = 'isabi_consent';

    public const VERSION = 1;

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @return array{decided: bool, status: string|null, version: int|null, allows_analytics: bool}
     */
    public static function state(?Request $request = null): array
    {
        $request ??= request();
        $raw = $request->cookie(self::COOKIE);

        if (! is_string($raw) || $raw === '') {
            return [
                'decided' => false,
                'status' => null,
                'version' => null,
                'allows_analytics' => false,
            ];
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! in_array($payload['status'] ?? null, [self::STATUS_ACCEPTED, self::STATUS_REJECTED], true)) {
            return [
                'decided' => false,
                'status' => null,
                'version' => null,
                'allows_analytics' => false,
            ];
        }

        $status = $payload['status'];

        return [
            'decided' => true,
            'status' => $status,
            'version' => (int) ($payload['v'] ?? 0),
            'allows_analytics' => $status === self::STATUS_ACCEPTED,
        ];
    }

    public static function makeCookie(string $status): SymfonyCookie
    {
        $payload = json_encode([
            'status' => $status,
            'v' => self::VERSION,
            'at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        return Cookie::make(
            name: self::COOKIE,
            value: $payload,
            minutes: 60 * 24 * 365,
            path: '/',
            domain: config('session.domain'),
            secure: (bool) config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: config('session.same_site') ?: 'lax',
        );
    }
}
