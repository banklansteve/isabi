<?php

namespace App\Support\SupportChat;

use App\Models\User;
use App\Support\Staff\StaffShift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SupportPresence
{
    public function heartbeat(User $user): void
    {
        if (! $user->isStaff()) {
            Cache::put($this->customerKey($user->id), now()->timestamp, $this->ttl());

            return;
        }

        Cache::put($this->staffKey($user->id), now()->timestamp, $this->ttl());
    }

    public function staffOnline(User $user): bool
    {
        if (! Cache::has($this->staffKey($user->id))) {
            return false;
        }

        if (! StaffShift::onDuty($user)) {
            return true;
        }

        $staff = app(\App\Support\Staff\StaffPresence::class);
        $idle = $staff->idleSeconds($user);

        return $idle === null || $idle < $staff->idleAfterSeconds();
    }

    public function anyStaffOnline(): bool
    {
        return $this->onlineStaffIds() !== [];
    }

    /**
     * @return list<int>
     */
    public function onlineStaffIds(): array
    {
        return User::query()
            ->staff()
            ->whereNotNull('id')
            ->pluck('id')
            ->filter(fn ($id) => Cache::has($this->staffKey((int) $id)))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, User>|list<User>  $staff
     * @return Collection<int, User>
     */
    public function onlineAmong(Collection|array $staff): Collection
    {
        $ids = array_flip($this->onlineStaffIds());

        return collect($staff)->filter(fn (User $user) => isset($ids[$user->id]))->values();
    }

    public function markTyping(int $ticketId, string $side): void
    {
        Cache::put($this->typingKey($ticketId, $side), now()->timestamp, (int) config('support.typing_ttl', 5));
    }

    public function isTyping(int $ticketId, string $side): bool
    {
        return Cache::has($this->typingKey($ticketId, $side));
    }

    public function withinWorkingHours(?Carbon $at = null): bool
    {
        $tz = (string) config('support.hours.timezone', 'Africa/Lagos');
        $at = ($at ?? now())->timezone($tz);
        $days = config('support.hours.days', [1, 2, 3, 4, 5, 6]);

        if (! in_array((int) $at->dayOfWeekIso, $days, true)) {
            return false;
        }

        $start = Carbon::parse($at->toDateString().' '.(string) config('support.hours.start', '08:00'), $tz);
        $end = Carbon::parse($at->toDateString().' '.(string) config('support.hours.end', '18:00'), $tz);

        return $at->betweenIncluded($start, $end);
    }

    public function teamAvailable(): bool
    {
        return $this->anyStaffOnline() || $this->withinWorkingHours();
    }

    private function ttl(): int
    {
        return (int) config('support.presence_ttl', 50);
    }

    private function staffKey(int $id): string
    {
        return 'support.presence.staff.'.$id;
    }

    private function customerKey(int $id): string
    {
        return 'support.presence.customer.'.$id;
    }

    private function typingKey(int $ticketId, string $side): string
    {
        return 'support.typing.'.$ticketId.'.'.$side;
    }
}
