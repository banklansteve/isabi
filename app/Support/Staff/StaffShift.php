<?php

namespace App\Support\Staff;

use App\Models\User;
use Illuminate\Support\Carbon;

class StaffShift
{
    /**
     * @return list<array{id: int, label: string, short: string}>
     */
    public static function weekdays(): array
    {
        return [
            ['id' => 1, 'label' => 'Monday', 'short' => 'Mon'],
            ['id' => 2, 'label' => 'Tuesday', 'short' => 'Tue'],
            ['id' => 3, 'label' => 'Wednesday', 'short' => 'Wed'],
            ['id' => 4, 'label' => 'Thursday', 'short' => 'Thu'],
            ['id' => 5, 'label' => 'Friday', 'short' => 'Fri'],
            ['id' => 6, 'label' => 'Saturday', 'short' => 'Sat'],
            ['id' => 7, 'label' => 'Sunday', 'short' => 'Sun'],
        ];
    }

    /**
     * @return array{days: list<int>, start: string, end: string, timezone: string}
     */
    public static function defaults(): array
    {
        $days = collect(config('support.hours.days', [1, 2, 3, 4, 5, 6]))
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->values()
            ->all();

        return [
            'days' => $days !== [] ? $days : [1, 2, 3, 4, 5, 6],
            'start' => self::normalizeTime((string) config('support.hours.start', '08:00')),
            'end' => self::normalizeTime((string) config('support.hours.end', '18:00')),
            'timezone' => (string) config('support.hours.timezone', 'Africa/Lagos'),
        ];
    }

