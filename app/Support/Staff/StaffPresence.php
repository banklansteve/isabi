<?php

namespace App\Support\Staff;

use App\Enums\StaffStatus;
use App\Models\LeaveRequest;
use App\Models\StaffIdleEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StaffPresence
{
    public function touch(User $user): void
    {
        if (! $user->isStaff()) {
            return;
        }

        $now = now();
        $previous = $this->lastSeenAt($user);

        if ($previous && $this->shouldTrackIdle($user, $previous)) {
            $gap = max(0, $now->getTimestamp() - $previous->getTimestamp());

            if ($gap >= $this->idleAfterSeconds()) {
                $idleStart = $previous->copy()->addSeconds($this->idleAfterSeconds());
                $this->recordIdleGap($user, $idleStart, $now);
            }
        }

        $this->closeOpenIdle($user, $now);

        Cache::put($this->key($user->id), $now->timestamp, now()->addMinutes(20));

        $throttleKey = 'staff.seen.write.'.$user->id;
        $seconds = max(15, (int) config('admin.ops_insights.presence_write_seconds', 30));

        if (Cache::has($throttleKey)) {
            return;
        }

        Cache::put($throttleKey, 1, $seconds);

        $user->forceFill(['last_seen_at' => $now])->saveQuietly();
    }

    public function reconcileIdle(User $user): void
    {
        if (! $user->isStaff() || $user->staff_status !== StaffStatus::Active || $user->isSuspended()) {
            return;
        }

        if (! StaffShift::onDuty($user) || StaffShift::onBreak($user) || $this->onLeave($user)) {
            $this->closeOpenIdle($user, now());

            return;
        }

        $lastSeen = $this->lastSeenAt($user);
        $idle = $this->idleSeconds($user, $lastSeen);

        if ($idle === null || $idle < $this->idleAfterSeconds()) {
            return;
        }

        $started = $lastSeen->copy()->addSeconds($this->idleAfterSeconds());
        $open = $this->openIdleEvent($user);

        if ($open) {
            return;
        }

        StaffIdleEvent::query()->create([
            'user_id' => $user->id,
            'work_date' => $started->toDateString(),
            'started_at' => $started,
            'ended_at' => null,
            'duration_seconds' => null,
        ]);
    }

    /**
     * @param  Collection<int, User>|list<User>  $staff
     */
    public function reconcileIdleForStaff(Collection|array $staff): void
    {
        foreach ($staff as $user) {
            $this->reconcileIdle($user);
        }
    }

    public function lastSeenAt(User $user): ?Carbon
    {
        $cached = Cache::get($this->key($user->id));

        if (is_numeric($cached)) {
            return Carbon::createFromTimestamp((int) $cached);
        }

        return $user->last_seen_at;
    }

    /**
     * @param  Collection<int, User>|list<User>  $staff
     * @return array<int, Carbon|null>
     */
    public function lastSeenMap(Collection|array $staff): array
    {
        $map = [];

        foreach ($staff as $user) {
            $map[$user->id] = $this->lastSeenAt($user);
        }

        return $map;
    }

    public function idleSeconds(User $user, ?Carbon $lastSeen = null): ?int
    {
        $lastSeen ??= $this->lastSeenAt($user);

        if (! $lastSeen) {
            return null;
        }

        return max(0, now()->getTimestamp() - $lastSeen->getTimestamp());
    }

    public function isAway(User $user, ?Carbon $lastSeen = null, bool $onDuty = false, bool $onLeave = false): bool
    {
        if (! $onDuty || $onLeave || StaffShift::onBreak($user)) {
            return false;
        }

        $idle = $this->idleSeconds($user, $lastSeen);

        if ($idle === null) {
            return true;
        }

        return $idle >= $this->idleAfterSeconds();
    }

    public function onDuty(?User $user = null, ?Carbon $at = null): bool
    {
        if ($user) {
            return StaffShift::onDuty($user, $at);
        }

        return StaffShift::withinDefaultHours($at);
    }

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function onLeaveIds(array $userIds, ?Carbon $date = null): array
    {
        if ($userIds === []) {
            return [];
        }

        $day = ($date ?? now())->toDateString();

        return LeaveRequest::query()
            ->whereIn('user_id', $userIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function idleAfterSeconds(): int
    {
        $minutes = (int) config(
            'admin.ops_insights.idle_after_minutes',
            config('support.idle_after_minutes', 7),
        );

        return max(60, $minutes * 60);
    }

    /**
     * @return array{
     *     status: string,
     *     label: string,
     *     last_seen_at: string|null,
     *     idle_seconds: int|null,
     *     away_seconds: int|null,
     *     away_label: string|null,
     *     on_duty: bool,
     *     on_leave: bool
     * }
     */
    public function snapshot(User $user, ?Carbon $lastSeen = null, bool $onLeave = false): array
    {
        $onDuty = $this->onDuty($user)
            && $user->staff_status === StaffStatus::Active
            && ! $user->isSuspended();
        $lastSeen ??= $this->lastSeenAt($user);
        $idle = $this->idleSeconds($user, $lastSeen);
        $threshold = $this->idleAfterSeconds();
        $away = $this->isAway($user, $lastSeen, $onDuty, $onLeave);
        $awaySeconds = $away && $idle !== null ? max(0, $idle - $threshold) : ($away ? null : 0);

        $status = match (true) {
            $onLeave => 'leave',
            ! $onDuty => 'off_duty',
            StaffShift::onBreak($user) => 'break',
            $lastSeen === null => 'unknown',
            $away => 'away',
            $idle !== null && $idle < 90 => 'active',
            default => 'idle',
        };

        return [
            'status' => $status,
            'label' => match ($status) {
                'active' => 'Active',
                'idle' => 'Idle',
                'away' => 'Away',
                'break' => 'On break',
                'leave' => 'On leave',
                'off_duty' => 'Off duty',
                default => 'Unknown',
            },
            'last_seen_at' => $lastSeen?->toIso8601String(),
            'idle_seconds' => $idle,
            'away_seconds' => $away ? ($idle ?? 0) : null,
            'away_label' => $away ? $this->humanDuration($idle ?? 0) : null,
            'on_duty' => $onDuty && ! $onLeave,
            'on_leave' => $onLeave,
        ];
    }

    public function humanDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest > 0 ? $hours.'h '.$rest.'m' : $hours.'h';
    }

    private function shouldTrackIdle(User $user, Carbon $at): bool
    {
        if ($user->staff_status !== StaffStatus::Active || $user->isSuspended()) {
            return false;
        }

        if ($this->onLeave($user)) {
            return false;
        }

        if (! StaffShift::onDuty($user, $at) && ! StaffShift::onDuty($user, now())) {
            return false;
        }

        return ! StaffShift::onBreak($user, $at) && ! StaffShift::onBreak($user, now());
    }

    private function onLeave(User $user): bool
    {
        return $user->isOnLeaveOn();
    }

    private function recordIdleGap(User $user, Carbon $startedAt, Carbon $endedAt): void
    {
        $open = $this->openIdleEvent($user);

        if ($open) {
            $open->close($endedAt);

            return;
        }

        if ($endedAt->lessThanOrEqualTo($startedAt)) {
            return;
        }

        StaffIdleEvent::query()->create([
            'user_id' => $user->id,
            'work_date' => $startedAt->toDateString(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => max(0, $endedAt->getTimestamp() - $startedAt->getTimestamp()),
        ]);
    }

    private function closeOpenIdle(User $user, Carbon $endedAt): void
    {
        $open = $this->openIdleEvent($user);

        if ($open) {
            $open->close($endedAt);
        }
    }

    private function openIdleEvent(User $user): ?StaffIdleEvent
    {
        return StaffIdleEvent::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->latest('id')
            ->first();
    }

    private function key(int $id): string
    {
        return 'staff.last_seen.'.$id;
    }
}
