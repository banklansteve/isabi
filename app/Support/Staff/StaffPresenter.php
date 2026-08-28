<?php

namespace App\Support\Staff;

use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\AnnouncementTemplate;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryCaseEvent;
use App\Models\LeaveRequest;
use App\Models\StaffRole;
use App\Models\User;
use Illuminate\Support\Carbon;

class StaffPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function listPayload(User $user, mixed $lastLogin = null): array
    {
        $tz = config('app.display_timezone');
        $invite = $user->relationLoaded('latestStaffInvitation')
            ? $user->latestStaffInvitation
            : $user->latestStaffInvitation()->first();

        $active = $user->last_login_at
            ?: ($lastLogin ? Carbon::parse($lastLogin) : null);

        return [
            'id' => $user->id,
            'uid' => $user->uid,
            'uid_kind' => \App\Support\Identity\UserUid::isStaffUid($user->uid) ? 'staff' : 'user',
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'initials' => strtoupper(mb_substr((string) $user->first_name, 0, 1).mb_substr((string) $user->last_name, 0, 1))
                ?: strtoupper(mb_substr($user->name, 0, 2)),
            'is_super' => $user->isSuperAdmin(),
            'status' => $user->staff_status?->value,
            'status_label' => $user->staff_status?->label(),
            'roles' => self::rolesPayload($user)['roles'],
            'last_login' => $active?->timezone($tz)->format('j M Y · g:ia'),
            'last_login_iso' => $active?->toIso8601String(),
            'last_logout' => $user->last_logout_at?->timezone($tz)->format('j M Y · g:ia'),
            'last_logout_iso' => $user->last_logout_at?->toIso8601String(),
            'joined' => $user->created_at?->timezone($tz)->format('j M Y'),
            'joined_iso' => $user->created_at?->toIso8601String(),
            'invite_expires_at' => $invite?->expires_at?->timezone($tz)->format('j M Y · g:ia'),
            'invite_expires_iso' => $invite?->expires_at?->toIso8601String(),
            'invite_expired' => $invite?->isExpired() ?? false,
            'password_set' => $user->hasSetPassword(),
            'on_leave' => ! ($user->hrProfile?->isExited() ?? false)
                && (bool) ($user->on_approved_leave ?? $user->isOnLeaveOn()),
            'exited' => $user->hrProfile?->isExited() ?? false,
            'shift' => StaffShift::for($user),
            'attendance' => StaffShift::attendance($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detailPayload(User $user, mixed $lastLogin = null): array
    {
        return [
            ...self::listPayload($user, $lastLogin),
            'suspension_reason' => $user->suspension_reason,
            'invited_by' => $user->invitedBy
                ? ['id' => $user->invitedBy->id, 'name' => $user->invitedBy->name]
                : null,
            'suggested_role' => $user->latestStaffInvitation?->suggestedRole?->name,
            'last_login_ip' => $user->last_login_ip,
        ];
    }

    /**
     * @return array{id: int, is_super: bool, roles: list<array{id: int|string, key: string, name: string, system: bool}>}
     */
    public static function rolesPayload(User $staff): array
    {
        $staff->loadMissing('staffRoles');

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

    /**
     * @return list<array<string, mixed>>
     */
    public static function templates(): array
    {
        return AnnouncementTemplate::query()
            ->orderByRaw("audience = 'staff' desc")
            ->orderBy('name')
            ->get()
            ->map(fn (AnnouncementTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'audience' => $template->audience,
                'subject' => $template->subject,
                'body' => $template->body,
                'channels' => array_values(array_intersect(
                    $template->channels ?? [],
                    ['in_app', 'email'],
                )) ?: ['in_app', 'email'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function panel(User $staff, bool $canSeeHr, bool $canSeeDiscipline): array
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

        $actorLog = AdminAuditLog::query()
            ->where('actor_id', $staff->id)
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(fn (AdminAuditLog $log) => [
                'id' => 'audit-'.$log->id,
                'title' => $log->summary,
                'when' => $log->created_at?->timezone($tz)->format('j M Y · g:ia'),
                'at' => $log->created_at?->toIso8601String(),
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
                'at' => $log->created_at?->toIso8601String(),
            ]);

        return [
            'staff' => self::detailPayload($staff),
            'activity' => [
                'admin_actions' => $adminActions,
                'actor_log' => $actorLog,
                'logins' => $logins,
                'feed' => self::unifiedFeed($staff, $canSeeHr, $canSeeDiscipline, $tz),
            ],
            'templates' => self::templates(),
            'links' => [
                'hr_profile' => $canSeeHr ? route('admin.hr.staff.show', $staff) : null,
                'discipline' => $canSeeDiscipline ? route('admin.hr.discipline.index', ['q' => $staff->name]) : null,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function unifiedFeed(User $staff, bool $canSeeHr, bool $canSeeDiscipline, string $tz): array
    {
        $items = collect();

        AdminAuditLog::query()
            ->with('actor:id,name')
            ->where(function ($query) use ($staff) {
                $query->where(function ($inner) use ($staff) {
                    $inner->where('subject_type', $staff->getMorphClass())
                        ->where('subject_id', $staff->id);
                })->orWhere('summary', 'like', '%'.$staff->email.'%');
            })
            ->latest('id')
            ->limit(80)
            ->get()
            ->each(function (AdminAuditLog $log) use ($items, $tz) {
                $items->push([
                    'id' => 'access-'.$log->id,
                    'source' => 'access',
                    'title' => self::calmAccessTitle($log->action),
                    'body' => $log->summary,
                    'actor' => $log->actor?->name,
                    'when' => $log->created_at?->timezone($tz)->format('j M Y · g:ia'),
                    'at' => $log->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    'href' => null,
                    'href_label' => null,
                ]);
            });

        if ($canSeeHr) {
            $profile = $staff->hrProfile;
            if ($profile?->start_date) {
                $at = $profile->start_date->copy()->startOfDay();
                $items->push([
                    'id' => 'hr-start',
                    'source' => 'hr',
                    'title' => 'HR start date on file',
                    'body' => $profile->position ? $profile->position : null,
                    'actor' => null,
                    'when' => $at->timezone($tz)->format('j M Y'),
                    'at' => $at->toIso8601String(),
                    'href' => route('admin.hr.staff.show', $staff),
                    'href_label' => 'Open HR profile',
                ]);
            }
            if ($profile?->isExited() && $profile->exit_date) {
                $at = $profile->exit_date->copy()->startOfDay();
                $items->push([
                    'id' => 'hr-exit',
                    'source' => 'hr',
                    'title' => 'Employment ended in HR records',
                    'body' => $profile->exit_reason ?: null,
                    'actor' => null,
                    'when' => $at->timezone($tz)->format('j M Y'),
                    'at' => $at->toIso8601String(),
                    'href' => route('admin.hr.staff.show', $staff),
                    'href_label' => 'Open HR profile',
                ]);
            }

            LeaveRequest::query()
                ->with('leaveType:id,name')
                ->where('user_id', $staff->id)
                ->latest('id')
                ->limit(40)
                ->get()
                ->each(function (LeaveRequest $leave) use ($items, $staff, $tz) {
                    $at = $leave->decided_at ?? $leave->created_at ?? now();
                    $type = $leave->leaveType?->name ?: 'Leave';
                    $range = $leave->start_date?->format('j M').' – '.$leave->end_date?->format('j M Y');
                    $items->push([
                        'id' => 'leave-'.$leave->id,
                        'source' => 'hr',
                        'title' => self::leaveTitle($leave),
                        'body' => trim($type.($range ? ' · '.$range : '')),
                        'actor' => null,
                        'when' => $at->timezone($tz)->format('j M Y · g:ia'),
                        'at' => $at->toIso8601String(),
                        'href' => route('admin.hr.staff.show', $staff),
                        'href_label' => 'Open HR profile',
                    ]);
                });
        }

        if ($canSeeDiscipline) {
            $caseIds = DisciplinaryCase::query()
                ->where('user_id', $staff->id)
                ->pluck('id', 'id');

            if ($caseIds->isNotEmpty()) {
                $cases = DisciplinaryCase::query()
                    ->whereIn('id', $caseIds->keys())
                    ->get()
                    ->keyBy('id');

                DisciplinaryCaseEvent::query()
                    ->whereIn('disciplinary_case_id', $caseIds->keys())
                    ->latest('id')
                    ->limit(80)
                    ->get()
                    ->each(function (DisciplinaryCaseEvent $event) use ($items, $cases, $tz) {
                        $case = $cases->get($event->disciplinary_case_id);
                        if (! $case) {
                            return;
                        }
                        $items->push([
                            'id' => 'case-event-'.$event->id,
                            'source' => 'discipline',
                            'title' => $event->typeLabel(),
                            'body' => 'Case '.$case->reference,
                            'actor' => null,
                            'when' => $event->created_at?->timezone($tz)->format('j M Y · g:ia'),
                            'at' => $event->created_at?->toIso8601String() ?? now()->toIso8601String(),
                            'href' => route('admin.hr.discipline.show', $case),
                            'href_label' => 'Open case',
                        ]);
                    });
            }
        }

        return $items
            ->sortByDesc(fn (array $row) => $row['at'] ?? '')
            ->values()
            ->take(80)
            ->all();
    }

    private static function calmAccessTitle(string $action): string
    {
        return match ($action) {
            'staff.invited' => 'Invite sent',
            'staff.invite_resent' => 'Invite resent',
            'staff.invite_revoked' => 'Invite revoked',
            'staff.invite_accepted' => 'Invite accepted',
            'staff.disabled' => 'Access disabled',
            'staff.reinstated' => 'Access restored',
            'staff.removed' => 'Access removed',
            'staff.promoted' => 'Super Admin granted',
            'staff.demoted' => 'Super Admin removed',
            'staff.role_assigned', 'staff.roles_synced' => 'Roles updated',
            'staff.role_removed' => 'Role removed',
            'staff.force_logout' => 'Sessions signed out',
            'staff.password_reset' => 'Password reset sent',
            'staff.messaged', 'staff.announcement_sent' => 'Message sent',
            default => str($action)->replace('.', ' · ')->headline()->toString(),
        };
    }

    private static function leaveTitle(LeaveRequest $leave): string
    {
        return match ($leave->status) {
            LeaveRequest::STATUS_APPROVED => 'Leave approved',
            LeaveRequest::STATUS_REJECTED => 'Leave declined',
            LeaveRequest::STATUS_CANCELLED => 'Leave cancelled',
            default => 'Leave requested',
        };
    }
}
