<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignStaffRoleRequest;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Staff\AdminPermissions;
use App\Support\Staff\StaffAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffAssignmentController extends Controller
{
    public function store(
        AssignStaffRoleRequest $request,
        User $staff,
        StaffAssignmentService $assignments,
    ): JsonResponse|RedirectResponse {
        abort_unless($staff->isStaff(), 404);
        abort_if($staff->is($request->user()) && $request->validated('role') === AdminPermissions::SUPER_KEY, 422, 'You cannot change your own Super Admin role.');

        if ($request->validated('role') === AdminPermissions::SUPER_KEY) {
            return $this->promote($request, $staff);
        }

        $role = StaffRole::query()->findOrFail($request->validated('role_id'));
        $fresh = $assignments->attach($staff->load('staffRoles'), $role, $request->user());

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Role assigned',
            'message' => $role->name.' now applies immediately.',
        ], ['staff' => $this->rolesPayload($fresh)]);
    }

    public function destroy(
        Request $request,
        User $staff,
        string $role,
        StaffAssignmentService $assignments,
    ): JsonResponse|RedirectResponse {
        abort_unless($staff->isStaff(), 404);
        abort_unless($request->user()?->isSuperAdmin(), 403);

        if ($role === AdminPermissions::SUPER_KEY) {
            return $this->demote($request, $staff);
        }

        $model = StaffRole::query()->findOrFail($role);
        $fresh = $assignments->detach($staff->load('staffRoles'), $model, $request->user());

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Role removed',
            'message' => $model->name.' no longer applies.',
        ], ['staff' => $this->rolesPayload($fresh)]);
    }

    private function promote(Request $request, User $staff): JsonResponse|RedirectResponse
    {
        if ($staff->isSuperAdmin()) {
            return AdminResponse::mutation($request, [
                'type' => 'success',
                'title' => 'Already Super Admin',
                'message' => $staff->name.' already has full access.',
            ], ['staff' => $this->rolesPayload($staff)]);
        }

        $old = $staff->role?->value;

        $staff->forceFill(['role' => UserRole::SuperAdmin])->save();

        AdminAudit::record(
            'staff.promoted',
            "{$request->user()->name} granted Super Admin to {$staff->email}.",
            $staff,
            ['role' => $old],
            ['role' => UserRole::SuperAdmin->value],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Super Admin granted',
            'message' => $staff->name.' now has full platform access. This is logged.',
        ], ['staff' => $this->rolesPayload($staff->fresh(['staffRoles']))]);
    }

    private function demote(Request $request, User $staff): JsonResponse|RedirectResponse
    {
        abort_if($staff->is($request->user()), 422, 'You cannot remove your own Super Admin role.');

        if (! $staff->isSuperAdmin()) {
            return AdminResponse::mutation($request, [
                'type' => 'success',
                'title' => 'Updated',
                'message' => $staff->name.' is not a Super Admin.',
            ], ['staff' => $this->rolesPayload($staff)]);
        }

        if (User::activeSuperAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove Super Admin from the last remaining Super Admin.',
            ]);
        }

        $old = $staff->role?->value;
        $staff->forceFill(['role' => UserRole::OperationsAdmin])->save();

        AdminAudit::record(
            'staff.demoted',
            "{$request->user()->name} removed Super Admin from {$staff->email}.",
            $staff,
            ['role' => $old],
            ['role' => UserRole::OperationsAdmin->value],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Super Admin removed',
            'message' => $staff->name.' is now operations staff. This is logged.',
        ], ['staff' => $this->rolesPayload($staff->fresh(['staffRoles']))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rolesPayload(User $staff): array
    {
        $roles = $staff->staffRoles->map(fn (StaffRole $role) => [
            'id' => $role->id,
            'key' => $role->slug,
            'name' => $role->name,
            'system' => false,
        ])->values()->all();

        if ($staff->isSuperAdmin()) {
            array_unshift($roles, [
                'id' => AdminPermissions::SUPER_KEY,
                'key' => AdminPermissions::SUPER_KEY,
                'name' => 'Super Admin',
                'system' => true,
            ]);
        }

        return [
            'id' => $staff->id,
            'is_super' => $staff->isSuperAdmin(),
            'roles' => $roles,
        ];
    }
}
