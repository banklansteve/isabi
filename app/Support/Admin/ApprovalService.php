<?php

namespace App\Support\Admin;

use App\Enums\StaffStatus;
use App\Enums\UserRole;
use App\Models\AdminApproval;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Realtime\Realtime;
use App\Support\Staff\AppSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ApprovalService
{
    public const ACTIONS_REQUIRING_APPROVAL = [
        'users.suspend',
        'users.delete',
        'jobs.remove',
        'reviews.remove',
        'messages.send',
    ];

    public function __construct(
        private readonly AppSettingsService $settings,
        private readonly AnnouncementService $announcements,
        private readonly Realtime $realtime,
    ) {}

    public function requiresApproval(User $actor, string $action): bool
    {
        if ($actor->isSuperAdmin()) {
            return false;
        }

        if (! in_array($action, self::ACTIONS_REQUIRING_APPROVAL, true)) {
            return false;
        }

        $configured = $this->settings->get('ops.approval_actions', self::ACTIONS_REQUIRING_APPROVAL);

        if (! is_array($configured)) {
            $configured = self::ACTIONS_REQUIRING_APPROVAL;
        }

        return in_array($action, $configured, true);
    }

    /**
     * @param  callable(): mixed  $execute
     * @param  array<string, mixed>  $payload
     * @return array{status: string, approval?: AdminApproval, result?: mixed}
     */
    public function run(
        User $actor,
        string $action,
        ?Model $subject,
        string $reason,
        array $payload,
        callable $execute,
    ): array {
        if (! $this->requiresApproval($actor, $action)) {
            $result = $execute();

            AdminAudit::record(
                $action,
                $this->summary($actor, $action, $subject, $reason, executed: true),
                $subject,
                $payload['old'] ?? null,
                $payload['new'] ?? $payload,
                $actor,
            );

            return ['status' => 'executed', 'result' => $result];
        }

        $approval = $this->queue($actor, $action, $subject, $reason, $payload);

        return ['status' => 'pending', 'approval' => $approval];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function queue(
        User $actor,
        string $action,
        ?Model $subject,
        string $reason,
        array $payload,
    ): AdminApproval {
        $approval = AdminApproval::query()->create([
            'uid' => $this->uid(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'payload' => $payload,
            'reason' => $reason,
            'status' => AdminApproval::STATUS_PENDING,
            'requested_by_user_id' => $actor->id,
        ]);

        AdminAudit::record(
            'approvals.requested',
            "{$actor->name} requested approval for {$action}.",
            $approval,
            null,
            ['action' => $action, 'reason' => $reason],
            $actor,
        );

        $this->notifySuperAdmins($approval);

        return $approval;
    }

    public function approve(AdminApproval $approval, User $reviewer, string $note, callable $execute): mixed
    {
        abort_unless($reviewer->isSuperAdmin(), 403);
        abort_unless($approval->isPending(), 422, 'This request is no longer pending.');

        $result = $execute();

        $approval->forceFill([
            'status' => AdminApproval::STATUS_APPROVED,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note !== '' ? $note : null,
        ])->save();

        AdminAudit::record(
            'approvals.approved',
            "{$reviewer->name} approved {$approval->action}.",
            $approval,
            ['status' => AdminApproval::STATUS_PENDING],
            ['status' => AdminApproval::STATUS_APPROVED, 'note' => $note],
            $reviewer,
        );

        $this->notifyRequester($approval, approved: true);

        return $result;
    }

    public function reject(AdminApproval $approval, User $reviewer, string $note): void
    {
        abort_unless($reviewer->isSuperAdmin(), 403);
        abort_unless($approval->isPending(), 422, 'This request is no longer pending.');

        $approval->forceFill([
            'status' => AdminApproval::STATUS_REJECTED,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note !== '' ? $note : 'Rejected',
        ])->save();

        AdminAudit::record(
            'approvals.rejected',
            "{$reviewer->name} rejected {$approval->action}.",
            $approval,
            ['status' => AdminApproval::STATUS_PENDING],
            ['status' => AdminApproval::STATUS_REJECTED, 'note' => $note],
            $reviewer,
        );

        $this->notifyRequester($approval, approved: false);
    }

    /**
     * @return Collection<int, AdminApproval>
     */
    public function pending(): Collection
    {
        if (! Schema::hasTable('admin_approvals')) {
            return collect();
        }

        return AdminApproval::query()
            ->with(['requester:id,uid,name,email,avatar_url', 'subject'])
            ->where('status', AdminApproval::STATUS_PENDING)
            ->latest('id')
            ->get();
    }

    /**
     * Pending approvals for the Super Admin priority inbox.
     *
     * @return list<array<string, mixed>>
     */
    public function superAdminAttentionItems(): array
    {
        if (! Schema::hasTable('admin_approvals')) {
            return [];
        }

        return $this->pending()
            ->map(function (AdminApproval $approval) {
                $subject = $this->subjectSummary($approval);

                return [
                    'key' => 'approval:'.$approval->uid,
                    'signature' => 'approval:'.$approval->uid.':'.($approval->created_at?->timestamp ?? 0),
                    'approval_uid' => $approval->uid,
                    'urgency' => 110,
                    'sort_at' => $approval->created_at?->timestamp ?? 0,
                    'title' => $this->actionLabel($approval->action),
                    'subtitle' => trim(implode(' · ', array_filter([
                        $approval->requester?->name ?: $approval->requester?->email,
                        $subject,
                        $approval->reason ? Str::limit($approval->reason, 72) : null,
                    ]))),
                    'href' => route('admin.approvals.show', $approval),
                    'icon' => 'ti ti-shield-check',
                    'tone' => 'high',
                    'queue' => 'Approval',
                    'group' => 'approvals',
                    'priority' => 'high',
                    'count' => 1,
                    'unread' => true,
                    'note' => $approval->reason,
                    'referrer' => [
                        'id' => $approval->requested_by_user_id,
                        'name' => $approval->requester?->name ?: $approval->requester?->email,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function notifySuperAdmins(AdminApproval $approval): void
    {
        $superIds = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('staff_status', StaffStatus::Active)
            ->pluck('id')
            ->all();

        if ($superIds === []) {
            return;
        }

        $approval->loadMissing('requester');
        $href = route('admin.approvals.show', $approval);
        $label = $this->actionLabel($approval->action);
        $requester = $approval->requester?->name ?: 'Operations staff';
        $subject = $this->subjectSummary($approval);

        $body = trim(implode("\n\n", array_filter([
            "{$requester} requested {$label}.",
            $subject ? "Subject: {$subject}" : null,
            $approval->reason ? "Reason: {$approval->reason}" : null,
        ])));

        $actor = $approval->requester
            ?? User::query()->find($approval->requested_by_user_id);

        if (! $actor instanceof User) {
            return;
        }

        $this->announcements->sendToStaff(
            $actor,
            $superIds,
            'Approval needed: '.$label,
            $body,
            [Announcement::CHANNEL_IN_APP],
            null,
            [
                'kind' => 'staff_approval',
                'href' => $href,
                'approval_uid' => $approval->uid,
            ],
        );
    }

    private function notifyRequester(AdminApproval $approval, bool $approved): void
    {
        $requester = $approval->requester;
        if (! $requester) {
            return;
        }

        $this->pushInApp(
            $requester,
            $approved ? 'Request approved' : 'Request rejected',
            ($approved ? 'Approved' : 'Rejected').': '.$this->actionLabel($approval->action),
            route('admin.my-approvals.index'),
        );
    }

    private function pushInApp(User $user, string $title, string $body, string $href): void
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasTable('announcement_deliveries')) {
            return;
        }

        $announcement = Announcement::query()->create([
            'audience' => Announcement::AUDIENCE_STAFF,
            'title' => $title,
            'subject' => $title,
            'body' => $body,
            'channels' => [Announcement::CHANNEL_IN_APP],
            'segment' => ['user_id' => $user->id, 'href' => $href],
            'status' => Announcement::STATUS_SENT,
            'sent_at' => now(),
            'recipient_count' => 1,
            'sent_count' => 1,
            'created_by_user_id' => null,
        ]);

        $delivery = AnnouncementDelivery::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'channel' => Announcement::CHANNEL_IN_APP,
            'status' => AnnouncementDelivery::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $item = $this->announcements->presentDelivery($delivery->load('announcement'), $user);
        $item['href'] = $href;

        $this->realtime->notification(
            $user,
            $item,
            $this->announcements->unreadInAppCount($user),
        );
    }

    private function summary(User $actor, string $action, ?Model $subject, string $reason, bool $executed): string
    {
        $target = $subject?->getKey() ? class_basename($subject).' #'.$subject->getKey() : 'item';

        return "{$actor->name} ".($executed ? 'completed' : 'requested')." {$action} on {$target}: {$reason}";
    }

    public function pendingCountFor(User $user): int
    {
        if (! Schema::hasTable('admin_approvals')) {
            return 0;
        }

        return AdminApproval::query()
            ->where('requested_by_user_id', $user->id)
            ->where('status', AdminApproval::STATUS_PENDING)
            ->count();
    }

    /**
     * @return array{pending: list<array<string, mixed>>, recent: list<array<string, mixed>>, pending_count: int}
     */
    public function forRequester(User $user): array
    {
        if (! Schema::hasTable('admin_approvals')) {
            return [
                'pending' => [],
                'recent' => [],
                'pending_count' => 0,
            ];
        }

        $pending = AdminApproval::query()
            ->with(['reviewer:id,name', 'subject'])
            ->where('requested_by_user_id', $user->id)
            ->where('status', AdminApproval::STATUS_PENDING)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (AdminApproval $approval) => $this->present($approval));

        $recent = AdminApproval::query()
            ->with(['reviewer:id,name', 'subject'])
            ->where('requested_by_user_id', $user->id)
            ->whereIn('status', [AdminApproval::STATUS_APPROVED, AdminApproval::STATUS_REJECTED])
            ->latest('reviewed_at')
            ->limit(40)
            ->get()
            ->map(fn (AdminApproval $approval) => $this->present($approval));

        return [
            'pending' => $pending->values()->all(),
            'recent' => $recent->values()->all(),
            'pending_count' => $this->pendingCountFor($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function present(AdminApproval $approval): array
    {
        $approval->loadMissing(['requester:id,name,email', 'reviewer:id,name', 'subject']);
        $timezone = (string) config('app.display_timezone', config('app.timezone'));

        return [
            'uid' => $approval->uid,
            'action' => $approval->action,
            'action_label' => $this->actionLabel($approval->action),
            'subject_label' => $this->subjectSummary($approval),
            'subject_href' => $this->subjectHref($approval),
            'reason' => $approval->reason,
            'status' => $approval->status,
            'status_label' => $this->statusLabel($approval->status),
            'requester' => $approval->requester?->only(['id', 'name', 'email']),
            'reviewer' => $approval->reviewer?->only(['id', 'name']),
            'review_note' => $approval->review_note,
            'when' => $approval->created_at?->timezone($timezone)->diffForHumans(),
            'reviewed_when' => $approval->reviewed_at?->timezone($timezone)->diffForHumans(),
            'submitted_at' => $approval->created_at?->timezone($timezone)->format('j M Y · g:ia'),
            'reviewed_at' => $approval->reviewed_at?->timezone($timezone)->format('j M Y · g:ia'),
            'payload' => $approval->payload,
        ];
    }

    public function actionLabel(string $action): string
    {
        return match ($action) {
            'users.suspend' => 'Suspend user',
            'users.reinstate' => 'Reinstate user',
            'users.delete' => 'Delete user',
            'jobs.hide' => 'Hide job log',
            'jobs.remove' => 'Delete job log',
            'reviews.hide' => 'Hide review',
            'reviews.remove' => 'Delete review',
            'messages.send' => 'Send templated message',
            default => Str::headline(str_replace('.', ' ', $action)),
        };
    }

    public function subjectSummary(AdminApproval $approval): ?string
    {
        $subject = $approval->subject;

        if ($subject instanceof User) {
            return $subject->displayBusinessName() ?: $subject->email;
        }

        if ($subject instanceof WorkLog) {
            return 'Job '.$subject->uid;
        }

        if ($subject instanceof Review) {
            return 'Review from '.($subject->client_display_name ?: 'client');
        }

        $payload = is_array($approval->payload) ? $approval->payload : [];

        if (in_array($approval->action, ['users.delete', 'users.suspend', 'users.reinstate'], true) && isset($payload['user_id'])) {
            $user = User::query()->find($payload['user_id']);

            return $user?->displayBusinessName() ?: $user?->email ?: 'User #'.$payload['user_id'];
        }

        return match ($approval->action) {
            'jobs.remove', 'jobs.hide' => isset($payload['work_log_uid'])
                ? 'Job '.$payload['work_log_uid']
                : null,
            'reviews.remove', 'reviews.hide' => isset($payload['review_uid'])
                ? 'Review '.$payload['review_uid']
                : null,
            default => null,
        };
    }

    public function subjectHref(AdminApproval $approval): ?string
    {
        $payload = is_array($approval->payload) ? $approval->payload : [];

        if (in_array($approval->action, ['users.delete', 'users.suspend', 'users.reinstate'], true) && isset($payload['user_id'])) {
            $user = User::query()->find($payload['user_id']);
            if ($user instanceof User) {
                return route('admin.users.show', $user);
            }
        }

        return match ($approval->action) {
            'jobs.remove', 'jobs.hide' => isset($payload['work_log_uid'])
                ? route('admin.jobs.index', ['job' => $payload['work_log_uid']])
                : null,
            'reviews.remove', 'reviews.hide' => isset($payload['review_uid'])
                ? route('admin.reviews.index', ['review' => $payload['review_uid']])
                : null,
            default => null,
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            AdminApproval::STATUS_PENDING => 'Awaiting Super Admin',
            AdminApproval::STATUS_APPROVED => 'Approved',
            AdminApproval::STATUS_REJECTED => 'Rejected',
            AdminApproval::STATUS_CANCELLED => 'Cancelled',
            default => Str::headline($status),
        };
    }

    private function uid(): string
    {
        do {
            $uid = 'AP'.strtoupper(Str::random(10));
        } while (AdminApproval::query()->where('uid', $uid)->exists());

        return $uid;
    }
}
