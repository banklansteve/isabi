<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StaffStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkMessageStaffRequest;
use App\Http\Requests\Admin\DestroyStaffRequest;
use App\Http\Requests\Admin\InviteStaffRequest;
use App\Http\Requests\Admin\MessageStaffRequest;
use App\Http\Requests\Admin\ResetStaffPasswordRequest;
use App\Http\Requests\Admin\SendStaffAnnouncementRequest;
use App\Http\Requests\Admin\StaffReasonRequest;
use App\Http\Requests\Admin\UpdateStaffShiftRequest;
use App\Models\LeaveRequest;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\AnnouncementService;
use App\Support\Staff\AdminPermissions;
use App\Support\Staff\StaffInvitationService;
use App\Support\Staff\StaffPresenter;
use App\Support\Staff\StaffShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
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
        $staff->load(['staffRoles', 'invitedBy:id,name,email', 'latestStaffInvitation.suggestedRole', 'hrProfile']);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            $actor = $request->user();

            return response()->json(StaffPresenter::panel(
                $staff,
                (bool) ($actor?->canDo('hr.view')),
                (bool) ($actor?->canDo('hr.discipline.view')),
            ));
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
            'message' => "They have {$hours} hours to open the link and set up their account.",
        ], ['staff' => StaffPresenter::listPayload($payload['user']->load(['staffRoles', 'latestStaffInvitation']))]);
    }

    public function resend(Request $request, User $staff, StaffInvitationService $invitations): JsonResponse|RedirectResponse
    {
        $this->assertCanManage($request->user());
        $invitations->resend($staff, $request->user());

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Invite resent',
            'message' => 'The previous link no longer works. A new one is on its way.',
        ], ['staff' => StaffPresenter::listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation']))]);
    }

    public function revoke(StaffReasonRequest $request, User $staff, StaffInvitationService $invitations): JsonResponse|RedirectResponse
    {
        $id = $staff->id;
        $invitations->revoke($staff, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Invite revoked',
            'message' => 'They will not appear as active staff.',
        ], ['deleted_id' => $id]);
    }

    public function disable(StaffReasonRequest $request, User $staff): JsonResponse|RedirectResponse
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
        ], ['staff' => StaffPresenter::listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation', 'hrProfile']))]);
    }

    public function reinstate(StaffReasonRequest $request, User $staff): JsonResponse|RedirectResponse
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
        ], ['staff' => StaffPresenter::listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation', 'hrProfile']))]);
    }

    public function forceLogout(StaffReasonRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        $this->guardSensitive($request->user(), $staff, allowLastSuper: true);

        if (! $staff->hasSetPassword()) {
            throw ValidationException::withMessages([
                'reason' => 'They have not signed in yet. Revoke or resend the invite instead.',
            ]);
        }

        $reason = $request->validated('reason');
        $staff->invalidateSessions();
        $staff->forceFill(['last_logout_at' => now()])->save();

        AdminAudit::record(
            'staff.force_logout',
            "{$request->user()->name} signed {$staff->email} out of every session: {$reason}",
            $staff,
            null,
            ['reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Sessions signed out',
            'message' => $staff->name.' will need to sign in again on every device.',
        ], ['staff' => StaffPresenter::listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation', 'hrProfile']))]);
    }

    public function updateShift(UpdateStaffShiftRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        abort_unless($staff->isStaff(), 404);

        $old = [
            'shift_days' => $staff->shift_days,
            'shift_starts_at' => $staff->shift_starts_at,
            'shift_ends_at' => $staff->shift_ends_at,
        ];
        $shift = $request->shift();
        $staff->forceFill($shift)->save();

        AdminAudit::record(
            'staff.shift_updated',
            "{$request->user()->name} set {$staff->email}'s shift to {$shift['shift_starts_at']}–{$shift['shift_ends_at']}.",
            $staff,
            $old,
            $shift,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Shift saved',
            'message' => $staff->name."'s working hours are updated.",
        ], ['staff' => StaffPresenter::listPayload($staff->fresh(['staffRoles', 'latestStaffInvitation', 'hrProfile']))]);
    }

    public function sendPasswordReset(ResetStaffPasswordRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        $this->guardSensitive($request->user(), $staff, allowLastSuper: true);

        if (! $staff->hasSetPassword()) {
            throw ValidationException::withMessages([
                'reason' => 'They have not set a password yet. Resend their invite instead.',
            ]);
        }

        if ($staff->isSuspended()) {
            throw ValidationException::withMessages([
                'reason' => 'Reinstate this account before sending a password reset.',
            ]);
        }

        $reason = $request->validated('reason');
        $status = Password::broker()->sendResetLink(['email' => $staff->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => 'Could not send a reset link right now. Try again shortly.',
            ]);
        }

        AdminAudit::record(
            'staff.password_reset',
            "{$request->user()->name} sent a password reset to {$staff->email}: {$reason}",
            $staff,
            null,
            ['reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Reset email sent',
            'message' => 'A password reset link is on its way to '.$staff->email.'.',
        ]);
    }

    public function message(
        MessageStaffRequest $request,
        User $staff,
        AnnouncementService $announcements,
    ): JsonResponse|RedirectResponse {
        abort_unless($staff->isStaff(), 404);

        $data = $request->validated();
        $announcement = $announcements->sendToStaff(
            $request->user(),
            [$staff->id],
            $data['subject'],
            $data['body'],
            $data['channels'],
        );

        AdminAudit::record(
            'staff.messaged',
            "{$request->user()->name} messaged {$staff->email}.",
            $staff,
            null,
            ['announcement_id' => $announcement->id, 'channels' => $data['channels']],
        );

        $count = (int) ($announcement->recipient_count ?? 1);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Message sent',
            'message' => $this->sentToast($count, $data['channels']),
        ], ['count' => $count]);
    }

    public function sendAnnouncement(
        SendStaffAnnouncementRequest $request,
        User $staff,
        AnnouncementService $announcements,
    ): JsonResponse|RedirectResponse {
        abort_unless($staff->isStaff(), 404);

        $data = $request->validated();
        $announcement = $announcements->sendToStaff(
            $request->user(),
            [$staff->id],
            $data['subject'],
            $data['body'],
            $data['channels'],
            (int) $data['template_id'],
        );

        AdminAudit::record(
            'staff.announcement_sent',
            "{$request->user()->name} sent a notice to {$staff->email}.",
            $staff,
            null,
            ['announcement_id' => $announcement->id, 'template_id' => $data['template_id']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Notice sent',
            'message' => 'Delivered to '.$staff->name.' only.',
        ], ['count' => 1]);
    }

    public function bulkMessage(
        BulkMessageStaffRequest $request,
        AnnouncementService $announcements,
    ): JsonResponse|RedirectResponse {
        $data = $request->validated();

        $ids = User::query()
            ->staff()
            ->whereIn('id', $data['ids'])
            ->pluck('id')
            ->all();

        if ($ids === []) {
            throw ValidationException::withMessages([
                'ids' => 'None of the selected people are staff accounts.',
            ]);
        }

        $announcement = $announcements->sendToStaff(
            $request->user(),
            $ids,
            $data['subject'],
            $data['body'],
            $data['channels'],
        );

        AdminAudit::record(
            'staff.bulk_messaged',
            "{$request->user()->name} messaged ".count($ids).' staff: '.$data['reason'],
            $announcement,
            null,
            ['reason' => $data['reason'], 'user_ids' => $ids, 'channels' => $data['channels']],
        );

        $count = (int) ($announcement->recipient_count ?? count($ids));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Message sent',
            'message' => $this->sentToast($count, $data['channels']),
        ], ['count' => $count]);
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
            'message' => $name.' no longer has admin access. HR and case history are kept.',
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

        return [
            'staff' => $staff->map(fn (User $user) => StaffPresenter::listPayload($user))->values(),
            'roles' => StaffRole::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (StaffRole $role) => $role->toAdminArray()),
            'templates' => StaffPresenter::templates(),
            'invite_ttl_hours' => AdminPermissions::ttlHours(),
            'shift_weekdays' => StaffShift::weekdays(),
            'opened_id' => null,
        ];
    }

    private function guardSensitive(User $actor, User $staff, bool $allowLastSuper = false): void
    {
        if ($staff->is($actor)) {
            throw ValidationException::withMessages([
                'reason' => 'You cannot change your own access this way.',
            ]);
        }

        if ($staff->isSuperAdmin() && ! $allowLastSuper && User::activeSuperAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'reason' => 'You cannot disable or remove the last Super Admin.',
            ]);
        }
    }

    private function assertCanManage(?User $actor): void
    {
        abort_unless($actor?->isSuperAdmin() && $actor->canDo('admin.staff.manage'), 403);
    }

    /**
     * @param  list<string>  $channels
     */
    private function sentToast(int $count, array $channels): string
    {
        $via = collect($channels)
            ->map(fn (string $channel) => $channel === 'email' ? 'email' : 'in-app')
            ->unique()
            ->values()
            ->all();

        $channelLabel = match (count($via)) {
            0, 1 => $via[0] ?? 'in-app',
            default => implode(' and ', $via),
        };

        return $count === 1
            ? "Sent to 1 person via {$channelLabel}."
            : "Sent to {$count} people via {$channelLabel}.";
    }
}
