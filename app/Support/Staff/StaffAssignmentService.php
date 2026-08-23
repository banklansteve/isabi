<?php

namespace App\Support\Staff;

use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAssignmentService
{
    public function attach(User $staff, StaffRole $role, User $assignedBy): User
    {
        $this->assertAssignable($staff);

        if (! $role->is_active) {
            throw ValidationException::withMessages([
                'role_id' => 'That role is inactive.',
            ]);
        }

        if ($staff->staffRoles->contains('id', $role->id)) {
            return $staff;
        }

        $old = $staff->staffRoles->pluck('name')->all();

        $staff->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $assignedBy->id,
            'assigned_at' => now(),
        ]);

        $fresh = $staff->fresh(['staffRoles']);

        AdminAudit::record(
            'staff.role_assigned',
            "{$assignedBy->name} assigned {$role->name} to {$staff->email}.",
            $staff,
            ['roles' => $old],
            ['roles' => $fresh->staffRoles->pluck('name')->all(), 'role_id' => $role->id],
            $assignedBy,
        );

        return $fresh;
    }

    public function detach(User $staff, StaffRole $role, User $assignedBy): User
    {
        $this->assertAssignable($staff);

        $old = $staff->staffRoles->pluck('name')->all();

        $staff->staffRoles()->detach($role->id);

        $fresh = $staff->fresh(['staffRoles']);

        AdminAudit::record(
            'staff.role_removed',
            "{$assignedBy->name} removed {$role->name} from {$staff->email}.",
            $staff,
            ['roles' => $old],
            ['roles' => $fresh->staffRoles->pluck('name')->all(), 'role_id' => $role->id],
            $assignedBy,
        );

        return $fresh;
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function reassignFrom(StaffRole $from, ?StaffRole $to, User $actor): int
    {
        $moved = 0;

        User::query()
            ->whereHas('staffRoles', fn ($query) => $query->where('staff_roles.id', $from->id))
            ->get()
            ->each(function (User $staff) use ($from, $to, $actor, &$moved) {
                $this->detach($staff, $from, $actor);
                if ($to) {
                    $this->attach($staff->fresh(['staffRoles']), $to, $actor);
                }
                $moved++;
            });

        return $moved;
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function sync(User $staff, array $roleIds, User $assignedBy): User
    {
        $this->assertAssignable($staff);

        $roleIds = collect($roleIds)->map(fn ($id) => (int) $id)->unique()->values();

        $roles = StaffRole::query()
            ->whereIn('id', $roleIds)
            ->where('is_active', true)
            ->get();

        if ($roles->count() !== $roleIds->count()) {
            throw ValidationException::withMessages([
                'role_ids' => 'One or more selected roles are invalid or inactive.',
            ]);
        }

        $old = $staff->staffRoles->pluck('name')->all();

        DB::transaction(function () use ($staff, $roles, $assignedBy): void {
            $staff->staffRoles()->sync(
                $roles->mapWithKeys(fn (StaffRole $role) => [
                    $role->id => [
                        'assigned_by_user_id' => $assignedBy->id,
                        'assigned_at' => now(),
                    ],
                ])->all()
            );
        });

        $fresh = $staff->fresh(['staffRoles']);

        AdminAudit::record(
            'staff.roles_synced',
            "{$assignedBy->name} updated roles for {$staff->email}.",
            $staff,
            ['roles' => $old],
            ['roles' => $fresh->staffRoles->pluck('name')->all()],
            $assignedBy,
        );

        return $fresh;
    }

    private function assertAssignable(User $staff): void
    {
        if (! $staff->isStaff()) {
            throw ValidationException::withMessages([
                'role_id' => 'Roles can only be assigned to staff.',
            ]);
        }
    }
}
