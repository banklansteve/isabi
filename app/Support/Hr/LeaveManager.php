<?php

namespace App\Support\Hr;

use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveManager
{
    public static function currentYear(): int
    {
        return (int) now()->year;
    }

    /**
     * Days allowed for a staff member on a leave type this year — a per-staff
     * allocation override when set, otherwise the leave type default.
     */
    public static function allowanceFor(User $user, LeaveType $type, ?int $year = null): int
    {
        $year ??= self::currentYear();

        $allocation = LeaveAllocation::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $year)
            ->first();

        return $allocation?->allowance_days ?? $type->allowance_days;
    }

    /**
     * Approved days taken for a leave type in a given year.
     */
    public static function usedDays(User $user, LeaveType $type, ?int $year = null): int
    {
        $year ??= self::currentYear();

        return (int) LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $type->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->sum('days');
    }

    /**
     * Days locked in pending (not yet approved) requests.
     */
    public static function pendingDays(User $user, LeaveType $type, ?int $year = null): int
    {
        $year ??= self::currentYear();

        return (int) LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $type->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereYear('start_date', $year)
            ->sum('days');
    }

    /**
     * Balance rows for every active leave type for a staff member.
     *
     * @return list<array<string, mixed>>
     */
    public static function balances(User $user, ?int $year = null): array
    {
        $year ??= self::currentYear();

        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (LeaveType $type) use ($user, $year) {
                $allowance = self::allowanceFor($user, $type, $year);
                $used = self::usedDays($user, $type, $year);
                $pending = self::pendingDays($user, $type, $year);

                return [
                    'leave_type_id' => $type->id,
                    'key' => $type->key,
                    'name' => $type->name,
                    'color' => $type->color,
                    'allowance_days' => $allowance,
                    'used_days' => $used,
                    'pending_days' => $pending,
                    'remaining_days' => $allowance - $used,
                ];
            })
            ->all();
    }

    /**
     * Existing pending/approved requests that overlap the range.
     *
     * @return Collection<int, LeaveRequest>
     */
    public static function overlaps(User $user, string $start, string $end, ?int $ignoreId = null)
    {
        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->activeHold()
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get();
    }

    public static function hasOverlap(User $user, string $start, string $end, ?int $ignoreId = null): bool
    {
        return self::overlaps($user, $start, $end, $ignoreId)->isNotEmpty();
    }

    /**
     * Remaining balance if the given number of days were approved on this type.
     */
    public static function remainingAfter(User $user, LeaveType $type, int $days, ?int $year = null): int
    {
        $year ??= self::currentYear();

        return self::allowanceFor($user, $type, $year) - self::usedDays($user, $type, $year) - $days;
    }

    public static function days(string $start, string $end): int
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->startOfDay();

        return (int) $from->diffInDays($to) + 1;
    }
}
