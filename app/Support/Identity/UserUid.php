<?php

namespace App\Support\Identity;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserUid
{
    public const LENGTH = 10;

    public const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public const PREFIX_OPERATIONS = 'OP';

    public const PREFIX_SUPER_ADMIN = 'SA';

    public static function prefixFor(UserRole|string|null $role): string
    {
        $role = $role instanceof UserRole ? $role : UserRole::tryFrom((string) $role);

        return match ($role) {
            UserRole::SuperAdmin => self::PREFIX_SUPER_ADMIN,
            UserRole::OperationsAdmin => self::PREFIX_OPERATIONS,
            default => '',
        };
    }

    /**
     * @return list<string>
     */
    public static function reservedPrefixes(): array
    {
        return [self::PREFIX_OPERATIONS, self::PREFIX_SUPER_ADMIN];
    }

    public static function isStaffUid(?string $uid): bool
    {
        if (! is_string($uid) || $uid === '') {
            return false;
        }

        foreach (self::reservedPrefixes() as $prefix) {
            if (str_starts_with($uid, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function matches(?string $uid, UserRole|string|null $role): bool
    {
        if (! self::isValid($uid)) {
            return false;
        }

        $prefix = self::prefixFor($role);

        if ($prefix === '') {
            return ! self::isStaffUid($uid);
        }

        return str_starts_with($uid, $prefix);
    }

    public static function generate(string $prefix = ''): string
    {
        $prefix = strtoupper($prefix);
        $remaining = self::LENGTH - strlen($prefix);
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $uid = $prefix;

        for ($i = 0; $i < $remaining; $i++) {
            $uid .= $alphabet[random_int(0, $max)];
        }

        return $uid;
    }

    public static function unique(?int $ignoreUserId = null, string $prefix = ''): string
    {
        $prefix = strtoupper($prefix);

        for ($attempt = 0; $attempt < 32; $attempt++) {
            $uid = self::generate($prefix);

            if ($prefix === '' && self::isStaffUid($uid)) {
                continue;
            }

            try {
                $taken = DB::table('users')
                    ->where('uid', $uid)
                    ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
                    ->exists();
            } catch (QueryException) {
                return $uid;
            }

            if (! $taken) {
                return $uid;
            }
        }

        throw new RuntimeException('Could not allocate a unique user uid.');
    }

    public static function isValid(?string $uid): bool
    {
        return is_string($uid) && preg_match('/^[A-Z0-9]{'.self::LENGTH.'}$/', $uid) === 1;
    }

    public static function fill(User $user): void
    {
        if (self::matches($user->uid, $user->role)) {
            return;
        }

        $user->uid = self::unique($user->id, self::prefixFor($user->role));
    }
}
