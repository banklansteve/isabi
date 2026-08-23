<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyStaffRoleRequest;
use App\Http\Requests\Admin\StoreStaffRoleRequest;
use App\Http\Requests\Admin\UpdateStaffRoleRequest;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Staff\AdminPermissions;
use App\Support\Staff\StaffAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StaffRoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Roles/Index', $this->indexProps());
    }

    public function store(StoreStaffRoleRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $maxSort = (int) StaffRole::query()->max('sort_order');

        $role = StaffRole::query()->create([
            ...$data,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $maxSort + 1,
            'created_by_user_id' => $request->user()->id,
        ]);

        AdminAudit::record(
            'staff.role_created',
            "{$request->user()->name} created the {$role->name} role.",
            $role,
            null,
            ['permissions' => $role->permissions],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Role created',
            'message' => $role->name.' is ready to assign.',
        ], ['role' => $role->loadCount('assignments')->toAdminArray()]);
    }

    public function update(UpdateStaffRoleRequest $request, StaffRole $role): JsonResponse|RedirectResponse
    {
        abort_if($role->is_system, 422, 'System roles cannot be edited.');

        $data = $request->validated();
        $old = $role->only(['name', 'description', 'permissions', 'is_active']);

        if (array_key_exists('slug', $data) && $data['slug'] !== $role->slug) {
            unset($data['slug']);
        }

        $role->fill($data)->save();

        AdminAudit::record(
            'staff.role_updated',
            "{$request->user()->name} updated the {$role->name} role.",
            $role,
            $old,
            $role->only(['name', 'description', 'permissions', 'is_active']),
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Role updated',
            'message' => 'Permission changes apply on the next request.',
        ], ['role' => $role->loadCount('assignments')->toAdminArray()]);
    }

    public function destroy(
        DestroyStaffRoleRequest $request,
        StaffRole $role,
        StaffAssignmentService $assignments,
    ): JsonResponse|RedirectResponse {
        abort_if($role->is_system, 422, 'System roles cannot be deleted.');

        $data = $request->validated();
        $name = $role->name;
        $assigned = (int) $role->assignments()->count();
        $reassign = isset($data['reassign_to'])
            ? StaffRole::query()->find($data['reassign_to'])
            : null;

        if ($assigned > 0) {
            $assignments->reassignFrom($role, $reassign, $request->user());
        }

        $role->delete();

        AdminAudit::record(
            'staff.role_destroyed',
            "{$request->user()->name} deleted the {$name} role: {$data['reason']}",
            null,
            ['role' => $name, 'assigned' => $assigned],
            ['reason' => $data['reason'], 'reassign_to' => $reassign?->name],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Role deleted',
            'message' => $assigned > 0
                ? ($reassign
                    ? "{$assigned} staff moved to {$reassign->name}."
                    : "{$assigned} staff no longer have that role.")
                : $name.' is gone.',
        ], ['deleted_id' => $role->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(): array
    {
        $roles = StaffRole::query()
            ->withCount('assignments')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (StaffRole $role) => $role->toAdminArray())
            ->values();

        $superCount = User::query()->where('role', UserRole::SuperAdmin)->count();

        return [
            'roles' => $roles,
            'super_admin' => [
                'id' => AdminPermissions::SUPER_KEY,
                'name' => 'Super Admin',
                'description' => 'Full platform control. This system role cannot be edited or deleted.',
                'is_system' => true,
                'assigned_count' => $superCount,
                'permissions_count' => count(AdminPermissions::keys()),
                'permissions' => AdminPermissions::keys(),
            ],
            'permission_groups' => AdminPermissions::grouped(),
        ];
    }
}
