<?php

namespace App\Support\Staff;

use App\Enums\StaffStatus;
use App\Models\ActivityLog;
use App\Models\LeaveRequest;
use App\Models\StaffIdleEvent;
use App\Models\User;
use Illuminate\Support\Carbon;

class StaffShiftAdherence
{
    public function __construct(private readonly StaffPresence $presence) {}

    /**
     * @return array<string, mixed>
     */
    public function forDate(User $user, Carbon|string|null $date = null): array
    {
        $schedule = StaffShift::for($user);
        $tz = $schedule['timezone'];
        $day = ($date ? Carbon::parse($date) : now())->timezone($tz)->startOfDay();
        $now = now()->timezone($tz);
        $isToday = $day->isSameDay($now);
        $window = StaffShift::windowForDate($user, $day);
        $onLeave = $this->onLeave($user, $day);

        $base = [
            'date' => $day->toDateString(),
            'date_label' => $isToday ? 'Today' : $day->format('j M Y'),
            'is_today' => $isToday,
            'scheduled' => $window !== null,
            'on_leave' => $onLeave,
            'shift' => [
                'days' => $schedule['days'],
                'days_label' => StaffShift::daysLabel($schedule['days']),
                'start' => $schedule['start'],
                'end' => $schedule['end'],
                'breaks' => $schedule['breaks'],
                'timezone' => $tz,
                'label' => $schedule['start'].'–'.$schedule['end'].' · '.StaffShift::daysLabel($schedule['days']),
            ],
        ];

        if ($onLeave) {
            return [
                ...$base,
                'summary' => [
                    'status' => 'leave',
                    'status_label' => 'On leave',
                    'signed_in_at' => '—',
                    'signed_out_at' => '—',
                    'late_minutes' => null,
                    'idle_total_seconds' => 0,
                    'idle_total_label' => '—',
                    'idle_events_count' => 0,
                    'adherence_pct' => null,
                ],
                'timeline' => [],
                'idle_events' => [],
                'sessions' => [],
            ];
        }

        if ($window === null) {
            return [
                ...$base,
                'summary' => [
                    'status' => 'off_day',
                    'status_label' => 'Off day',
                    'signed_in_at' => '—',
                    'signed_out_at' => '—',
                    'late_minutes' => null,
                    'idle_total_seconds' => 0,
                    'idle_total_label' => '—',
                    'idle_events_count' => 0,
                    'adherence_pct' => null,
                ],
                'timeline' => [],
                'idle_events' => [],
                'sessions' => [],
            ];
        }

        $sessions = $this->sessionsForDay($user, $day, $tz, $isToday);
        $idleEvents = $this->idleEventsForDay($user, $day);
        $summary = $this->buildSummary($user, $window, $sessions, $idleEvents, $isToday, $now, $tz);
        $timeline = $this->buildTimeline($window, $sessions, $idleEvents, $tz);

        return [
            ...$base,
            'summary' => $summary,
            'timeline' => $timeline,
            'idle_events' => $idleEvents,
            'sessions' => $sessions,
        ];
    }

    private function onLeave(User $user, Carbon $day): bool
    {
        if ($user->hrProfile?->isExited()) {
            return false;
        }

        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->exists();
    }

    /**
     * @return list<array{login_at: string, logout_at: string|null, login_label: string, logout_label: string}>
     */
    private function sessionsForDay(User $user, Carbon $day, string $tz, bool $isToday): array
    {
        $sessions = $this->sessionsFromActivityLogs($user, $day, $tz);

        if ($isToday && $sessions === []) {
            $sessions = $this->sessionsFromPresence($user, $tz);
        }

        return $sessions;
    }

