<?php

namespace App\Support;

use App\Models\User;
use App\Support\Staff\AppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Soft maintenance mode — blocks artisans/guests without touching sessions or queues.
 */
class MaintenanceMode
{
    public const COOKIE = 'kraftrack_maint_bypass';

    public const KEY_ENABLED = 'maintenance.enabled';

    public const KEY_SECRET = 'maintenance.secret';

    public const KEY_ENABLED_AT = 'maintenance.enabled_at';

    public const KEY_ENABLED_BY = 'maintenance.enabled_by';

    /** Bypass cookie lifetime while maintenance stays on (minutes). */
    public const BYPASS_MINUTES = 60 * 24 * 7;

    public function __construct(
        private readonly AppSettingsService $settings,
    ) {}

    public function enabled(): bool
    {
        if (! Schema::hasTable('app_settings')) {
            return false;
        }

        try {
            return $this->settings->boolean(self::KEY_ENABLED, false);
        } catch (\Throwable) {
            return false;
        }
    }

    public function secret(): ?string
    {
        $secret = $this->settings->get(self::KEY_SECRET);

        return filled($secret) ? (string) $secret : null;
    }

    public function enabledAt(): ?string
    {
        $at = $this->settings->get(self::KEY_ENABLED_AT);

        return filled($at) ? (string) $at : null;
    }

    public function enabledByUserId(): ?int
    {
        $id = $this->settings->get(self::KEY_ENABLED_BY);

        return filled($id) ? (int) $id : null;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     enabled_at: string|null,
     *     enabled_by: array{id: int, name: string}|null,
     *     bypass_url: string|null,
     *     bypass_token: string|null
     * }
     */
    public function statusForAdmin(?User $viewer = null): array
    {
        $enabled = $this->enabled();
        $secret = $enabled ? $this->secret() : null;
        $byId = $this->enabledByUserId();
        $by = null;

        if ($byId) {
            $user = User::query()->find($byId);
            if ($user) {
                $by = [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                ];
            }
        }

        $showBypass = $enabled && $secret && $viewer?->isSuperAdmin();

        return [
            'enabled' => $enabled,
            'enabled_at' => $this->enabledAt(),
            'enabled_by' => $by,
            'bypass_url' => $showBypass ? route('maintenance.bypass', $secret) : null,
            'bypass_token' => $showBypass ? $secret : null,
        ];
    }

    public function enable(User $actor, ?string $note = null): string
    {
        $secret = $this->freshSecret();

        $this->settings->put(self::KEY_ENABLED, true, $actor, $this->definition(self::KEY_ENABLED));
        $this->settings->put(self::KEY_SECRET, $secret, $actor, $this->definition(self::KEY_SECRET));
        $this->settings->put(self::KEY_ENABLED_AT, now()->toIso8601String(), $actor, $this->definition(self::KEY_ENABLED_AT));
        $this->settings->put(self::KEY_ENABLED_BY, (string) $actor->id, $actor, $this->definition(self::KEY_ENABLED_BY));
        $this->settings->forgetCache();

        return $secret;
    }

    public function disable(User $actor): void
    {
        // Rotate secret first so existing bypass cookies stop matching.
        $this->settings->put(self::KEY_SECRET, $this->freshSecret(), $actor, $this->definition(self::KEY_SECRET));
        $this->settings->put(self::KEY_ENABLED, false, $actor, $this->definition(self::KEY_ENABLED));
        $this->settings->put(self::KEY_ENABLED_AT, '', $actor, $this->definition(self::KEY_ENABLED_AT));
        $this->settings->put(self::KEY_ENABLED_BY, '', $actor, $this->definition(self::KEY_ENABLED_BY));
        $this->settings->forgetCache();
    }

    /** Invalidate current bypass cookies; keep maintenance on. */
    public function regenerateSecret(User $actor): string
    {
        $secret = $this->freshSecret();
        $this->settings->put(self::KEY_SECRET, $secret, $actor, $this->definition(self::KEY_SECRET));
        $this->settings->forgetCache();

        return $secret;
    }

    public function tokenMatches(?string $token): bool
    {
        $secret = $this->secret();

        if (! $this->enabled() || ! filled($secret) || ! filled($token)) {
            return false;
        }

        return hash_equals($secret, (string) $token);
    }

    public function hasValidBypass(?Request $request = null): bool
    {
        $request ??= request();
        $cookie = $request->cookie(self::COOKIE);

        return $this->tokenMatches(is_string($cookie) ? $cookie : null);
    }

    public function makeBypassCookie(string $token): SymfonyCookie
    {
        return Cookie::make(
            name: self::COOKIE,
            value: $token,
            minutes: self::BYPASS_MINUTES,
            path: '/',
            domain: config('session.domain'),
            secure: (bool) config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: config('session.same_site') ?: 'lax',
        );
    }

    public function forgetBypassCookie(): SymfonyCookie
    {
        return Cookie::forget(self::COOKIE);
    }

    /**
     * Paths that must stay reachable during maintenance (staff auth + this feature).
     */
    public function pathIsAllowed(string $path): bool
    {
        $path = '/'.ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        if ($path === '/up') {
            return true;
        }

        if (str_starts_with($path, '/admin')) {
            return true;
        }

        if (str_starts_with($path, '/maintenance')) {
            return true;
        }

        return false;
    }

    private function freshSecret(): string
    {
        return Str::lower(Str::random(48));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function definition(string $key): ?array
    {
        return collect(config('admin.settings', []))->firstWhere('key', $key);
    }
}
