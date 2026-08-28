<?php

namespace App\Support\Staff;

use App\Enums\StaffStatus;
use App\Models\LeaveRequest;
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
        Cache::put($this->key($user->id), $now->timestamp, now()->addMinutes(20));

        $throttleKey = 'staff.seen.write.'.$user->id;
        $seconds = max(15, (int) config('admin.ops_insights.presence_write_seconds', 30));

        if (Cache::has($throttleKey)) {
            return;
        }

        Cache::put($throttleKey, 1, $seconds);

        $user->forceFill(['last_seen_at' => $now])->saveQuietly();
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
        if (! $onDuty || $onLeave) {
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

    private function key(int $id): string
    {
        return 'staff.last_seen.'.$id;
    }
}