    /**
     * @return list<array{login_at: string, logout_at: string|null, login_label: string, logout_label: string}>
     */
    private function sessionsFromActivityLogs(User $user, Carbon $day, string $tz): array
    {
        $logs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->whereIn('action', ['auth.admin_login', 'auth.admin_logout', 'auth.login'])
            ->whereBetween('created_at', [
                $day->copy()->timezone($tz)->startOfDay()->utc(),
                $day->copy()->timezone($tz)->endOfDay()->utc(),
            ])
            ->orderBy('created_at')
            ->get();

        $sessions = [];
        $openLogin = null;
        $isToday = $day->isSameDay(now()->timezone($tz));

        foreach ($logs as $log) {
            $at = $log->created_at->timezone($tz);
            $isLogin = in_array($log->action, ['auth.admin_login', 'auth.login'], true);

            if ($isLogin) {
                if ($openLogin) {
                    $sessions[] = $this->sessionRow($openLogin, null, $tz);
                }
                $openLogin = $at;
                continue;
            }

            if ($openLogin) {
                $sessions[] = $this->sessionRow($openLogin, $at, $tz);
                $openLogin = null;
            }
        }

        if ($openLogin) {
            $lastSeen = $this->presence->lastSeenAt($user);
            $logout = null;

            if ($user->last_logout_at?->timezone($tz)->isSameDay($day) && $user->last_logout_at->greaterThan($openLogin)) {
                $logout = $user->last_logout_at->timezone($tz);
            } elseif (! $isToday) {
                $logout = null;
            } elseif ($lastSeen && $lastSeen->timezone($tz)->isSameDay($day) && $lastSeen->greaterThan($openLogin)) {
                $logout = null;
            }

            $sessions[] = $this->sessionRow($openLogin, $logout, $tz);
        } elseif ($isToday && $user->last_login_at?->timezone($tz)->isSameDay($day)) {
            $login = $user->last_login_at->timezone($tz);
            $logout = null;
            if ($user->last_logout_at?->timezone($tz)->isSameDay($day) && $user->last_logout_at->greaterThan($login)) {
                $signedOutAfter = $user->last_logout_at->timezone($tz);
                $lastSeen = $this->presence->lastSeenAt($user);
                if (! $lastSeen || ! $lastSeen->greaterThan($signedOutAfter)) {
                    $logout = $signedOutAfter;
                }
            }
            $sessions[] = $this->sessionRow($login, $logout, $tz);
        }

        return $sessions;
    }

    /**
     * @return list<array{login_at: string, logout_at: string|null, login_label: string, logout_label: string}>
     */
    private function sessionsFromPresence(User $user, string $tz): array
    {
        $attendance = StaffShift::attendance($user, $this->presence);

        if (! $attendance['signed_in_today']) {
            return [];
        }

        $login = filled($attendance['logged_in_at'])
            ? Carbon::parse($attendance['logged_in_at'])->timezone($tz)
            : $this->presence->lastSeenAt($user)?->timezone($tz);

        if (! $login) {
            return [];
        }

        $logout = filled($attendance['logged_out_at'])
            ? Carbon::parse($attendance['logged_out_at'])->timezone($tz)
            : null;

        return [$this->sessionRow($login, $logout, $tz)];
    }

