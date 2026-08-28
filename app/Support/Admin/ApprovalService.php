<?php

namespace App\Support\Admin;

use App\Models\AdminApproval;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\User;
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
        'users.reinstate',
        'jobs.hide',
        'jobs.remove',
        'reviews.hide',
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

    private function notifySuperAdmins(AdminApproval $approval): void
    {
        $supers = User::query()->where('role', \App\Enums\UserRole::SuperAdmin)->get();
        $approval->loadMissing('requester');

        foreach ($supers as $super) {
            $this->pushInApp(
                $super,
                'Approval needed',
                "{$approval->requester?->name} requested {$this->actionLabel($approval->action)}.",
                route('admin.approvals.index'),
            );
        }
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
            route('admin.approvals.index'),
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

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'users.suspend' => 'user suspension',
            'users.reinstate' => 'user reinstatement',
            'jobs.hide' => 'hiding a job log',
            'jobs.remove' => 'removing a job log',
            'reviews.hide' => 'hiding a review',
            'reviews.remove' => 'removing a review',
            'messages.send' => 'sending a templated message',
            default => str_replace('.', ' ', $action),
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
