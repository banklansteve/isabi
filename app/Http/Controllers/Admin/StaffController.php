<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StaffStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminReasonRequest;
use App\Http\Requests\Admin\DestroyStaffRequest;
use App\Http\Requests\Admin\InviteStaffRequest;
use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\LeaveRequest;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Staff\AdminPermissions;
use App\Support\Staff\StaffInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Staff/Index', $this->indexProps());
    }

    public function show(Request $request, User $staff): Response|JsonResponse
    {
        abort_unless($staff->isStaff(), 404);
        $staff->load(['staffRoles', 'invitedBy:id,name,email', 'latestStaffInvitation.suggestedRole']);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($this->panel($staff));
        }

        return Inertia::render('Admin/Staff/Index', [
            ...$this->indexProps(),
            'opened_id' => $staff->id,
        ]);
    }

    public function store(InviteStaffRequest $request, StaffInvitationService $invitations): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $payload = $invitations->invite(
            invitedBy: $request->user(),
            email: $data['email'],
            name: $data['name'] ?? null,
            suggestedRoleId: isset($data['suggested_role_id']) ? (int) $data['suggested_role_id'] : null,
        );

        $hours = AdminPermissions::ttlHours();

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Invite sent',
            'message' => "They have {$hours} hours to set up their account.",
        ], ['staff' => $this->listPayload($payload['user']->load(['staffRoles', 'latestStaffInvitation']))]);
    }

    public function resend(Request $request, User $staff, StaffInvitationService $invitations): JsonResponse|RedirectResponse
    {
        $invitations->resend($staff, $request->user());

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Invite resent',
            'message' => 'The previous link no longer works. A new one is on its way.',
        ], ['staff' => $this->listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation']))]);
    }

    public function revoke(AdminReasonRequest $request, User $staff, StaffInvitationService $invitations): JsonResponse|RedirectResponse
    {
        $id = $staff->id;
        $invitations->revoke($staff, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Invite revoked',
            'message' => 'They will not appear as active staff.',
        ], ['deleted_id' => $id]);
    }

    public function disable(AdminReasonRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        $this->guardSensitive($request->user(), $staff);

        $reason = $request->validated('reason');
        $old = ['staff_status' => $staff->staff_status?->value];

        $staff->forceFill([
            'staff_status' => StaffStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();

        $staff->invalidateSessions();

        AdminAudit::record(
            'staff.disabled',
            "{$request->user()->name} disabled {$staff->email}: {$reason}",
            $staff,
            $old,
            ['staff_status' => StaffStatus::Suspended->value, 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Access disabled',
            'message' => $staff->name.' is signed out everywhere and cannot sign in.',
        ], ['staff' => $this->listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation']))]);
    }

    public function reinstate(AdminReasonRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        $this->guardSensitive($request->user(), $staff, allowLastSuper: true);

        $reason = $request->validated('reason');
        $old = ['staff_status' => $staff->staff_status?->value];

        $staff->forceFill([
            'staff_status' => $staff->hasSetPassword() ? StaffStatus::Active : StaffStatus::Invited,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        AdminAudit::record(
            'staff.reinstated',
            "{$request->user()->name} reinstated {$staff->email}: {$reason}",
            $staff,
            $old,
            ['staff_status' => $staff->staff_status?->value, 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Access restored',
            'message' => $staff->name.' can sign in again.',
        ], ['staff' => $this->listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation']))]);
    }

    public function destroy(DestroyStaffRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        $this->guardSensitive($request->user(), $staff);

        $reason = $request->validated('reason');
        $name = $staff->name;
        $email = $staff->email;
        $id = $staff->id;

        $staff->invalidateSessions();
        $staff->delete();

        AdminAudit::record(
            'staff.removed',
            "{$request->user()->name} removed {$email}: {$reason}",
            $staff,
            ['email' => $email],
            ['reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Staff removed',
            'message' => $name.' no longer has access. The account is recoverable from the database if this was a mistake.',
        ], ['deleted_id' => $id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(): array
    {
        $today = now()->toDateString();

        $staff = User::query()
            ->staff()
            ->with(['staffRoles', 'invitedBy:id,name,email', 'latestStaffInvitation', 'hrProfile'])
            ->withExists(['leaveRequests as on_approved_leave' => function ($query) use ($today) {
                $query->where('status', LeaveRequest::STATUS_APPROVED)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            }])
            ->orderByRaw("role = 'super_admin' desc")
            ->orderBy('name')
            ->get();

        $lastLogins = ActivityLog::query()
            ->selectRaw('user_id, MAX(created_at) as last_at')
            ->whereIn('user_id', $staff->pluck('id'))
            ->whereIn('action', ['auth.admin_login', 'auth.login'])
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        return [
            'staff' => $staff->map(fn (User $user) => $this->listPayload($user, $lastLogins->get($user->id)))->values(),
            'roles' => StaffRole::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (StaffRole $role) => $role->toAdminArray()),
            'invite_ttl_hours' => AdminPermissions::ttlHours(),
            'opened_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panel(User $staff): array
    {
        $tz = config('app.display_timezone');

        $adminActions = AdminAuditLog::query()
            ->with('actor:id,name,email')
            ->where(function ($query) use ($staff) {
                $query->where(function ($inner) use ($staff) {
                    $inner->where('subject_type', $staff->getMorphClass())
                        ->where('subject_id', $staff->id);
                })->orWhere('summary', 'like', '%'.$staff->email.'%');
            })
            ->latest('id')
            ->limit(80)
            ->get()
            ->map(fn (AdminAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'summary' => $log->summary,
                'actor' => $log->actor?->name,
                'when' => $log->created_at?->timezone($tz)->format('j M Y · g:ia'),
            ]);

        $logins = ActivityLog::query()
            ->where('user_id', $staff->id)
            ->whereIn('action', ['auth.admin_login', 'auth.admin_logout', 'auth.login'])
            ->latest('created_at')
            ->limit(40)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'title' => $log->titleFromAction(),
                'ip' => $log->ip_address,
                'when' => $log->created_at?->timezone($tz)->format('j M Y · g:ia'),
            ]);

        return [
            'staff' => $this->detailPayload($staff),
            'activity' => [
                'admin_actions' => $adminActions,
                'logins' => $logins,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(User $user, mixed $lastLogin = null): array
    {
        $tz = config('app.display_timezone');
        $invite = $user->relationLoaded('latestStaffInvitation') ? $user->latestStaffInvitation : $user->latestStaffInvitation()->first();
        $active = $lastLogin ? Carbon::parse($lastLogin) : null;

        $roles = $user->staffRoles->map(fn (StaffRole $role) => [
            'id' => $role->id,
            'key' => $role->slug,
            'name' => $role->name,
            'system' => false,
        ])->values()->all();

        if ($user->isSuperAdmin()) {
            array_unshift($roles, [
                'id' => AdminPermissions::SUPER_KEY,
                'key' => AdminPermissions::SUPER_KEY,
                'name' => 'Super Admin',
                'system' => true,
            ]);
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'initials' => strtoupper(mb_substr((string) $user->first_name, 0, 1).mb_substr((string) $user->last_name, 0, 1)) ?: strtoupper(mb_substr($user->name, 0, 2)),
            'is_super' => $user->isSuperAdmin(),
            'status' => $user->staff_status?->value,
            'status_label' => $user->staff_status?->label(),
            'roles' => $roles,
            'last_login' => $active?->timezone($tz)->format('j M Y · g:ia'),
            'last_login_iso' => $active?->toIso8601String(),
            'joined' => $user->created_at?->timezone($tz)->format('j M Y'),
            'joined_iso' => $user->created_at?->toIso8601String(),
            'invite_expires_at' => $invite?->expires_at?->timezone($tz)->format('j M Y · g:ia'),
            'invite_expires_iso' => $invite?->expires_at?->toIso8601String(),
            'invite_expired' => $invite?->isExpired() ?? false,
            'password_set' => $user->hasSetPassword(),
            'on_leave' => ! ($user->hrProfile?->isExited() ?? false)
                && (bool) ($user->on_approved_leave ?? $user->isOnLeaveOn()),
            'exited' => $user->hrProfile?->isExited() ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(User $user): array
    {
        return [
            ...$this->listPayload($user),
            'suspension_reason' => $user->suspension_reason,
            'invited_by' => $user->invitedBy
                ? ['id' => $user->invitedBy->id, 'name' => $user->invitedBy->name]
                : null,
            'suggested_role' => $user->latestStaffInvitation?->suggestedRole?->name,
        ];
    }

    private function guardSensitive(User $actor, User $staff, bool $allowLastSuper = false): void
    {
        abort_if($staff->is($actor), 422, 'You cannot change your own access this way.');

        if ($staff->isSuperAdmin() && ! $allowLastSuper && User::activeSuperAdminCount() <= 1) {
            abort(422, 'You cannot disable or remove the last Super Admin.');
        }
    }
}