    /**
     * @return array{login_at: string, logout_at: string|null, login_label: string, logout_label: string}
     */
    private function sessionRow(Carbon $login, ?Carbon $logout, string $tz): array
    {
        return [
            'login_at' => $login->toIso8601String(),
            'logout_at' => $logout?->toIso8601String(),
            'login_label' => $login->format('H:i'),
            'logout_label' => $logout ? $logout->format('H:i') : 'Still signed in',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function idleEventsForDay(User $user, Carbon $day): array
    {
        return StaffIdleEvent::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $day->toDateString())
            ->orderBy('started_at')
            ->get()
            ->map(function (StaffIdleEvent $event) {
                $duration = $event->duration_seconds;
                if ($duration === null && $event->ended_at === null) {
                    $duration = max(0, now()->getTimestamp() - $event->started_at->getTimestamp());
                }

                return [
                    'id' => $event->id,
                    'start' => $event->started_at->format('H:i'),
                    'end' => $event->ended_at?->format('H:i'),
                    'start_at' => $event->started_at->toIso8601String(),
                    'end_at' => $event->ended_at?->toIso8601String(),
                    'duration_seconds' => $duration,
                    'duration_label' => $duration !== null ? $this->presence->humanDuration($duration) : '—',
                    'open' => $event->isOpen(),
                ];
            })
            ->all();
    }

    /**
     * @param  array{start: Carbon, end: Carbon, breaks: list<array{start: Carbon, end: Carbon, label: string}>, timezone: string}  $window
     * @param  list<array{login_at: string, logout_at: string|null, login_label: string, logout_label: string}>  $sessions
     * @param  list<array<string, mixed>>  $idleEvents
     * @return array<string, mixed>
     */
    private function buildSummary(
        User $user,
        array $window,
        array $sessions,
        array $idleEvents,
        bool $isToday,
        Carbon $now,
        string $tz,
    ): array {
        $shiftStart = $window['start'];
        $shiftEnd = $window['end'];
        $firstLogin = $this->firstLogin($sessions, $tz);
        $lastLogout = $this->lastLogout($sessions, $isToday);
        $lateMinutes = null;
        $status = 'missed';
        $statusLabel = 'Not signed in';

        if ($firstLogin) {
            $lateMinutes = $firstLogin->greaterThan($shiftStart->copy()->addMinutes(5))
                ? (int) $shiftStart->diffInMinutes($firstLogin)
                : 0;

            if ($lateMinutes > 0) {
                $status = 'late';
                $statusLabel = 'Late · '.$lateMinutes.'m';
            } else {
                $status = 'on_time';
                $statusLabel = 'On time';
            }

            if ($isToday && $now->lessThan($shiftEnd) && ! $lastLogout) {
                $status = $lateMinutes > 0 ? 'late' : 'in_progress';
                $statusLabel = $lateMinutes > 0 ? 'Late · in shift' : 'In shift';
            } elseif ($firstLogin && ! $lastLogout && $isToday) {
                $status = $lateMinutes > 0 ? 'late' : 'in_progress';
                $statusLabel = $lateMinutes > 0 ? 'Late · in shift' : 'In shift';
            } elseif ($firstLogin && $lastLogout) {
                $status = $lateMinutes > 0 ? 'late' : 'on_time';
                $statusLabel = $lateMinutes > 0 ? 'Late · '.$lateMinutes.'m' : 'Completed';
            }
        } elseif ($isToday && $now->lessThan($shiftStart)) {
            $status = 'pending';
            $statusLabel = 'Shift not started';
        } elseif ($isToday && $now->betweenIncluded($shiftStart, $shiftEnd)) {
            $signedInToday = StaffShift::attendance($user, $this->presence)['signed_in_today'];
            if (! $signedInToday) {
                $status = 'missed';
                $statusLabel = 'Not signed in';
            }
        }

        if ($user->staff_status !== StaffStatus::Active) {
            $status = 'inactive';
            $statusLabel = $user->staff_status?->label() ?? 'Inactive';
        }

        $idleTotal = collect($idleEvents)->sum(fn (array $row) => (int) ($row['duration_seconds'] ?? 0));
        $expectedMinutes = max(1, (int) $shiftStart->diffInMinutes($shiftEnd) - $this->breakMinutes($window['breaks']));
        $presentMinutes = $this->presentMinutes($sessions, $window, $idleTotal);
        $adherencePct = min(100, max(0, (int) round(($presentMinutes / $expectedMinutes) * 100)));

        if ($status === 'missed' || $status === 'pending') {
            $adherencePct = $status === 'pending' ? null : 0;
        }

        return [
            'status' => $status,
            'status_label' => $statusLabel,
            'signed_in_at' => $firstLogin ? $firstLogin->format('H:i') : '—',
            'signed_out_at' => $lastLogout
                ? $lastLogout->format('H:i')
                : ($firstLogin ? ($isToday ? 'Still signed in' : '—') : '—'),
            'late_minutes' => $lateMinutes,
            'idle_total_seconds' => $idleTotal,
            'idle_total_label' => $this->presence->humanDuration($idleTotal),
            'idle_events_count' => count($idleEvents),
            'adherence_pct' => $adherencePct,
        ];
    }

    /**
     * @param  list<array{login_at: string, logout_at: string|null}>  $sessions
     */
    private function firstLogin(array $sessions, \DateTimeZone|string $tz): ?Carbon
    {
        $first = collect($sessions)->sortBy('login_at')->first();

        return $first ? Carbon::parse($first['login_at'])->timezone($tz) : null;
    }

    /**
     * @param  list<array{login_at: string, logout_at: string|null}>  $sessions
     */
    private function lastLogout(array $sessions, bool $isToday): ?Carbon
    {
        $closed = collect($sessions)->filter(fn (array $row) => filled($row['logout_at']))->sortByDesc('logout_at')->first();

        if ($closed) {
            return Carbon::parse($closed['logout_at']);
        }

        if ($isToday) {
            return null;
        }

        return null;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon, label: string}>  $breaks
     */
    private function breakMinutes(array $breaks): int
    {
        return (int) collect($breaks)->sum(fn (array $break) => max(0, $break['start']->diffInMinutes($break['end'])));
    }

    /**
     * @param  list<array{login_at: string, logout_at: string|null}>  $sessions
     * @param  array{start: Carbon, end: Carbon, breaks: list<array{start: Carbon, end: Carbon, label: string}>}  $window
     */
    private function presentMinutes(array $sessions, array $window, int $idleSeconds): int
    {
        $shiftStart = $window['start'];
        $shiftEnd = $window['end'];
        $total = 0;

        foreach ($sessions as $session) {
            $login = Carbon::parse($session['login_at']);
            $logout = filled($session['logout_at']) ? Carbon::parse($session['logout_at']) : now();
            $start = $login->greaterThan($shiftStart) ? $login : $shiftStart;
            $end = $logout->lessThan($shiftEnd) ? $logout : $shiftEnd;

            if ($end->greaterThan($start)) {
                $total += (int) $start->diffInMinutes($end);
            }
        }

        foreach ($window['breaks'] as $break) {
            $total = max(0, $total - (int) $break['start']->diffInMinutes($break['end']));
        }

        $idleMinutes = (int) ceil($idleSeconds / 60);

        return max(0, $total - $idleMinutes);
    }

    /**
     * @param  array{start: Carbon, end: Carbon, breaks: list<array{start: Carbon, end: Carbon, label: string}>}  $window
     * @param  list<array{login_at: string, logout_at: string|null, login_label: string, logout_label: string}>  $sessions
     * @param  list<array<string, mixed>>  $idleEvents
     * @return list<array<string, mixed>>
     */
    private function buildTimeline(array $window, array $sessions, array $idleEvents, string $tz): array
    {
        $shiftStart = $window['start'];
        $shiftEnd = $window['end'];
        $totalMinutes = max(1, (int) $shiftStart->diffInMinutes($shiftEnd));
        $segments = [];

        foreach ($this->expectedSegments($window) as $segment) {
            $segments[] = $this->timelineSegment(
                'expected',
                $segment['start'],
                $segment['end'],
                $shiftStart,
                $totalMinutes,
                $segment['label'],
            );
        }

        foreach ($window['breaks'] as $break) {
            $segments[] = $this->timelineSegment(
                'break',
                $break['start'],
                $break['end'],
                $shiftStart,
                $totalMinutes,
                $break['label'],
            );
        }

        foreach ($sessions as $session) {
            $login = Carbon::parse($session['login_at'])->timezone($tz);
            $logout = filled($session['logout_at'])
                ? Carbon::parse($session['logout_at'])->timezone($tz)
                : min(now()->timezone($tz), $shiftEnd);

            $clippedStart = $login->greaterThan($shiftStart) ? $login : $shiftStart;
            $clippedEnd = $logout->lessThan($shiftEnd) ? $logout : $shiftEnd;

            if ($clippedEnd->greaterThan($clippedStart)) {
                $segments[] = $this->timelineSegment(
                    'present',
                    $clippedStart,
                    $clippedEnd,
                    $shiftStart,
                    $totalMinutes,
                    'Signed in',
                );
            }
        }

        foreach ($idleEvents as $event) {
            $start = Carbon::parse($event['start_at'])->timezone($tz);
            $end = filled($event['end_at'])
                ? Carbon::parse($event['end_at'])->timezone($tz)
                : min(now()->timezone($tz), $shiftEnd);

            if ($end->greaterThan($start)) {
                $segments[] = $this->timelineSegment(
                    'idle',
                    $start,
                    $end,
                    $shiftStart,
                    $totalMinutes,
                    'Idle · '.($event['duration_label'] ?? ''),
                );
            }
        }

        return collect($segments)
            ->sortBy(fn (array $row) => $row['start_minutes'])
            ->values()
            ->all();
    }

    /**
     * @param  array{start: Carbon, end: Carbon, breaks: list<array{start: Carbon, end: Carbon, label: string}>}  $window
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function expectedSegments(array $window): array
    {
        $segments = [];
        $cursor = $window['start']->copy();
        $breaks = collect($window['breaks'])->sortBy(fn (array $b) => $b['start']->timestamp)->values();

        foreach ($breaks as $break) {
            if ($break['start']->greaterThan($cursor)) {
                $segments[] = [
                    'start' => $cursor->copy(),
                    'end' => $break['start']->copy(),
                    'label' => 'Expected on floor',
                ];
            }
            $cursor = $break['end']->copy();
        }

        if ($cursor->lessThan($window['end'])) {
            $segments[] = [
                'start' => $cursor->copy(),
                'end' => $window['end']->copy(),
                'label' => 'Expected on floor',
            ];
        }

        return $segments;
    }

    private function timelineSegment(
        string $type,
        Carbon $start,
        Carbon $end,
        Carbon $shiftStart,
        int $totalMinutes,
        string $label,
    ): array {
        $startMinutes = max(0, (int) $shiftStart->diffInMinutes($start, false) * -1);
        $widthMinutes = max(1, (int) $start->diffInMinutes($end));

        return [
            'type' => $type,
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'start_minutes' => $startMinutes,
            'width_minutes' => $widthMinutes,
            'start_pct' => round(($startMinutes / $totalMinutes) * 100, 1),
            'width_pct' => round(($widthMinutes / $totalMinutes) * 100, 1),
            'label' => $label,
        ];
    }
}
