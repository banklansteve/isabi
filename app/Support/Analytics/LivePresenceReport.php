<?php

namespace App\Support\Analytics;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Live presence counts from users.last_seen_at (updated by realtime pings).
 * Intentionally reads the live users table — not analytics summaries.
 */
class LivePresenceReport
{
    public const ONLINE_WITHIN_SECONDS = 120;

    /**
     * @return array{
     *     online_now: array{super_admins: int, operations: int, users: int, total: int},
     *     online_today: array{super_admins: int, operations: int, users: int, total: int},
     *     as_of: string,
     *     online_window_seconds: int,
     *     hint: string
     * }
     */
    public function snapshot(?Carbon $now = null): array
    {
        $now ??= now();
        $onlineSince = $now->copy()->subSeconds(self::ONLINE_WITHIN_SECONDS);
        $todayStart = $now->copy()->timezone(config('app.timezone'))->startOfDay();

        $onlineNow = $this->countsSince($onlineSince);
        $onlineToday = $this->countsSince($todayStart);

        return [
            'online_now' => $onlineNow,
            'online_today' => $onlineToday,
            'as_of' => $now->toIso8601String(),
            'online_window_seconds' => self::ONLINE_WITHIN_SECONDS,
            'hint' => 'Live presence from recent activity pings — not the same as daily engagement charts.',
        ];
    }

    /**
     * @return array{super_admins: int, operations: int, users: int, total: int}
     */
    private function countsSince(Carbon $since): array
    {
        $rows = User::query()
            ->where('last_seen_at', '>=', $since)
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $super = (int) ($rows[UserRole::SuperAdmin->value] ?? 0);
        $ops = (int) ($rows[UserRole::OperationsAdmin->value] ?? 0);
        $users = (int) ($rows[UserRole::User->value] ?? 0);

        return [
            'super_admins' => $super,
            'operations' => $ops,
            'users' => $users,
            'total' => $super + $ops + $users,
        ];
    }
}