    /**
     * @return array{days: list<int>, start: string, end: string, breaks: list<array{start: string, end: string, label: string}>, timezone: string, custom: bool}
     */
    public static function for(User $user): array
    {
        $defaults = self::defaults();
        $days = collect($user->shift_days ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $start = filled($user->shift_starts_at)
            ? self::normalizeTime((string) $user->shift_starts_at)
            : $defaults['start'];
        $end = filled($user->shift_ends_at)
            ? self::normalizeTime((string) $user->shift_ends_at)
            : $defaults['end'];
        $custom = $days !== [] || filled($user->shift_starts_at) || filled($user->shift_ends_at) || filled($user->shift_breaks);

        return [
            'days' => $days !== [] ? $days : $defaults['days'],
            'start' => $start,
            'end' => $end,
            'breaks' => self::normalizeBreaks($user->shift_breaks ?? [], $start, $end),
            'timezone' => $defaults['timezone'],
            'custom' => $custom,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, breaks: list<array{start: Carbon, end: Carbon, label: string}>, timezone: string}|null
     */
    public static function windowForDate(User $user, Carbon $date): ?array
    {
        $schedule = self::for($user);
        $tz = $schedule['timezone'];
        $at = $date->copy()->timezone($tz);

        if (! in_array((int) $at->dayOfWeekIso, $schedule['days'], true)) {
            return null;
        }

        $start = Carbon::parse($at->toDateString().' '.$schedule['start'], $tz);
        $end = Carbon::parse($at->toDateString().' '.$schedule['end'], $tz);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $breaks = collect($schedule['breaks'])
            ->map(function (array $break) use ($at, $tz) {
                return [
                    'start' => Carbon::parse($at->toDateString().' '.$break['start'], $tz),
                    'end' => Carbon::parse($at->toDateString().' '.$break['end'], $tz),
                    'label' => $break['label'],
                ];
            })
            ->all();

        return [
            'start' => $start,
            'end' => $end,
            'breaks' => $breaks,
            'timezone' => $tz,
        ];
    }

    public static function onBreak(User $user, ?Carbon $at = null): bool
    {
        $schedule = self::for($user);
        $at = ($at ?? now())->timezone($schedule['timezone']);

        if (! in_array((int) $at->dayOfWeekIso, $schedule['days'], true)) {
            return false;
        }

        foreach ($schedule['breaks'] as $break) {
            $start = Carbon::parse($at->toDateString().' '.$break['start'], $schedule['timezone']);
            $end = Carbon::parse($at->toDateString().' '.$break['end'], $schedule['timezone']);

            if ($at->betweenIncluded($start, $end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>|null  $breaks
     * @return list<array{start: string, end: string, label: string}>
     */
    public static function normalizeBreaks(?array $breaks, string $shiftStart, string $shiftEnd): array
    {
        return collect($breaks ?? [])
            ->map(function (array $break) {
                return [
                    'start' => self::normalizeTime((string) ($break['start'] ?? '')),
                    'end' => self::normalizeTime((string) ($break['end'] ?? '')),
                    'label' => filled($break['label'] ?? null) ? trim((string) $break['label']) : 'Break',
                ];
            })
            ->filter(fn (array $break) => $break['start'] !== $break['end'])
            ->sortBy('start')
            ->values()
            ->all();
    }

    public static function onDuty(User $user, ?Carbon $at = null): bool
    {
        $schedule = self::for($user);
        $at = ($at ?? now())->timezone($schedule['timezone']);

        if (! in_array((int) $at->dayOfWeekIso, $schedule['days'], true)) {
            return false;
        }

        $start = Carbon::parse($at->toDateString().' '.$schedule['start'], $schedule['timezone']);
        $end = Carbon::parse($at->toDateString().' '.$schedule['end'], $schedule['timezone']);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();

            if ($at->lt($start)) {
                $start->subDay();
                $end->subDay();
            }
        }

        return $at->betweenIncluded($start, $end);
    }

    public static function withinDefaultHours(?Carbon $at = null): bool
    {
        $defaults = self::defaults();
        $at = ($at ?? now())->timezone($defaults['timezone']);

        if (! in_array((int) $at->dayOfWeekIso, $defaults['days'], true)) {
            return false;
        }

        $start = Carbon::parse($at->toDateString().' '.$defaults['start'], $defaults['timezone']);
        $end = Carbon::parse($at->toDateString().' '.$defaults['end'], $defaults['timezone']);

        return $at->betweenIncluded($start, $end);
    }

    /**
     * @return array<string, mixed>
     */
    public static function attendance(User $user, ?StaffPresence $presence = null): array
    {
        $schedule = self::for($user);
        $tz = $schedule['timezone'];
        $now = now()->timezone($tz);
        $onDutyToday = in_array((int) $now->dayOfWeekIso, $schedule['days'], true);
        $expectedStart = Carbon::parse($now->toDateString().' '.$schedule['start'], $tz);
        $login = $user->last_login_at?->timezone($tz);
        $logout = $user->last_logout_at?->timezone($tz);
        $presence ??= app(StaffPresence::class);
        $lastSeen = $presence->lastSeenAt($user);
        $activeToday = $lastSeen && $lastSeen->timezone($tz)->isSameDay($now);
        $loggedInToday = $login && $login->isSameDay($now);
        $signedOutAfterLogin = $logout && $login && $logout->greaterThan($login);
        $sessionStillOpen = $activeToday && (
            ! $signedOutAfterLogin
            || ($lastSeen && $logout && $lastSeen->greaterThan($logout))
        );
        $signedInToday = $loggedInToday || $sessionStillOpen;
        $late = $loggedInToday && $login->greaterThan($expectedStart->copy()->addMinutes(5));
        $idle = $presence->idleSeconds($user, $lastSeen);
        $idleLabel = $idle === null ? '—' : $presence->humanDuration($idle);
        $onShiftNow = self::onDuty($user, $now) && $sessionStillOpen && $idle !== null && $idle < $presence->idleAfterSeconds();
        $presenceSnapshot = $presence->snapshot($user, $lastSeen);

        $loggedInLabel = match (true) {
            $onShiftNow => 'Active now · '.$lastSeen?->timezone($tz)->format('H:i'),
            $sessionStillOpen && $activeToday && ! $loggedInToday => 'Active today · '.$lastSeen?->timezone($tz)->format('H:i'),
            $loggedInToday => $login->format('H:i'),
            $login => $login->format('j M · H:i'),
            default => '—',
        };

        return [
            'days' => $schedule['days'],
            'days_label' => self::daysLabel($schedule['days']),
            'start' => $schedule['start'],
            'end' => $schedule['end'],
            'timezone' => $tz,
            'custom' => $schedule['custom'],
            'on_duty_today' => $onDutyToday,
            'on_duty_now' => self::onDuty($user, $now),
            'on_shift_now' => $onShiftNow,
            'expected_in' => $schedule['start'],
            'expected_out' => $schedule['end'],
            'logged_in' => $loggedInLabel,
            'logged_in_at' => ($loggedInToday ? $login : $lastSeen)?->toIso8601String(),
            'last_activity' => $lastSeen
                ? ($lastSeen->timezone($tz)->isSameDay($now)
                    ? $lastSeen->timezone($tz)->format('H:i')
                    : $lastSeen->timezone($tz)->format('j M · H:i'))
                : '—',
            'last_activity_at' => $lastSeen?->toIso8601String(),
            'logged_out' => $signedOutAfterLogin && ! $sessionStillOpen
                ? ($logout->isSameDay($now) ? $logout->format('H:i') : $logout->format('j M · H:i'))
                : ($sessionStillOpen ? 'Still signed in' : '—'),
            'logged_out_at' => $signedOutAfterLogin && ! $sessionStillOpen ? $logout?->toIso8601String() : null,
            'signed_in_today' => $signedInToday,
            'late' => $late,
            'missed' => $onDutyToday && ! $signedInToday && $now->greaterThan($expectedStart),
            'idle_seconds' => $idle,
            'idle_label' => $idleLabel,
            'presence_status' => $presenceSnapshot['status'],
            'presence_label' => $presenceSnapshot['label'],
            'shift_label' => $schedule['start'].'–'.$schedule['end'].' · '.self::daysLabel($schedule['days']),
        ];
    }

    /**
     * @param  list<int>  $days
     */
    public static function daysLabel(array $days): string
    {
        $map = collect(self::weekdays())->keyBy('id');
        $shorts = collect($days)
            ->map(fn (int $day) => $map[$day]['short'] ?? null)
            ->filter()
            ->values();

        if ($shorts->isEmpty()) {
            return 'No days';
        }

        if ($shorts->count() === 7) {
            return 'Every day';
        }

        return $shorts->implode(', ');
    }

    public static function normalizeTime(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $match)) {
            return sprintf('%02d:%02d', min(23, (int) $match[1]), min(59, (int) $match[2]));
        }

        return '08:00';
    }
}
