<?php

namespace App\Support\Admin;

use App\Models\Announcement;
use App\Models\PatrolCase;
use App\Models\Review;
use App\Models\StaffCaseReferral;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkLog;
use App\Enums\UserRole;
use App\Enums\StaffStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffCaseReferralService
{
    public function __construct(
        private readonly AnnouncementService $announcements,
    ) {}

    /**
     * @return list<array{id: int, name: string}>
     */
    public function staffOptions(?User $except = null, bool $opsOnly = true): array
    {
        return User::query()
            ->staff()
            ->where('staff_status', StaffStatus::Active)
            ->when($opsOnly, fn ($q) => $q->where('role', '!=', UserRole::SuperAdmin))
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name ?: $user->email,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve a subject from type + public uid (or numeric id for patrol).
     *
     * @return array{0: string, 1: Model}
     */
    public function resolveSubject(string $subjectType, string $subjectUid): array
    {
        $type = Str::lower(trim($subjectType));
        $uid = trim($subjectUid);

        if ($uid === '' || ! in_array($type, StaffCaseReferral::SUBJECT_TYPES, true)) {
            throw ValidationException::withMessages([
                'subject_uid' => 'Pick a valid case to refer.',
            ]);
        }

        $model = match ($type) {
            StaffCaseReferral::SUBJECT_JOB => WorkLog::query()->where('uid', $uid)->first(),
            StaffCaseReferral::SUBJECT_REVIEW => Review::query()->where('uid', $uid)->first(),
            StaffCaseReferral::SUBJECT_SUPPORT => SupportTicket::query()->where('uid', $uid)->first(),
            StaffCaseReferral::SUBJECT_PATROL => PatrolCase::query()->find(ctype_digit($uid) ? (int) $uid : 0),
            StaffCaseReferral::SUBJECT_USER => User::query()
                ->where('id', ctype_digit($uid) ? (int) $uid : 0)
                ->where('role', UserRole::User)
                ->first(),
            default => null,
        };

        if (! $model) {
            throw ValidationException::withMessages([
                'subject_uid' => 'That case could not be found.',
            ]);
        }

        return [$type, $model];
    }

    public function canRefer(User $actor, string $subjectType): bool
    {
        return match ($subjectType) {
            StaffCaseReferral::SUBJECT_JOB,
            StaffCaseReferral::SUBJECT_REVIEW => $actor->canDo('admin.content.manage'),
            StaffCaseReferral::SUBJECT_SUPPORT => $actor->canDo('admin.support.manage'),
            StaffCaseReferral::SUBJECT_PATROL => $actor->canDo('patrol.view') || $actor->canDo('patrol.investigate'),
            default => false,
        };
    }

    public function canEscalate(User $actor, string $subjectType): bool
    {
        if (! $actor->isStaff() || $actor->isRestrictedStaff() || $actor->isSuperAdmin()) {
            return false;
        }

        return match ($subjectType) {
            StaffCaseReferral::SUBJECT_JOB,
            StaffCaseReferral::SUBJECT_REVIEW => $actor->canDo('admin.content.manage'),
            StaffCaseReferral::SUBJECT_SUPPORT => $actor->canDo('admin.support.manage'),
            StaffCaseReferral::SUBJECT_PATROL => $actor->canDo('patrol.view') || $actor->canDo('patrol.investigate'),
            StaffCaseReferral::SUBJECT_USER => $actor->canDo('admin.users.view'),
            default => false,
        };
    }

    public function escalateToSuper(User $actor, string $subjectType, Model $subject, string $note): StaffCaseReferral
    {
        if (! $this->canEscalate($actor, $subjectType)) {
            abort(403);
        }

        $note = trim($note);

        return DB::transaction(function () use ($actor, $subjectType, $subject, $note) {
            StaffCaseReferral::query()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subject->getKey())
                ->active()
                ->update([
                    'status' => StaffCaseReferral::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

            $leadSuper = User::query()
                ->where('role', UserRole::SuperAdmin)
                ->where('staff_status', StaffStatus::Active)
                ->orderBy('id')
                ->first();

            abort_unless($leadSuper !== null, 422, 'No Super Admin is available to receive escalations.');

            $referral = StaffCaseReferral::query()->create([
                'subject_type' => $subjectType,
                'subject_id' => $subject->getKey(),
                'assignee_user_id' => $leadSuper->id,
                'referred_by_user_id' => $actor->id,
                'note' => $note,
                'queue' => StaffCaseReferral::QUEUE_ESCALATION,
                'source' => StaffCaseReferral::SOURCE_ESCALATED,
                'status' => StaffCaseReferral::STATUS_ACTIVE,
                'referred_at' => now(),
            ]);

            AdminAudit::record(
                'cases.escalated',
                "{$actor->name} escalated {$subjectType} #{$subject->getKey()} to Super Admin: {$note}",
                $subject,
                null,
                [
                    'referral_id' => $referral->id,
                    'note' => $note,
                ],
            );

            $this->notifySuperAdmins($actor, $referral->fresh(['referredBy']) ?? $referral, $subject);

            return $referral->fresh(['referredBy', 'acknowledgedBy']) ?? $referral;
        });
    }

    public function acknowledgeSuperEscalation(User $super, StaffCaseReferral $referral): StaffCaseReferral
    {
        abort_unless($super->isSuperAdmin(), 403);
        abort_unless($referral->isActive() && $referral->isSuperEscalation(), 422);

        $wasNew = $referral->acknowledged_at === null;

        if ($wasNew) {
            $referral->forceFill([
                'acknowledged_at' => now(),
                'acknowledged_by_user_id' => $super->id,
            ])->save();
        }

        $fresh = $referral->fresh(['referredBy', 'acknowledgedBy']) ?? $referral;

        if ($wasNew) {
            $this->clearEscalationNotifications($super, $fresh);
            $this->notifyReferrerOfAction($super, $fresh, 'in_review');
        }

        return $fresh;
    }

    public function completeSuperEscalation(User $super, StaffCaseReferral $referral): StaffCaseReferral
    {
        abort_unless($super->isSuperAdmin(), 403);
        abort_unless($referral->isSuperEscalation(), 422);

        if (! $referral->isActive()) {
            return $referral;
        }

        $referral->forceFill([
            'status' => StaffCaseReferral::STATUS_COMPLETED,
            'completed_at' => now(),
            'acknowledged_at' => $referral->acknowledged_at ?? now(),
            'acknowledged_by_user_id' => $referral->acknowledged_by_user_id ?? $super->id,
        ])->save();

        $subject = $this->subjectModel($referral);
        AdminAudit::record(
            'cases.escalation_completed',
            "{$super->name} resolved a Super Admin escalation.",
            $subject,
            null,
            ['referral_id' => $referral->id],
            $super,
        );

        $fresh = $referral->fresh(['referredBy', 'acknowledgedBy']) ?? $referral;
        $this->clearEscalationNotifications($super, $fresh);
        $this->notifyReferrerOfAction($super, $fresh, 'resolved');

        return $fresh;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function superAdminAttentionItems(): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        return StaffCaseReferral::query()
            ->active()
            ->superEscalations()
            ->with(['referredBy:id,name,email', 'acknowledgedBy:id,name,email'])
            ->latest('referred_at')
            ->limit(50)
            ->get()
            ->map(function (StaffCaseReferral $referral) {
                $row = $this->present($referral);
                if ($row === null) {
                    return null;
                }

                $unread = $referral->acknowledged_at === null;

                return [
                    'key' => 'escalation:'.$referral->id,
                    'signature' => 'escalation:'.$referral->id.':'.($referral->referred_at?->timestamp ?? 0),
                    'referral_id' => $referral->id,
                    'urgency' => 120,
                    'sort_at' => $referral->referred_at?->timestamp ?? 0,
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'href' => $this->escalationHref($referral, $row['href']),
                    'icon' => $row['icon'],
                    'tone' => 'high',
                    'queue' => 'Super Admin',
                    'group' => 'escalations',
                    'priority' => 'high',
                    'count' => 1,
                    'unread' => $unread,
                    'acknowledged' => $referral->acknowledged_at !== null,
                    'note' => $referral->note,
                    'referrer' => $row['referrer'],
                    'meta' => [
                        'subject_type' => $referral->subject_type,
                        'subject_id' => $referral->subject_id,
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Outbound escalations for ops staff status tracking.
     *
     * @return list<array{label: string, status: string, tone: string, href: string}>
     */
    public function outboundEscalations(User $requester): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        return StaffCaseReferral::query()
            ->where('referred_by_user_id', $requester->id)
            ->superEscalations()
            ->with(['acknowledgedBy:id,name,email'])
            ->latest('referred_at')
            ->limit(8)
            ->get()
            ->map(function (StaffCaseReferral $referral) {
                $row = $this->present($referral);
                if ($row === null) {
                    return null;
                }

                return [
                    'label' => $row['title'],
                    'status' => $this->requesterStatusLabel($referral),
                    'tone' => $this->requesterStatusTone($referral),
                    'href' => $row['href'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Active escalation on a subject, if any.
     *
     * @return array<string, mixed>|null
     */
    public function escalationBlockFor(string $subjectType, int $subjectId): ?array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return null;
        }

        $referral = StaffCaseReferral::query()
            ->active()
            ->superEscalations()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with(['referredBy:id,name,email', 'acknowledgedBy:id,name,email'])
            ->latest('referred_at')
            ->first();

        if (! $referral) {
            return null;
        }

        return [
            'id' => $referral->id,
            'status' => $this->requesterStatusLabel($referral),
            'tone' => $this->requesterStatusTone($referral),
            'note' => $referral->note,
            'referred_at' => $this->stamp($referral->referred_at),
            'referred_by' => $referral->referredBy?->name ?: $referral->referredBy?->email,
            'acknowledged_at' => $this->stamp($referral->acknowledged_at),
        ];
    }

    public function defaultQueue(string $subjectType, ?Model $subject = null): string
    {
        return match ($subjectType) {
            StaffCaseReferral::SUBJECT_REVIEW => StaffCaseReferral::QUEUE_MODERATION,
            StaffCaseReferral::SUBJECT_SUPPORT => StaffCaseReferral::QUEUE_SUPPORT,
            StaffCaseReferral::SUBJECT_PATROL => StaffCaseReferral::QUEUE_PATROL,
            StaffCaseReferral::SUBJECT_JOB => ($subject instanceof WorkLog && $subject->flagged_at !== null)
                ? StaffCaseReferral::QUEUE_MODERATION
                : StaffCaseReferral::QUEUE_GENERAL,
            default => StaffCaseReferral::QUEUE_GENERAL,
        };
    }

    public function refer(
        User $actor,
        string $subjectType,
        Model $subject,
        User $assignee,
        string $note,
        ?string $queue = null,
    ): StaffCaseReferral {
        if (! $this->canRefer($actor, $subjectType)) {
            abort(403);
        }

        if (! $assignee->isStaff() || $assignee->isSuspended()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Pick an active operations staff member, not an artisan.',
            ]);
        }

        // First-line work stays with ops. Super Admin receives cases via escalate or takeover.
        if ($assignee->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Assign this to an operations staff member first. Use Escalate to Super Admin when you need them.',
            ]);
        }

        if (! $actor->isSuperAdmin() && $assignee->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Pick an operations staff member.',
            ]);
        }

        if (! $actor->isSuperAdmin() && (int) $assignee->id === (int) $actor->id) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Refer this case to another staff member.',
            ]);
        }

        $queue = $queue && in_array($queue, StaffCaseReferral::QUEUES, true)
            ? $queue
            : $this->defaultQueue($subjectType, $subject);

        if ($queue === StaffCaseReferral::QUEUE_ESCALATION) {
            $queue = $this->defaultQueue($subjectType, $subject);
        }

        $note = trim($note);

        return DB::transaction(function () use ($actor, $subjectType, $subject, $assignee, $note, $queue) {
            StaffCaseReferral::query()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subject->getKey())
                ->active()
                ->update([
                    'status' => StaffCaseReferral::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

            $referral = StaffCaseReferral::query()->create([
                'subject_type' => $subjectType,
                'subject_id' => $subject->getKey(),
                'assignee_user_id' => $assignee->id,
                'referred_by_user_id' => $actor->id,
                'note' => $note,
                'queue' => $queue,
                'source' => StaffCaseReferral::SOURCE_REFERRED,
                'status' => StaffCaseReferral::STATUS_ACTIVE,
                'referred_at' => now(),
            ]);

            $this->syncSubject($subjectType, $subject, $assignee, $actor, $note);

            AdminAudit::record(
                'cases.referred',
                "{$actor->name} referred {$subjectType} #{$subject->getKey()} to {$assignee->name}: {$note}",
                $subject,
                null,
                [
                    'referral_id' => $referral->id,
                    'assignee_id' => $assignee->id,
                    'queue' => $queue,
                    'note' => $note,
                ],
            );

            $this->notifyAssignee($actor, $assignee, $referral->fresh(['assignee', 'referredBy']) ?? $referral, $subject);
            $this->notifySuperAdminsOfReferral(
                $actor,
                $referral->fresh(['assignee', 'referredBy']) ?? $referral,
                $subject,
            );

            return $referral->fresh(['assignee', 'referredBy']) ?? $referral;
        });
    }

    /**
     * Super Admin takes an active ops case (or escalation) onto their own desk.
     */
    public function takeOver(User $super, StaffCaseReferral $referral, ?string $note = null): StaffCaseReferral
    {
        abort_unless($super->isSuperAdmin(), 403);
        abort_unless($referral->isActive(), 422, 'This case is no longer active.');

        if ((int) $referral->assignee_user_id === (int) $super->id) {
            if ($referral->isSuperEscalation() && $referral->acknowledged_at === null) {
                return $this->acknowledgeSuperEscalation($super, $referral);
            }

            return $referral->fresh(['assignee', 'referredBy', 'acknowledgedBy']) ?? $referral;
        }

        $subject = $this->subjectModel($referral);
        abort_unless($subject !== null, 404);

        $note = trim((string) ($note ?: 'Super Admin took this case over.'));

        return DB::transaction(function () use ($super, $referral, $subject, $note) {
            $wasEscalation = $referral->isSuperEscalation();

            $referral->forceFill([
                'status' => StaffCaseReferral::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->save();

            $owned = StaffCaseReferral::query()->create([
                'subject_type' => $referral->subject_type,
                'subject_id' => $referral->subject_id,
                'assignee_user_id' => $super->id,
                'referred_by_user_id' => $referral->referred_by_user_id ?: $super->id,
                'note' => $note,
                'queue' => $wasEscalation
                    ? StaffCaseReferral::QUEUE_ESCALATION
                    : ($referral->queue ?: $this->defaultQueue($referral->subject_type, $subject)),
                'source' => StaffCaseReferral::SOURCE_TAKEN_OVER,
                'status' => StaffCaseReferral::STATUS_ACTIVE,
                'referred_at' => now(),
                'acknowledged_at' => now(),
                'acknowledged_by_user_id' => $super->id,
            ]);

            $this->syncSubject($referral->subject_type, $subject, $super, $super, $note);

            AdminAudit::record(
                'cases.taken_over',
                "{$super->name} took over {$referral->subject_type} #{$referral->subject_id}.",
                $subject,
                null,
                [
                    'referral_id' => $owned->id,
                    'from_referral_id' => $referral->id,
                    'previous_assignee_id' => $referral->assignee_user_id,
                ],
                $super,
            );

            $closed = $referral->fresh(['referredBy', 'assignee']) ?? $referral;
            $this->notifyReferrerOfAction($super, $closed, 'taken_over');

            return $owned->fresh(['assignee', 'referredBy', 'acknowledgedBy']) ?? $owned;
        });
    }

    /**
     * Super Admin (or current assignee) moves an active case to another ops staff member or Super Admin.
     */
    public function reassign(User $actor, StaffCaseReferral $referral, User $assignee, string $note): StaffCaseReferral
    {
        abort_unless($referral->isActive(), 422, 'This case is no longer active.');
        abort_unless(
            $actor->isSuperAdmin() || (int) $referral->assignee_user_id === (int) $actor->id,
            403,
        );

        if (! $assignee->isStaff() || $assignee->isSuspended()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Pick an active staff member.',
            ]);
        }

        // Ops assignees may only hand to other ops — Super Admin path is escalate/takeover.
        if (! $actor->isSuperAdmin() && $assignee->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Escalate to Super Admin instead of assigning them directly.',
            ]);
        }

        if ((int) $assignee->id === (int) $referral->assignee_user_id) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Pick a different person to reassign to.',
            ]);
        }

        $subject = $this->subjectModel($referral);
        abort_unless($subject !== null, 404);

        $queue = $referral->isSuperEscalation() && $assignee->isSuperAdmin()
            ? StaffCaseReferral::QUEUE_ESCALATION
            : ($referral->queue === StaffCaseReferral::QUEUE_ESCALATION
                ? $this->defaultQueue($referral->subject_type, $subject)
                : $referral->queue);

        $note = trim($note);

        return DB::transaction(function () use ($actor, $referral, $assignee, $subject, $note, $queue) {
            $referral->forceFill([
                'status' => StaffCaseReferral::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->save();

            $owned = StaffCaseReferral::query()->create([
                'subject_type' => $referral->subject_type,
                'subject_id' => $referral->subject_id,
                'assignee_user_id' => $assignee->id,
                'referred_by_user_id' => $actor->id,
                'note' => $note,
                'queue' => $queue,
                'source' => StaffCaseReferral::SOURCE_REASSIGNED,
                'status' => StaffCaseReferral::STATUS_ACTIVE,
                'referred_at' => now(),
                'acknowledged_at' => $assignee->isSuperAdmin() ? now() : null,
                'acknowledged_by_user_id' => $assignee->isSuperAdmin() ? $actor->id : null,
            ]);

            $this->syncSubject($referral->subject_type, $subject, $assignee, $actor, $note);

            AdminAudit::record(
                'cases.reassigned',
                "{$actor->name} reassigned {$referral->subject_type} #{$referral->subject_id} to {$assignee->name}.",
                $subject,
                null,
                [
                    'referral_id' => $owned->id,
                    'from_referral_id' => $referral->id,
                    'assignee_id' => $assignee->id,
                ],
                $actor,
            );

            if (! $assignee->is($actor)) {
                $this->notifyAssignee($actor, $assignee, $owned->fresh(['assignee', 'referredBy']) ?? $owned, $subject);
            }

            $closed = $referral->fresh(['referredBy', 'assignee']) ?? $referral;
            $this->notifyReferrerOfAction($actor, $closed, 'reassigned');

            return $owned->fresh(['assignee', 'referredBy', 'acknowledgedBy']) ?? $owned;
        });
    }

    public function returnToReferrer(User $actor, StaffCaseReferral $referral, ?string $note = null): StaffCaseReferral
    {
        abort_unless($referral->isActive(), 422, 'This referral is no longer active.');
        abort_unless((int) $referral->assignee_user_id === (int) $actor->id || $actor->isSuperAdmin(), 403);

        $referrer = $referral->referredBy;
        abort_unless($referrer?->isStaff(), 422, 'The original referrer is no longer available.');

        return DB::transaction(function () use ($actor, $referral, $referrer, $note) {
            $referral->forceFill([
                'status' => StaffCaseReferral::STATUS_RETURNED,
                'returned_at' => now(),
            ])->save();

            $returnNote = filled($note)
                ? trim($note)
                : 'Returned: '.($referral->note ?: 'Please take another look.');

            $subject = $this->subjectModel($referral);
            abort_unless($subject !== null, 404);

            $this->clearSubjectAssignment($referral->subject_type, $subject);

            $returned = $this->refer(
                $actor,
                $referral->subject_type,
                $subject,
                $referrer,
                $returnNote,
                $referral->queue,
            );

            return $returned;
        });
    }

    public function complete(User $actor, StaffCaseReferral $referral): StaffCaseReferral
    {
        if ($referral->isSuperEscalation()) {
            return $this->completeSuperEscalation($actor, $referral);
        }

        abort_unless(
            (int) $referral->assignee_user_id === (int) $actor->id || $actor->isSuperAdmin(),
            403,
        );

        if (! $referral->isActive()) {
            return $referral;
        }

        $referral->forceFill([
            'status' => StaffCaseReferral::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $subject = $this->subjectModel($referral);
        AdminAudit::record(
            'cases.referral_completed',
            "{$actor->name} completed a referred {$referral->subject_type} case.",
            $subject,
            null,
            ['referral_id' => $referral->id, 'queue' => $referral->queue],
            $actor,
        );

        $fresh = $referral->fresh(['assignee', 'referredBy']) ?? $referral;
        $this->notifyReferrerOfAction($actor, $fresh, 'resolved');

        return $fresh;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inboxFor(User $assignee, ?string $queue = null, bool $includeEscalations = false): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $query = StaffCaseReferral::query()
            ->active()
            ->forAssignee($assignee)
            ->with(['assignee:id,name,email', 'referredBy:id,name,email', 'acknowledgedBy:id,name,email'])
            ->latest('referred_at');

        if (! $includeEscalations) {
            $query->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION);
        }

        if ($queue && $queue !== 'all' && in_array($queue, StaffCaseReferral::QUEUES, true)) {
            $query->where('queue', $queue);
        }

        return $query->limit(200)
            ->get()
            ->map(fn (StaffCaseReferral $referral) => $this->present($referral))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Super Admin: every active case across ops + their own desk.
     *
     * @return list<array<string, mixed>>
     */
    public function inboxForAllStaff(?string $queue = null, bool $includeEscalations = true): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $query = StaffCaseReferral::query()
            ->active()
            ->with(['assignee:id,name,email', 'referredBy:id,name,email', 'acknowledgedBy:id,name,email'])
            ->latest('referred_at');

        if (! $includeEscalations) {
            $query->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION);
        }

        if ($queue && $queue !== 'all' && in_array($queue, StaffCaseReferral::QUEUES, true)) {
            $query->where('queue', $queue);
        }

        return $query->limit(400)
            ->get()
            ->map(fn (StaffCaseReferral $referral) => $this->present($referral))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inboxForReferrer(User $referrer, ?string $queue = null): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $since = now()->subDays(14);

        $query = StaffCaseReferral::query()
            ->where('referred_by_user_id', $referrer->id)
            ->where(function ($builder) use ($since) {
                $builder->where('status', StaffCaseReferral::STATUS_ACTIVE)
                    ->orWhere(function ($inner) use ($since) {
                        $inner->where('status', StaffCaseReferral::STATUS_COMPLETED)
                            ->where('completed_at', '>=', $since);
                    })
                    ->orWhere(function ($inner) use ($since) {
                        $inner->where('status', StaffCaseReferral::STATUS_RETURNED)
                            ->where('returned_at', '>=', $since);
                    });
            })
            ->with(['assignee:id,name,email', 'referredBy:id,name,email', 'acknowledgedBy:id,name,email'])
            ->latest('referred_at');

        if ($queue && $queue !== 'all' && in_array($queue, StaffCaseReferral::QUEUES, true)) {
            $query->where('queue', $queue);
        }

        return $query->limit(200)
            ->get()
            ->map(function (StaffCaseReferral $referral) {
                $row = $this->present($referral);
                if ($row === null) {
                    return null;
                }

                $row['origin_label'] = match ($row['origin'] ?? '') {
                    StaffCaseReferral::SOURCE_ESCALATED => 'Escalated upstairs',
                    StaffCaseReferral::SOURCE_TAKEN_OVER => 'Taken over',
                    StaffCaseReferral::SOURCE_REASSIGNED => 'Reassigned',
                    default => 'You referred',
                };
                $row['viewer_status'] = $row['requester_status'] ?? null;

                return $row;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function assignedPage(User $viewer, ?string $queue = null, ?User $viewingStaff = null, string $scope = 'mine', string $desk = 'assigned'): array
    {
        $canManage = $viewer->isSuperAdmin();
        $desk = strtolower(trim($desk));
        if (! in_array($desk, ['assigned', 'referred'], true)) {
            $desk = 'assigned';
        }

        $scope = $canManage ? strtolower(trim($scope)) : 'mine';
        if (! in_array($scope, ['mine', 'all', 'staff'], true)) {
            $scope = 'mine';
        }

        if ($desk === 'referred') {
            $scope = 'mine';
            $viewingStaff = null;
        }

        if ($scope === 'staff' && (! $viewingStaff || ! $viewingStaff->isStaff() || $viewingStaff->isSuspended())) {
            $scope = 'mine';
            $viewingStaff = null;
        }

        $includeEscalations = $canManage;
        $filterQueue = in_array($queue, ['all', 'jobs'], true) ? null : $queue;

        if ($desk === 'referred') {
            $items = $this->inboxForReferrer($viewer, $filterQueue);
            $assignee = $viewer;
            $scope = 'mine';
        } elseif ($canManage && $scope === 'all') {
            $items = $this->inboxForAllStaff($filterQueue, $includeEscalations);
            $assignee = null;
        } elseif ($canManage && $scope === 'staff' && $viewingStaff) {
            $items = $this->inboxFor($viewingStaff, $filterQueue, false);
            $assignee = $viewingStaff;
        } else {
            $items = $this->inboxFor($viewer, $filterQueue, $includeEscalations);
            $assignee = $viewer;
            $scope = 'mine';
        }

        if ($queue === 'jobs') {
            $items = array_values(array_filter(
                $items,
                fn (array $item) => ($item['subject_type'] ?? '') === StaffCaseReferral::SUBJECT_JOB,
            ));
        }

        $active = StaffCaseReferral::query()->active();
        if ($desk === 'referred') {
            $active->where('referred_by_user_id', $viewer->id);
        } elseif ($scope === 'mine') {
            $active->forAssignee($viewer);
        } elseif ($scope === 'staff' && $viewingStaff) {
            $active->forAssignee($viewingStaff)->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION);
        } elseif (! $includeEscalations) {
            $active->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION);
        }

        $counts = [
            'all' => (clone $active)->when(
                $desk !== 'referred' && $scope !== 'all' && ! $includeEscalations,
                fn ($q) => $q->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION),
            )->count(),
            'moderation' => (clone $active)->where('queue', StaffCaseReferral::QUEUE_MODERATION)->count(),
            'support' => (clone $active)->where('queue', StaffCaseReferral::QUEUE_SUPPORT)->count(),
            'patrol' => (clone $active)->where('queue', StaffCaseReferral::QUEUE_PATROL)->count(),
            'jobs' => (clone $active)->where('subject_type', StaffCaseReferral::SUBJECT_JOB)->count(),
            'escalation' => (clone $active)->where('queue', StaffCaseReferral::QUEUE_ESCALATION)->count(),
            'mine' => StaffCaseReferral::query()->active()->forAssignee($viewer)->count(),
            'referred' => StaffCaseReferral::query()
                ->where('referred_by_user_id', $viewer->id)
                ->where(function ($builder) {
                    $builder->where('status', StaffCaseReferral::STATUS_ACTIVE)
                        ->orWhere(function ($inner) {
                            $inner->where('status', StaffCaseReferral::STATUS_COMPLETED)
                                ->where('completed_at', '>=', now()->subDays(14));
                        })
                        ->orWhere(function ($inner) {
                            $inner->where('status', StaffCaseReferral::STATUS_RETURNED)
                                ->where('returned_at', '>=', now()->subDays(14));
                        });
                })
                ->count(),
            'team' => StaffCaseReferral::query()
                ->active()
                ->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION)
                ->whereHas('assignee', fn ($q) => $q->where('role', '!=', UserRole::SuperAdmin))
                ->count(),
        ];

        $mineBreakdown = [
            'referred' => 0,
            'taken_over' => 0,
            'escalated' => 0,
        ];
        if ($desk === 'assigned' && ($scope === 'mine' || $canManage)) {
            foreach ($this->inboxFor($viewer, null, true) as $row) {
                $origin = $row['origin'] ?? 'referred';
                if (isset($mineBreakdown[$origin])) {
                    $mineBreakdown[$origin]++;
                }
            }
        }

        return [
            'queue' => $queue ?: 'all',
            'desk' => $desk,
            'scope' => $scope,
            'counts' => $counts,
            'mine_breakdown' => $mineBreakdown,
            'items' => $items,
            'staff' => $this->staffOptions($viewer),
            'assignable_staff' => $canManage
                ? array_values(array_merge(
                    [[
                        'id' => $viewer->id,
                        'name' => ($viewer->name ?: $viewer->email).' (me)',
                    ]],
                    $this->opsStaffOptions($viewer),
                ))
                : $this->staffOptions($viewer),
            'ops_staff' => $canManage ? $this->opsStaffOptions($viewer) : [],
            'viewing' => [
                'id' => $assignee?->id,
                'name' => $assignee ? ($assignee->name ?: $assignee->email) : 'Everyone',
                'is_self' => $assignee !== null && (int) $assignee->id === (int) $viewer->id,
                'is_super' => $assignee?->isSuperAdmin() ?? false,
                'scope' => $scope,
            ],
            'can_manage' => $canManage,
            'growth_duties' => [
                [
                    'label' => 'Job logs patrol',
                    'href' => route('admin.patrol.jobs'),
                    'ability' => 'patrol.view',
                ],
                [
                    'label' => 'Reviews patrol',
                    'href' => route('admin.patrol.reviews'),
                    'ability' => 'patrol.view',
                ],
                [
                    'label' => 'Referral monitoring',
                    'href' => route('admin.referrals.index'),
                    'ability' => 'admin.referrals.view',
                ],
                [
                    'label' => 'Onboarding follow-up',
                    'href' => route('admin.onboarding.index'),
                    'ability' => 'ops.onboarding.manage',
                ],
                [
                    'label' => 'Re-engagement outreach',
                    'href' => route('admin.reengagement.index'),
                    'ability' => 'ops.reengagement.manage',
                ],
            ],
        ];
    }

    /**
     * Active ops staff for Super Admin workload browsing (excludes Super Admins).
     *
     * @return list<array{id: int, name: string, assigned_count: int}>
     */
    public function opsStaffOptions(?User $except = null): array
    {
        $ids = User::query()
            ->staff()
            ->where('staff_status', StaffStatus::Active)
            ->where('role', '!=', UserRole::SuperAdmin)
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($ids->isEmpty()) {
            return [];
        }

        $counts = StaffCaseReferral::query()
            ->active()
            ->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION)
            ->whereIn('assignee_user_id', $ids->pluck('id'))
            ->selectRaw('assignee_user_id, COUNT(*) as total')
            ->groupBy('assignee_user_id')
            ->pluck('total', 'assignee_user_id');

        return $ids
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name ?: $user->email,
                'assigned_count' => (int) ($counts[$user->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Attention-feed rows for the assignee's active referrals.
     *
     * @return list<array<string, mixed>>
     */
    public function attentionItems(User $assignee): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        return StaffCaseReferral::query()
            ->active()
            ->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION)
            ->forAssignee($assignee)
            ->with(['referredBy:id,name,email'])
            ->latest('referred_at')
            ->limit(40)
            ->get()
            ->map(function (StaffCaseReferral $referral) {
                $row = $this->present($referral);
                if ($row === null) {
                    return null;
                }

                $isModeration = $referral->queue === StaffCaseReferral::QUEUE_MODERATION;
                $urgency = $isModeration ? 110 : 70;

                return [
                    'key' => 'referral:'.$referral->id,
                    'signature' => 'referral:'.$referral->id.':'.($referral->referred_at?->timestamp ?? 0),
                    'urgency' => $urgency,
                    'sort_at' => $referral->referred_at?->timestamp ?? 0,
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'href' => $row['href'],
                    'icon' => $row['icon'],
                    'tone' => $isModeration ? 'high' : 'medium',
                    'queue' => $row['queue_label'],
                    'group' => $isModeration ? 'moderation' : 'assigned',
                    'priority' => $isModeration ? 'high' : 'medium',
                    'count' => 1,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Notify the original referrer when someone acts on their referred case.
     *
     * @return list<array<string, mixed>>
     */
    public function referrerUpdateItems(User $referrer): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $since = now()->subDays(14);

        return StaffCaseReferral::query()
            ->where('referred_by_user_id', $referrer->id)
            ->where(function ($query) use ($since) {
                $query->where(function ($inner) use ($since) {
                    $inner->where('status', StaffCaseReferral::STATUS_COMPLETED)
                        ->where('completed_at', '>=', $since);
                })->orWhere(function ($inner) use ($since) {
                    $inner->where('status', StaffCaseReferral::STATUS_RETURNED)
                        ->where('returned_at', '>=', $since);
                })->orWhere(function ($inner) use ($since) {
                    $inner->whereNotNull('acknowledged_at')
                        ->where('acknowledged_at', '>=', $since)
                        ->whereNull('completed_at')
                        ->where('status', StaffCaseReferral::STATUS_ACTIVE);
                });
            })
            ->with([
                'assignee:id,name,email',
                'referredBy:id,name,email',
                'acknowledgedBy:id,name,email',
            ])
            ->orderByDesc('updated_at')
            ->limit(40)
            ->get()
            ->map(function (StaffCaseReferral $referral) {
                $row = $this->present($referral);
                if ($row === null) {
                    return null;
                }

                $actedAt = $referral->completed_at
                    ?? $referral->returned_at
                    ?? $referral->acknowledged_at
                    ?? $referral->updated_at;
                $status = $this->referrerUpdateStatus($referral);
                $actor = $this->referrerUpdateActor($referral);

                $href = $row['href'];
                $separator = str_contains($href, '?') ? '&' : '?';
                $href .= $separator.'referral_update='.$referral->id;

                return [
                    'key' => 'referral-update:'.$referral->id,
                    'signature' => 'referral-update:'.$referral->id.':'.$status.':'.($actedAt?->timestamp ?? 0),
                    'referral_id' => $referral->id,
                    'urgency' => 65,
                    'sort_at' => $actedAt?->timestamp ?? 0,
                    'title' => $this->referrerUpdateTitle($referral, $row['title'], $status),
                    'subtitle' => $this->referrerUpdateSubtitle($referral, $actor, $status),
                    'href' => $href,
                    'icon' => $row['icon'],
                    'tone' => 'medium',
                    'queue' => 'Your referrals',
                    'group' => 'referral_updates',
                    'priority' => 'medium',
                    'count' => 1,
                    'meta' => [
                        'subject_type' => $referral->subject_type,
                        'subject_id' => $referral->subject_id,
                        'status' => $status,
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function referrerUpdateStatus(StaffCaseReferral $referral): string
    {
        if ($referral->status === StaffCaseReferral::STATUS_RETURNED) {
            return 'returned';
        }

        if ($referral->status === StaffCaseReferral::STATUS_COMPLETED) {
            $followOn = $this->followOnSource($referral);

            return match ($followOn) {
                StaffCaseReferral::SOURCE_TAKEN_OVER => 'taken_over',
                StaffCaseReferral::SOURCE_REASSIGNED => 'reassigned',
                default => 'resolved',
            };
        }

        if ($referral->acknowledged_at !== null) {
            return 'in_review';
        }

        return 'updated';
    }

    private function followOnSource(StaffCaseReferral $referral): ?string
    {
        if (! $referral->completed_at) {
            return null;
        }

        $start = $referral->completed_at->copy()->subSeconds(5);
        $end = $referral->completed_at->copy()->addMinute();

        return StaffCaseReferral::query()
            ->where('subject_type', $referral->subject_type)
            ->where('subject_id', $referral->subject_id)
            ->where('id', '>', $referral->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('source', [
                StaffCaseReferral::SOURCE_TAKEN_OVER,
                StaffCaseReferral::SOURCE_REASSIGNED,
            ])
            ->orderBy('id')
            ->value('source');
    }

    private function referrerUpdateTitle(StaffCaseReferral $referral, string $subjectTitle, ?string $status = null): string
    {
        $status ??= $this->referrerUpdateStatus($referral);

        return match ($status) {
            'resolved' => 'Resolved: '.$subjectTitle,
            'returned' => 'Returned: '.$subjectTitle,
            'taken_over' => 'Taken over: '.$subjectTitle,
            'reassigned' => 'Reassigned: '.$subjectTitle,
            'in_review' => 'In review: '.$subjectTitle,
            default => 'Update: '.$subjectTitle,
        };
    }

    private function referrerUpdateSubtitle(StaffCaseReferral $referral, string $actor, ?string $status = null): string
    {
        $status ??= $this->referrerUpdateStatus($referral);

        return match ($status) {
            'resolved' => ($actor !== '' ? $actor.' resolved' : 'Resolved').' the case you referred',
            'returned' => ($actor !== '' ? $actor.' returned' : 'Returned').' the case to you',
            'taken_over' => ($actor !== '' ? $actor : 'Super Admin').' took over the case you referred',
            'reassigned' => ($actor !== '' ? $actor.' reassigned' : 'Reassigned').' the case you referred',
            'in_review' => ($actor !== '' ? $actor.' is reviewing' : 'Now in review').' your escalation',
            default => ($actor !== '' ? $actor.' updated' : 'Updated').' the case you referred',
        };
    }

    private function referrerUpdateActor(StaffCaseReferral $referral): string
    {
        if ($referral->status === StaffCaseReferral::STATUS_COMPLETED) {
            $followOn = $this->followOnSource($referral);

            if ($followOn === StaffCaseReferral::SOURCE_TAKEN_OVER) {
                return StaffCaseReferral::query()
                    ->where('subject_type', $referral->subject_type)
                    ->where('subject_id', $referral->subject_id)
                    ->where('source', StaffCaseReferral::SOURCE_TAKEN_OVER)
                    ->where('id', '>', $referral->id)
                    ->with('assignee:id,name,email')
                    ->orderBy('id')
                    ->first()
                    ?->assignee
                    ?->name
                    ?: 'Super Admin';
            }

            if ($referral->isSuperEscalation()) {
                return $referral->acknowledgedBy?->name
                    ?: $referral->assignee?->name
                    ?: 'Super Admin';
            }

            return $referral->assignee?->name
                ?: $referral->assignee?->email
                ?: 'Operations';
        }

        if ($referral->status === StaffCaseReferral::STATUS_RETURNED) {
            return $referral->assignee?->name
                ?: $referral->assignee?->email
                ?: 'Operations';
        }

        return $referral->acknowledgedBy?->name
            ?: $referral->acknowledgedBy?->email
            ?: 'Super Admin';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function present(StaffCaseReferral $referral): ?array
    {
        try {
            $subject = $this->subjectModel($referral);
            if ($subject === null) {
                return null;
            }

            $meta = $this->subjectMeta($referral->subject_type, $subject);
        } catch (\Throwable) {
            return null;
        }

        $referredAt = $referral->referred_at ?? $referral->created_at;
        $origin = $this->originFor($referral);
        $title = $referral->isSuperEscalation()
            ? 'Escalated: '.$meta['title']
            : $meta['title'];

        return [
            'id' => $referral->id,
            'subject_type' => $referral->subject_type,
            'subject_uid' => $meta['uid'],
            'queue' => $referral->queue,
            'queue_label' => $this->queueLabel($referral->queue),
            'source' => $referral->source ?: $origin,
            'origin' => $origin,
            'origin_label' => $this->originLabel($origin),
            'status' => $referral->status,
            'requester_status' => $this->requesterStatusLabel($referral),
            'title' => $title,
            'subtitle' => $this->subtitle($referral, $meta),
            'note' => $referral->note,
            'details' => [
                'subject_type' => $referral->subject_type,
                'subject_uid' => $meta['uid'],
                'artisan' => $meta['artisan'],
                'queue' => $this->queueLabel($referral->queue),
                'origin' => $this->originLabel($origin),
                'status' => $this->requesterStatusLabel($referral),
                'note' => $referral->note,
                'referred_at' => $this->stamp($referredAt),
                'acknowledged_at' => $this->stamp($referral->acknowledged_at),
                'referrer' => $referral->referredBy?->name ?: $referral->referredBy?->email,
                'assignee' => $referral->assignee?->name ?: $referral->assignee?->email,
                'acknowledged_by' => $referral->acknowledgedBy?->name ?: $referral->acknowledgedBy?->email,
            ],
            'href' => $referral->isSuperEscalation()
                ? $this->escalationHref($referral, $meta['href'])
                : $meta['href'],
            'icon' => $meta['icon'],
            'artisan' => $meta['artisan'],
            'referrer' => [
                'id' => $referral->referred_by_user_id,
                'name' => $referral->referredBy?->name ?: $referral->referredBy?->email,
            ],
            'assignee' => [
                'id' => $referral->assignee_user_id,
                'name' => $referral->assignee?->name ?: $referral->assignee?->email,
                'is_super' => (bool) $referral->assignee?->isSuperAdmin(),
            ],
            'acknowledged_at' => $this->stamp($referral->acknowledged_at),
            'referred_at' => $this->stamp($referredAt),
            'referred_iso' => $referredAt?->toIso8601String(),
            'age' => $referredAt ? $referredAt->diffForHumans() : null,
        ];
    }

    private function originFor(StaffCaseReferral $referral): string
    {
        if ($referral->source) {
            return $referral->source;
        }

        if ($referral->isSuperEscalation()) {
            return StaffCaseReferral::SOURCE_ESCALATED;
        }

        return StaffCaseReferral::SOURCE_REFERRED;
    }

    private function originLabel(string $origin): string
    {
        return match ($origin) {
            StaffCaseReferral::SOURCE_TAKEN_OVER => 'Taken over',
            StaffCaseReferral::SOURCE_ESCALATED => 'Escalated to you',
            StaffCaseReferral::SOURCE_REASSIGNED => 'Reassigned',
            default => 'Referred',
        };
    }

    /**
     * Compact referral block for entity drawers.
     *
     * @return array<string, mixed>|null
     */
    public function referralBlockFor(string $subjectType, int $subjectId): ?array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return null;
        }

        $referral = StaffCaseReferral::query()
            ->active()
            ->where('queue', '!=', StaffCaseReferral::QUEUE_ESCALATION)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with(['assignee:id,name,email', 'referredBy:id,name,email'])
            ->latest('referred_at')
            ->first();

        if (! $referral) {
            return null;
        }

        return [
            'id' => $referral->id,
            'assignee_id' => $referral->assignee_user_id,
            'assignee_name' => $referral->assignee?->name ?: $referral->assignee?->email,
            'note' => $referral->note,
            'queue' => $referral->queue,
            'referred_at' => $this->stamp($referral->referred_at),
            'referred_by' => $referral->referredBy?->name ?: $referral->referredBy?->email,
        ];
    }

    private function syncSubject(
        string $subjectType,
        Model $subject,
        User $assignee,
        User $actor,
        string $note,
    ): void {
        match ($subjectType) {
            StaffCaseReferral::SUBJECT_JOB => $subject->forceFill([
                'referred_to_user_id' => $assignee->id,
                'referred_by_user_id' => $actor->id,
                'referred_note' => $note,
                'referred_at' => now(),
            ])->save(),
            StaffCaseReferral::SUBJECT_REVIEW => $subject->forceFill([
                'assigned_to_user_id' => $assignee->id,
                'referred_by_user_id' => $actor->id,
                'referred_note' => $note,
                'referred_at' => now(),
            ])->save(),
            StaffCaseReferral::SUBJECT_SUPPORT => $subject->forceFill([
                'assigned_to_user_id' => $assignee->id,
            ])->save(),
            StaffCaseReferral::SUBJECT_PATROL => $subject->forceFill([
                'assigned_to' => $assignee->id,
            ])->save(),
            default => null,
        };
    }

    private function clearSubjectAssignment(string $subjectType, Model $subject): void
    {
        match ($subjectType) {
            StaffCaseReferral::SUBJECT_JOB => $subject->forceFill([
                'referred_to_user_id' => null,
                'referred_by_user_id' => null,
                'referred_note' => null,
                'referred_at' => null,
            ])->save(),
            StaffCaseReferral::SUBJECT_REVIEW => $subject->forceFill([
                'assigned_to_user_id' => null,
                'referred_by_user_id' => null,
                'referred_note' => null,
                'referred_at' => null,
            ])->save(),
            StaffCaseReferral::SUBJECT_SUPPORT => $subject->forceFill([
                'assigned_to_user_id' => null,
            ])->save(),
            StaffCaseReferral::SUBJECT_PATROL => $subject->forceFill([
                'assigned_to' => null,
            ])->save(),
            default => null,
        };
    }

    private function subjectModel(StaffCaseReferral $referral): ?Model
    {
        return match ($referral->subject_type) {
            StaffCaseReferral::SUBJECT_JOB => WorkLog::query()
                ->with(['user:id,name,email,business_name'])
                ->find($referral->subject_id),
            StaffCaseReferral::SUBJECT_REVIEW => Review::query()
                ->with(['artisan:id,name,email,business_name', 'workLog:id,uid,description'])
                ->find($referral->subject_id),
            StaffCaseReferral::SUBJECT_SUPPORT => SupportTicket::query()
                ->with(['user:id,name,email,business_name'])
                ->find($referral->subject_id),
            StaffCaseReferral::SUBJECT_PATROL => PatrolCase::query()
                ->with(['artisan:id,name,email,business_name'])
                ->find($referral->subject_id),
            StaffCaseReferral::SUBJECT_USER => User::query()
                ->find($referral->subject_id),
            default => null,
        };
    }

    /**
     * @return array{uid: string, title: string, href: string, icon: string, artisan: ?string}
     */
    private function subjectMeta(string $subjectType, Model $subject): array
    {
        return match ($subjectType) {
            StaffCaseReferral::SUBJECT_JOB => [
                'uid' => (string) ($subject->uid ?? $subject->getKey()),
                'title' => 'Job log',
                'href' => route('admin.jobs.index', ['job' => $subject->uid]),
                'icon' => 'ti ti-briefcase',
                'artisan' => $subject->user?->displayBusinessName() ?? $subject->user?->name,
            ],
            StaffCaseReferral::SUBJECT_REVIEW => [
                'uid' => (string) ($subject->uid ?? $subject->getKey()),
                'title' => $subject->flagged_at ? 'Flagged review' : 'Review',
                'href' => route('admin.reviews.index', ['review' => $subject->uid]),
                'icon' => 'ti ti-shield-check',
                'artisan' => $subject->artisan?->displayBusinessName() ?? $subject->artisan?->name,
            ],
            StaffCaseReferral::SUBJECT_SUPPORT => [
                'uid' => (string) ($subject->uid ?? $subject->getKey()),
                'title' => $subject->subject ?: 'Support chat',
                'href' => route('admin.support.show', $subject),
                'icon' => 'ti ti-headset',
                'artisan' => $subject->user?->displayBusinessName() ?? $subject->user?->name,
            ],
            StaffCaseReferral::SUBJECT_PATROL => [
                'uid' => (string) $subject->getKey(),
                'title' => 'Patrol case #'.$subject->getKey(),
                'href' => route('admin.patrol.show', $subject),
                'icon' => 'ti ti-binoculars',
                'artisan' => $subject->artisan?->displayBusinessName() ?? $subject->artisan?->name,
            ],
            StaffCaseReferral::SUBJECT_USER => [
                'uid' => (string) $subject->getKey(),
                'title' => ($subject->displayBusinessName() ?: $subject->name ?: 'User').' profile',
                'href' => route('admin.users.show', $subject),
                'icon' => 'ti ti-user-exclamation',
                'artisan' => $subject->displayBusinessName() ?? $subject->name,
            ],
            default => [
                'uid' => (string) $subject->getKey(),
                'title' => 'Case',
                'href' => route('admin.assigned.index'),
                'icon' => 'ti ti-transfer',
                'artisan' => null,
            ],
        };
    }

    /**
     * @param  array{artisan: ?string}  $meta
     */
    private function subtitle(StaffCaseReferral $referral, array $meta): string
    {
        $parts = array_values(array_filter([
            $meta['artisan'] ? 'Artisan: '.$meta['artisan'] : null,
            $referral->referredBy
                ? 'From '.($referral->referredBy->name ?: $referral->referredBy->email)
                : null,
            $referral->note ? Str::limit($referral->note, 80) : null,
        ]));

        return $parts === [] ? $this->queueLabel($referral->queue) : implode(' · ', $parts);
    }

    private function queueLabel(string $queue): string
    {
        return match ($queue) {
            StaffCaseReferral::QUEUE_MODERATION => 'Moderation',
            StaffCaseReferral::QUEUE_SUPPORT => 'Support',
            StaffCaseReferral::QUEUE_PATROL => 'Patrol',
            StaffCaseReferral::QUEUE_ESCALATION => 'Super Admin',
            default => 'General',
        };
    }

    private function requesterStatusLabel(StaffCaseReferral $referral): string
    {
        if ($referral->status === StaffCaseReferral::STATUS_COMPLETED) {
            return 'Resolved';
        }

        if ($referral->acknowledged_at !== null) {
            return 'In review';
        }

        return 'Pending';
    }

    private function requesterStatusTone(StaffCaseReferral $referral): string
    {
        if ($referral->status === StaffCaseReferral::STATUS_COMPLETED) {
            return 'resolved';
        }

        if ($referral->acknowledged_at !== null) {
            return 'review';
        }

        return 'pending';
    }

    private function escalationHref(StaffCaseReferral $referral, string $baseHref): string
    {
        $separator = str_contains($baseHref, '?') ? '&' : '?';

        return $baseHref.$separator.'escalation='.$referral->id;
    }

    public function notifySuperAdminsOfFlag(User $actor, string $subjectType, Model $subject, string $reason): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if (! in_array($subjectType, [StaffCaseReferral::SUBJECT_JOB, StaffCaseReferral::SUBJECT_REVIEW], true)) {
            return;
        }

        $meta = $this->subjectMeta($subjectType, $subject);
        $href = $subjectType === StaffCaseReferral::SUBJECT_JOB
            ? route('admin.jobs.index', ['job' => $meta['uid'], 'tab' => 'flagged'])
            : route('admin.reviews.index', ['review' => $meta['uid'], 'tab' => 'flagged']);

        $this->notifySuperAdminsModerationEvent(
            $actor,
            'staff_flag',
            'Flagged: '.$meta['title'],
            trim(implode("\n\n", array_filter([
                "{$actor->name} flagged a {$subjectType} for moderation.",
                "Reason: {$reason}",
                $meta['artisan'] ? "Artisan: {$meta['artisan']}" : null,
            ]))),
            $href,
            ['subject_type' => $subjectType, 'subject_uid' => $meta['uid']],
        );
    }

    public function notifySuperAdminsOfReferral(User $actor, StaffCaseReferral $referral, Model $subject): void
    {
        if ($actor->isSuperAdmin() || $referral->queue !== StaffCaseReferral::QUEUE_MODERATION) {
            return;
        }

        $meta = $this->subjectMeta($referral->subject_type, $subject);
        $assignee = $referral->assignee?->name ?: $referral->assignee?->email ?: 'operations staff';

        $this->notifySuperAdminsModerationEvent(
            $actor,
            'staff_referral',
            'Referred for moderation: '.$meta['title'],
            trim(implode("\n\n", array_filter([
                "{$actor->name} referred a {$referral->subject_type} to {$assignee}.",
                $referral->note ? "Note: {$referral->note}" : null,
                $meta['artisan'] ? "Artisan: {$meta['artisan']}" : null,
            ]))),
            $meta['href'],
            ['referral_id' => $referral->id, 'subject_type' => $referral->subject_type],
        );
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private function notifySuperAdminsModerationEvent(
        User $actor,
        string $kind,
        string $subjectLine,
        string $body,
        string $href,
        array $segment = [],
    ): void {
        $superIds = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('staff_status', StaffStatus::Active)
            ->pluck('id')
            ->all();

        if ($superIds === []) {
            return;
        }

        $this->announcements->sendToStaff(
            $actor,
            $superIds,
            $subjectLine,
            $body,
            [Announcement::CHANNEL_IN_APP],
            null,
            array_merge(['kind' => $kind, 'href' => $href], $segment),
        );
    }

    private function notifySuperAdmins(User $actor, StaffCaseReferral $referral, Model $subject): void
    {
        $superIds = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('staff_status', StaffStatus::Active)
            ->pluck('id')
            ->all();

        if ($superIds === []) {
            return;
        }

        $meta = $this->subjectMeta($referral->subject_type, $subject);
        $href = $this->escalationHref($referral, $meta['href']);
        $subjectLine = 'Escalation: '.$meta['title'];
        $body = trim(implode("\n\n", array_filter([
            "{$actor->name} escalated a {$referral->subject_type} to Super Admin.",
            $referral->note ? "Context: {$referral->note}" : null,
            $meta['artisan'] ? "Artisan: {$meta['artisan']}" : null,
        ])));

        $this->announcements->sendToStaff(
            $actor,
            $superIds,
            $subjectLine,
            $body,
            [Announcement::CHANNEL_IN_APP],
            null,
            [
                'kind' => 'staff_escalation',
                'href' => $href,
                'referral_id' => $referral->id,
                'subject_type' => $referral->subject_type,
                'subject_id' => $referral->subject_id,
            ],
        );
    }

    private function notifyAssignee(
        User $actor,
        User $assignee,
        StaffCaseReferral $referral,
        Model $subject,
    ): void {
        $meta = $this->subjectMeta($referral->subject_type, $subject);
        $queueLabel = $this->queueLabel($referral->queue);
        $subjectLine = "{$queueLabel}: case assigned to you";
        $body = trim(implode("\n\n", array_filter([
            "{$actor->name} referred a {$referral->subject_type} to you.",
            $referral->note ? "Note: {$referral->note}" : null,
            'Open it here: '.$meta['href'],
        ])));

        $this->announcements->sendToStaff(
            $actor,
            [$assignee->id],
            $subjectLine,
            $body,
            [Announcement::CHANNEL_IN_APP],
            null,
            [
                'kind' => 'staff_referral',
                'href' => $meta['href'],
                'referral_id' => $referral->id,
            ],
        );
    }

    private function notifyReferrerOfAction(User $actor, StaffCaseReferral $referral, string $action): void
    {
        $referrerId = (int) $referral->referred_by_user_id;

        if ($referrerId <= 0 || $referrerId === (int) $actor->id) {
            return;
        }

        $referrer = User::query()->find($referrerId);

        if (! $referrer?->isStaff() || $referrer->isSuspended()) {
            return;
        }

        $subject = $this->subjectModel($referral);

        if (! $subject) {
            return;
        }

        $meta = $this->subjectMeta($referral->subject_type, $subject);
        $href = $meta['href'];
        $separator = str_contains($href, '?') ? '&' : '?';
        $href .= $separator.'referral_update='.$referral->id;

        $actorName = $actor->name ?: $actor->email ?: 'Staff';
        $title = match ($action) {
            'resolved' => 'Resolved: '.$meta['title'],
            'in_review' => 'In review: '.$meta['title'],
            'taken_over' => 'Taken over: '.$meta['title'],
            'reassigned' => 'Reassigned: '.$meta['title'],
            'returned' => 'Returned: '.$meta['title'],
            default => 'Update: '.$meta['title'],
        };
        $body = match ($action) {
            'resolved' => "{$actorName} resolved the case you referred.",
            'in_review' => "{$actorName} is reviewing your escalation.",
            'taken_over' => "{$actorName} took over the case you referred.",
            'reassigned' => "{$actorName} reassigned the case you referred.",
            'returned' => "{$actorName} returned the case to you.",
            default => "{$actorName} updated the case you referred.",
        };

        $this->announcements->sendToStaff(
            $actor,
            [$referrerId],
            $title,
            $body,
            [Announcement::CHANNEL_IN_APP],
            null,
            [
                'kind' => 'staff_referral_update',
                'href' => $href,
                'referral_id' => $referral->id,
                'action' => $action,
                'subject_type' => $referral->subject_type,
                'subject_id' => $referral->subject_id,
            ],
        );
    }

    private function clearEscalationNotifications(User $super, StaffCaseReferral $referral): void
    {
        app(OpsAttentionFeed::class)->markRead(
            $super,
            'escalation:'.$referral->id,
            'escalation:'.$referral->id.':'.($referral->referred_at?->timestamp ?? 0),
        );

        app(OpsAttentionFeed::class)->markReferrerUpdatesOpened(
            $super,
            $referral->subject_type,
            (int) $referral->subject_id,
            (int) $referral->id,
        );
    }

    private function stamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $carbon = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $carbon->timezone(config('app.display_timezone'))->format('j M Y · g:ia');
    }
}
