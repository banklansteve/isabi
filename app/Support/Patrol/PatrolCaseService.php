<?php

namespace App\Support\Patrol;

use App\Models\PatrolCase;
use App\Models\PatrolCaseAction;
use App\Models\PatrolCaseNote;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\AdminAudit;
use App\Support\Patrol\Rules\RapidLoggingRule;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PatrolCaseService
{
    public function addNote(PatrolCase $case, User $actor, string $body): PatrolCaseNote
    {
        $note = PatrolCaseNote::query()->create([
            'patrol_case_id' => $case->id,
            'author_id' => $actor->id,
            'body' => $body,
            'created_at' => now(),
        ]);

        $this->audit($case, $actor, 'note_added', null, null, ['note_id' => $note->id]);

        return $note;
    }

    public function startReview(PatrolCase $case, User $actor): PatrolCase
    {
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');

        return $this->setStatus($case, $actor, PatrolCase::STATUS_IN_REVIEW, 'Moved to in review.');
    }

    public function recommend(PatrolCase $case, User $actor, string $outcome, string $reason): PatrolCase
    {
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');
        abort_unless(array_key_exists($outcome, $case->recommendationOptions()), 422);

        return DB::transaction(function () use ($case, $actor, $outcome, $reason) {
            $before = $this->snapshot($case);

            $case->forceFill([
                'status' => PatrolCase::STATUS_PENDING_APPROVAL,
                'recommended_outcome' => $outcome,
            ])->save();

            $this->recordAction($case, $actor, 'recommended_'.$outcome, $reason, $before, $this->snapshot($case));
            $this->audit($case, $actor, 'recommended_'.$outcome, $reason, $before, $this->snapshot($case));

            return $case->fresh() ?? $case;
        });
    }

    public function dismiss(PatrolCase $case, User $actor, string $reason): PatrolCase
    {
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');
        abort_unless($this->canDismiss($actor, $case), 403, 'You do not have permission to dismiss this case.');

        return DB::transaction(function () use ($case, $actor, $reason) {
            $before = $this->snapshot($case);
            $this->restoreAutoHiddenSubject($case);

            $case->forceFill([
                'status' => PatrolCase::STATUS_RESOLVED_DISMISSED,
                'recommended_outcome' => null,
                'resolved_at' => now(),
                'resolved_by' => $actor->id,
            ])->save();

            $after = $this->snapshot($case);
            $this->recordAction($case, $actor, 'dismissed', $reason, $before, $after);
            $this->audit($case, $actor, 'dismissed', $reason, $before, $after);

            return $case->fresh() ?? $case;
        });
    }

    public function remove(PatrolCase $case, User $actor, string $reason): PatrolCase
    {
        abort_unless($actor->canDo('patrol.resolve'), 403);
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');

        return DB::transaction(function () use ($case, $actor, $reason) {
            $before = $this->snapshot($case);
            $this->softRemoveSubject($case);

            $case->forceFill([
                'status' => PatrolCase::STATUS_RESOLVED_ACTIONED,
                'recommended_outcome' => 'remove',
                'resolved_at' => now(),
                'resolved_by' => $actor->id,
            ])->save();

            $after = $this->snapshot($case);
            $this->recordAction($case, $actor, 'removed', $reason, $before, $after);
            $this->audit($case, $actor, 'removed', $reason, $before, $after);

            return $case->fresh() ?? $case;
        });
    }

    public function handoff(PatrolCase $case, User $actor, string $reason): PatrolCase
    {
        abort_unless($actor->canDo('patrol.resolve'), 403);
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');

        return DB::transaction(function () use ($case, $actor, $reason) {
            $before = $this->snapshot($case);

            $case->forceFill([
                'status' => PatrolCase::STATUS_RESOLVED_ACTIONED,
                'recommended_outcome' => 'suspend',
                'resolved_at' => now(),
                'resolved_by' => $actor->id,
            ])->save();

            $after = $this->snapshot($case);
            $this->recordAction($case, $actor, 'handoff_suspend', $reason, $before, $after);
            $this->audit($case, $actor, 'handoff_suspend', $reason, $before, $after);

            return $case->fresh() ?? $case;
        });
    }

    public function hide(PatrolCase $case, User $actor, string $reason): PatrolCase
    {
        abort_unless($actor->canDo('patrol.resolve'), 403);
        abort_unless($case->isReview(), 422, 'Only review cases can be hidden this way.');
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');

        return DB::transaction(function () use ($case, $actor, $reason) {
            $before = $this->snapshot($case);
            $review = $case->review;

            if ($review && ! $review->removed_at && ! $review->hidden_at) {
                $review->forceFill([
                    'hidden_at' => now(),
                    'hidden_reason' => 'patrol_hide',
                ])->save();
            }

            $case->forceFill([
                'status' => PatrolCase::STATUS_RESOLVED_ACTIONED,
                'recommended_outcome' => 'hide',
                'resolved_at' => now(),
                'resolved_by' => $actor->id,
            ])->save();

            $after = $this->snapshot($case);
            $this->recordAction($case, $actor, 'hidden', $reason, $before, $after);
            $this->audit($case, $actor, 'hidden', $reason, $before, $after);

            return $case->fresh() ?? $case;
        });
    }

    public function approve(PatrolCase $case, User $actor, string $reason): PatrolCase
    {
        abort_unless($actor->canDo('patrol.resolve'), 403);
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');
        abort_unless($case->status === PatrolCase::STATUS_PENDING_APPROVAL, 422, 'This case is not pending approval.');

        $outcome = (string) $case->recommended_outcome;
        abort_unless(array_key_exists($outcome, $case->recommendationOptions()), 422, 'This recommendation cannot be applied.');

        return match ($outcome) {
            'dismiss' => $this->dismiss($case, $actor, $reason),
            'remove' => $this->remove($case, $actor, $reason),
            'hide' => $this->hide($case, $actor, $reason),
            'suspend' => $this->handoff($case, $actor, $reason),
            default => abort(422, 'This recommendation cannot be applied.'),
        };
    }

    public function reject(PatrolCase $case, User $actor, string $reason): PatrolCase
    {
        abort_unless($actor->canDo('patrol.resolve'), 403);
        abort_unless($case->isOpen(), 422, 'This case is already resolved.');
        abort_unless($case->status === PatrolCase::STATUS_PENDING_APPROVAL, 422, 'This case is not pending approval.');

        return DB::transaction(function () use ($case, $actor, $reason) {
            $before = $this->snapshot($case);

            $case->forceFill([
                'status' => PatrolCase::STATUS_IN_REVIEW,
                'recommended_outcome' => null,
            ])->save();

            $after = $this->snapshot($case);
            $this->recordAction($case, $actor, 'recommendation_rejected', $reason, $before, $after);
            $this->audit($case, $actor, 'recommendation_rejected', $reason, $before, $after);

            return $case->fresh() ?? $case;
        });
    }

    public function canDismiss(User $actor, PatrolCase $case): bool
    {
        if ($actor->canDo('patrol.resolve')) {
            return true;
        }

        return $actor->canDo('patrol.investigate') && PatrolSeverity::isLow($case->severity);
    }

    /**
     * Drop stale rapid_logging attachments after the rule heuristic changed.
     * Rapid-logging-only cases are dismissed; mixed cases keep other rules.
     * Cases are never deleted.
     *
     * @return array{dismissed: int, detached: int, restored: int}
     */
    public function reviseStaleRapidLogging(): array
    {
        $reason = 'rule revised: historical bulk create';
        $dismissed = 0;
        $detached = 0;
        $restored = 0;
        $rule = app(RapidLoggingRule::class);

        $cases = PatrolCase::query()
            ->jobs()
            ->open()
            ->whereHas('rules', fn ($query) => $query->where('rule_key', 'rapid_logging'))
            ->with(['rules', 'workLog'])
            ->get();

        foreach ($cases as $case) {
            $log = $case->workLog;
            if (! $log) {
                continue;
            }

            if ($rule->evaluate($log) !== null) {
                continue;
            }

            $stats = DB::transaction(function () use ($case, $log, $reason) {
                $before = $this->snapshot($case);
                $remaining = $case->rules()->where('rule_key', '!=', 'rapid_logging')->get();
                $restored = 0;

                if ($remaining->isEmpty()) {
                    $restored = $this->restorePatrolAutoHide($case, $log);
                    $case->forceFill([
                        'status' => PatrolCase::STATUS_RESOLVED_DISMISSED,
                        'recommended_outcome' => null,
                        'resolved_at' => now(),
                        'resolved_by' => null,
                    ])->save();

                    $after = $this->snapshot($case->fresh(['workLog']) ?? $case);
                    $this->recordAction($case, null, 'dismissed', $reason, $before, $after);
                    $this->audit($case, null, 'dismissed', $reason, $before, $after);

                    return ['dismissed' => 1, 'detached' => 0, 'restored' => $restored];
                }

                $case->rules()->where('rule_key', 'rapid_logging')->delete();
                $case->severity = PatrolSeverity::max($remaining->pluck('severity'));
                $case->save();

                if (! PatrolSeverity::isHigh($case->severity)) {
                    $restored = $this->restorePatrolAutoHide($case, $log);
                }

                $after = $this->snapshot($case->fresh(['workLog']) ?? $case);
                $this->recordAction($case, null, 'rule_detached', $reason, $before, $after);
                $this->audit($case, null, 'rule_detached', $reason, $before, $after);

                return ['dismissed' => 0, 'detached' => 1, 'restored' => $restored];
            });

            $dismissed += $stats['dismissed'];
            $detached += $stats['detached'];
            $restored += $stats['restored'];
        }

        return [
            'dismissed' => $dismissed,
            'detached' => $detached,
            'restored' => $restored,
        ];
    }

    private function restoreAutoHiddenSubject(PatrolCase $case): void
    {
        if ($case->isReview()) {
            $review = $case->review;
            if ($review && $case->auto_hidden_at && $review->hidden_reason === 'patrol_auto' && ! $review->removed_at) {
                $review->forceFill([
                    'hidden_at' => null,
                    'hidden_reason' => null,
                ])->save();
            }

            return;
        }

        $log = $case->workLog;
        if ($log) {
            $this->restorePatrolAutoHide($case, $log);
        }
    }

    private function softRemoveSubject(PatrolCase $case): void
    {
        if ($case->isReview()) {
            $review = $case->review;
            if ($review && ! $review->removed_at) {
                $review->forceFill([
                    'hidden_at' => $review->hidden_at ?? now(),
                    'hidden_reason' => 'patrol_remove',
                    'removed_at' => now(),
                ])->save();
            }

            return;
        }

        $log = $case->workLog;
        if ($log && ! $log->removed_at) {
            $log->forceFill([
                'hidden_at' => $log->hidden_at ?? now(),
                'hidden_reason' => 'patrol_remove',
                'removed_at' => now(),
            ])->save();
        }
    }

    private function restorePatrolAutoHide(PatrolCase $case, WorkLog $log): int
    {
        if (! $case->auto_hidden_at || $log->hidden_reason !== 'patrol_auto' || $log->removed_at) {
            return 0;
        }

        $log->forceFill([
            'hidden_at' => null,
            'hidden_reason' => null,
        ])->save();

        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(PatrolCase $case): array
    {
        $log = $case->relationLoaded('workLog') ? $case->workLog : $case->workLog()->first();
        $review = $case->relationLoaded('review') ? $case->review : $case->review()->first();

        return [
            'kind' => $case->kind ?: PatrolCase::KIND_JOB,
            'status' => $case->status,
            'severity' => $case->severity,
            'recommended_outcome' => $case->recommended_outcome,
            'auto_hidden_at' => $case->auto_hidden_at?->toIso8601String(),
            'work_log_hidden_at' => $log?->hidden_at?->toIso8601String(),
            'work_log_removed_at' => $log?->removed_at?->toIso8601String(),
            'review_hidden_at' => $review?->hidden_at?->toIso8601String(),
            'review_removed_at' => $review?->removed_at?->toIso8601String(),
            'hidden_reason' => $review?->hidden_reason ?? $log?->hidden_reason,
        ];
    }

    private function setStatus(PatrolCase $case, User $actor, string $status, string $reason): PatrolCase
    {
        if (! array_key_exists($status, config('patrol.statuses', []))) {
            throw new InvalidArgumentException("Unknown patrol status [{$status}].");
        }

        return DB::transaction(function () use ($case, $actor, $status, $reason) {
            $before = $this->snapshot($case);
            $case->forceFill(['status' => $status])->save();
            $after = $this->snapshot($case);
            $this->recordAction($case, $actor, 'status_changed', $reason, $before, $after);
            $this->audit($case, $actor, 'status_changed', $reason, $before, $after);

            return $case->fresh() ?? $case;
        });
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordAction(
        PatrolCase $case,
        ?User $actor,
        string $action,
        ?string $reason,
        ?array $before,
        ?array $after,
    ): void {
        PatrolCaseAction::query()->create([
            'patrol_case_id' => $case->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(
        PatrolCase $case,
        ?User $actor,
        string $action,
        ?string $reason,
        ?array $before,
        ?array $after,
    ): void {
        $actorName = $actor?->name ?? 'System';

        AdminAudit::record(
            'patrol.'.$action,
            trim("{$actorName} {$action} patrol case #{$case->id}".($reason ? ": {$reason}" : '.')),
            $case,
            $before,
            $after,
            $actor,
        );
    }
}
