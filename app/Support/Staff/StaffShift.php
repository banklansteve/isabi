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
     * @return array{days: list<int>, start: string, end: string, timezone: string, custom: bool}
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
        $custom = $days !== [] || filled($user->shift_starts_at) || filled($user->shift_ends_at);

        return [
            'days' => $days !== [] ? $days : $defaults['days'],
            'start' => filled($user->shift_starts_at)
                ? self::normalizeTime((string) $user->shift_starts_at)
                : $defaults['start'],
            'end' => filled($user->shift_ends_at)
                ? self::normalizeTime((string) $user->shift_ends_at)
                : $defaults['end'],
            'timezone' => $defaults['timezone'],
            'custom' => $custom,
        ];
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
        $signedInToday = $login && $login->isSameDay($now);
        $signedOutAfterLogin = $logout && $login && $logout->greaterThan($login);
        $late = $signedInToday && $login->greaterThan($expectedStart->copy()->addMinutes(5));
        $presence ??= app(StaffPresence::class);
        $idle = $presence->idleSeconds($user);
        $idleLabel = $idle === null ? '—' : $presence->humanDuration($idle);

        return [
            'days' => $schedule['days'],
            'days_label' => self::daysLabel($schedule['days']),
            'start' => $schedule['start'],
            'end' => $schedule['end'],
            'timezone' => $tz,
            'custom' => $schedule['custom'],
            'on_duty_today' => $onDutyToday,
            'on_duty_now' => self::onDuty($user, $now),
            'expected_in' => $schedule['start'],
            'expected_out' => $schedule['end'],
            'logged_in' => $signedInToday ? $login->format('H:i') : ($login ? $login->format('j M · H:i') : '—'),
            'logged_in_at' => $login?->toIso8601String(),
            'logged_out' => $signedOutAfterLogin
                ? ($logout->isSameDay($now) ? $logout->format('H:i') : $logout->format('j M · H:i'))
                : '—',
            'logged_out_at' => $signedOutAfterLogin ? $logout?->toIso8601String() : null,
            'signed_in_today' => $signedInToday,
            'late' => $late,
            'missed' => $onDutyToday && ! $signedInToday && $now->greaterThan($expectedStart),
            'idle_seconds' => $idle,
            'idle_label' => $idleLabel,
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
