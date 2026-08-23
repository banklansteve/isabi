<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SessionLifetime
{
    public const MIN_MINUTES = 30;

    public const MAX_MINUTES = 525600;

    public const KEY_USERS = 'session.lifetime_users';

    public const KEY_OPERATIONS = 'session.lifetime_operations';

    public const KEY_SUPER_ADMIN = 'session.lifetime_super_admin';

    public const DEFAULT_USERS = 43200;

    public const DEFAULT_OPERATIONS = 20160;

    public const DEFAULT_SUPER_ADMIN = 20160;

    public const REMEMBER_FLOOR_MINUTES = 576000;

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::KEY_USERS,
            self::KEY_OPERATIONS,
            self::KEY_SUPER_ADMIN,
        ];
    }

    public function minutesFor(?User $user): int
    {
        if ($user?->isSuperAdmin()) {
            return $this->minutesForKey(self::KEY_SUPER_ADMIN);
        }

        if ($user?->isStaff()) {
            return $this->minutesForKey(self::KEY_OPERATIONS);
        }

        return $this->minutesForKey(self::KEY_USERS);
    }

    public function minutesForKey(string $key): int
    {
        $value = config($key);
        $fallback = match ($key) {
            self::KEY_OPERATIONS => self::DEFAULT_OPERATIONS,
            self::KEY_SUPER_ADMIN => self::DEFAULT_SUPER_ADMIN,
            default => self::DEFAULT_USERS,
        };

        $minutes = is_numeric($value) ? (int) $value : $fallback;

        return $this->clamp($minutes);
    }

    public function apply(?User $user): int
    {
        $minutes = $this->minutesFor($user);

        config(['session.lifetime' => $minutes]);
        $this->configureGuard($minutes);

        return $minutes;
    }

    public function configureGuard(int $minutes): void
    {
        $guard = Auth::guard('web');

        if (! method_exists($guard, 'setRememberDuration')) {
            return;
        }

        $guard->setRememberDuration(max($minutes, self::REMEMBER_FLOOR_MINUTES));
    }

    public function primeHandlerLifetime(): void
    {
        config([
            'session.lifetime' => max(
                $this->minutesForKey(self::KEY_USERS),
                $this->minutesForKey(self::KEY_OPERATIONS),
                $this->minutesForKey(self::KEY_SUPER_ADMIN),
            ),
        ]);
    }

    public function clamp(int $minutes): int
    {
        return max(self::MIN_MINUTES, min(self::MAX_MINUTES, $minutes));
    }
}
