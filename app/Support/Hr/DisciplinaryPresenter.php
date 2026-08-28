<?php

namespace App\Support\Hr;

use App\Models\DisciplinaryAction;
use App\Models\DisciplinaryAppeal;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryCaseEvent;
use App\Models\DisciplinaryEvidence;
use App\Models\DisciplinaryLetterTemplate;
use App\Models\DisciplinaryNote;
use App\Models\User;
use Illuminate\Support\Collection;

class DisciplinaryPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function caseRow(DisciplinaryCase $case): array
    {
        return [
            'id' => $case->id,
            'reference' => $case->reference,
            'staff_id' => $case->user_id,
            'staff_name' => $case->staff?->name ?: $case->staff?->email,
            'staff_initials' => $case->staff ? self::initials($case->staff) : '—',
            'owner_name' => $case->owner?->name,
            'category' => $case->category,
            'category_label' => $case->categoryLabel(),
            'severity' => $case->severity,
            'severity_label' => $case->severityLabel(),
            'status' => $case->status,
            'status_label' => $case->statusLabel(),
            'incident_on' => $case->incident_on->toDateString(),
            'incident_label' => $case->incident_on->format('j M Y'),
            'opened_label' => $case->created_at?->format('j M Y'),
            'updated_label' => $case->updated_at?->format('j M Y'),
            'is_open' => $case->isOpen(),
            'is_archived' => $case->isArchived(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function caseDetail(DisciplinaryCase $case, User $actor): array
    {
        $canConfidential = $actor->isSuperAdmin();
        $latest = $case->latestAction();

        return [
            ...self::caseRow($case),
            'description' => $case->description,
            'owner_id' => $case->owner_id,
            'opened_by_name' => $case->opener?->name,
            'allowed_transitions' => collect($case->allowedTransitions())
                ->map(fn (string $status) => [
                    'value' => $status,
                    'label' => config('discipline.statuses')[$status] ?? $status,
                ])
                ->values(),
            'can_issue_action' => $case->status === DisciplinaryCase::STATUS_DECISION_PENDING && ! $case->isArchived(),
            'can_raise_appeal' => $case->status === DisciplinaryCase::STATUS_ACTION_ISSUED && ! $case->isArchived(),
            'can_close' => in_array($case->status, [DisciplinaryCase::STATUS_ACTION_ISSUED, DisciplinaryCase::STATUS_RESOLVED], true),
            'can_archive' => in_array($case->status, [DisciplinaryCase::STATUS_RESOLVED, DisciplinaryCase::STATUS_CLOSED], true) && ! $case->isArchived(),
            'needs_access_followup' => $latest?->isSerious() ?? false,
            'latest_action' => $latest ? self::actionDetail($latest) : null,
            'actions' => $case->actions
                ->sortBy('issued_at')
                ->map(fn (DisciplinaryAction $action) => self::actionDetail($action))
                ->values(),
            'appeals' => $case->appeals
                ->sortBy('raised_at')
                ->map(fn (DisciplinaryAppeal $appeal) => self::appealRow($appeal))
                ->values(),
            'notes' => $case->notes
                ->filter(fn (DisciplinaryNote $note) => ! $note->confidential || $canConfidential)
                ->sortBy('created_at')
                ->map(fn (DisciplinaryNote $note) => self::noteRow($note))
                ->values(),
            'evidence' => $case->evidence
                ->sortBy('created_at')
                ->map(fn (DisciplinaryEvidence $item) => self::evidenceRow($item))
                ->values(),
            'timeline' => self::timeline($case, $canConfidential),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function timeline(DisciplinaryCase $case, bool $canConfidential): array
    {
        return $case->events
            ->filter(fn (DisciplinaryCaseEvent $event) => ! $event->confidential || $canConfidential)
            ->sortBy('created_at')
            ->sortBy('id')
            ->map(function (DisciplinaryCaseEvent $event) use ($case) {
                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'type_label' => $event->typeLabel(),
                    'reason' => $event->reason,
                    'confidential' => $event->confidential,
                    'actor_name' => $event->actor?->name ?: 'System',
                    'occurred_at' => $event->created_at?->toIso8601String(),
                    'occurred_label' => $event->created_at?->format('j M Y · H:i'),
                    'summary' => self::eventSummary($event, $case),
                    'payload' => $event->payload ?? [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function actionDetail(DisciplinaryAction $action): array
    {
        return [
            'id' => $action->id,
            'type' => $action->type,
            'type_label' => $action->typeLabel(),
            'justification' => $action->justification,
            'letter_subject' => $action->letter_subject,
            'letter_body' => $action->letter_body,
            'is_serious' => $action->isSerious(),
            'suspension_starts_on' => $action->suspension_starts_on?->toDateString(),
            'suspension_ends_on' => $action->suspension_ends_on?->toDateString(),
            'suspension_label' => $action->suspension_starts_on && $action->suspension_ends_on
                ? $action->suspension_starts_on->format('j M Y').' – '.$action->suspension_ends_on->format('j M Y')
                : null,
            'issued_by_name' => $action->issuer?->name,
            'issued_label' => $action->issued_at?->format('j M Y · H:i'),
            'acknowledged' => $action->isAcknowledged(),
            'acknowledged_label' => $action->acknowledged_at?->format('j M Y · H:i'),
            'has_response' => $action->hasResponse(),
            'response_body' => $action->response_body,
            'response_label' => $action->response_submitted_at?->format('j M Y · H:i'),
        ];
    }

    /**
     * Notice payload for the affected staff member — letter and outcome only.
     *
     * @return array<string, mixed>
     */
    public static function notice(DisciplinaryAction $action): array
    {
        $case = $action->case;

        return [
            'id' => $action->id,
            'case_id' => $case->id,
            'reference' => $case->reference,
            'type' => $action->type,
            'type_label' => $action->typeLabel(),
            'letter_subject' => $action->letter_subject,
            'letter_body' => $action->letter_body,
            'justification' => $action->justification,
            'issued_label' => $action->issued_at?->format('j M Y'),
            'acknowledged' => $action->isAcknowledged(),
            'acknowledged_label' => $action->acknowledged_at?->format('j M Y · H:i'),
            'has_response' => $action->hasResponse(),
            'response_body' => $action->response_body,
            'can_appeal' => $case->status === DisciplinaryCase::STATUS_ACTION_ISSUED && ! $case->isArchived(),
            'appeal_pending' => $case->appeals()->whereNull('reviewed_at')->exists(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function appealRow(DisciplinaryAppeal $appeal): array
    {
        return [
            'id' => $appeal->id,
            'grounds' => $appeal->grounds,
            'raised_by_name' => $appeal->raiser?->name,
            'raised_label' => $appeal->raised_at?->format('j M Y · H:i'),
            'outcome' => $appeal->outcome,
            'outcome_label' => $appeal->outcomeLabel(),
            'outcome_reason' => $appeal->outcome_reason,
            'reviewed_by_name' => $appeal->reviewer?->name,
            'reviewed_label' => $appeal->reviewed_at?->format('j M Y · H:i'),
            'is_reviewed' => $appeal->isReviewed(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function noteRow(DisciplinaryNote $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'confidential' => $note->confidential,
            'author_name' => $note->author?->name,
            'created_label' => $note->created_at?->format('j M Y · H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function evidenceRow(DisciplinaryEvidence $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->document?->title,
            'original_name' => $item->document?->original_name,
            'uploaded_by_name' => $item->uploader?->name,
            'created_label' => $item->created_at?->format('j M Y · H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function templateRow(DisciplinaryLetterTemplate $template): array
    {
        return [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'outcome_type' => $template->outcome_type,
            'outcome_label' => $template->outcomeLabel(),
            'body' => $template->body,
            'is_active' => $template->is_active,
            'updated_by_name' => $template->updater?->name,
        ];
    }

    /**
     * @param  Collection<int, DisciplinaryCase>  $cases
     * @return array<string, mixed>
     */
    public static function aggregateReport(Collection $cases): array
    {
        $resolved = $cases->whereIn('status', [
            DisciplinaryCase::STATUS_RESOLVED,
            DisciplinaryCase::STATUS_CLOSED,
        ]);

        $durations = $resolved->map(function (DisciplinaryCase $case) {
            $end = $case->updated_at ?? $case->created_at;

            return $case->created_at && $end
                ? $case->created_at->diffInDays($end)
                : null;
        })->filter();

        $appealed = $cases->filter(fn (DisciplinaryCase $case) => $case->appeals->isNotEmpty())->count();

        $byCategory = $cases
            ->groupBy(fn (DisciplinaryCase $case) => $case->categoryLabel())
            ->map(fn ($group, $label) => ['label' => $label, 'count' => $group->count()])
            ->values();

        $months = collect(range(5, 0))->map(function (int $back) use ($cases) {
            $month = now()->startOfMonth()->subMonths($back);

            return [
                'label' => $month->format('M Y'),
                'count' => $cases->filter(fn (DisciplinaryCase $case) => $case->created_at?->isSameMonth($month))->count(),
            ];
        })->values();

        return [
            'total' => $cases->count(),
            'open' => $cases->filter(fn (DisciplinaryCase $case) => $case->isOpen())->count(),
            'resolved' => $resolved->count(),
            'average_days_to_resolution' => $durations->count() ? round($durations->avg(), 1) : null,
            'appeal_count' => $appealed,
            'appeal_rate' => $cases->count() > 0 ? (int) round(($appealed / $cases->count()) * 100) : 0,
            'by_category' => $byCategory,
            'volume' => $months,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            'statuses' => DisciplinaryCase::statusOptions(),
            'severities' => DisciplinaryCase::severityOptions(),
            'categories' => DisciplinaryCase::categoryOptions(),
            'outcomes' => DisciplinaryAction::typeOptions(),
            'appeal_outcomes' => collect(config('discipline.appeal_outcomes', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    private static function eventSummary(DisciplinaryCaseEvent $event, DisciplinaryCase $case): string
    {
        $payload = $event->payload ?? [];

        return match ($event->type) {
            'created' => "Case {$case->reference} opened.",
            'status_changed' => sprintf(
                'Status moved from %s to %s.',
                config('discipline.statuses')[$payload['from'] ?? ''] ?? ($payload['from'] ?? 'previous'),
                config('discipline.statuses')[$payload['to'] ?? ''] ?? ($payload['to'] ?? 'next'),
            ),
            'note_added' => $event->confidential ? 'A restricted note was added.' : 'A case note was added.',
            'evidence_attached' => 'Supporting material was attached'.(isset($payload['title']) ? ': '.$payload['title'] : '.'),
            'action_issued' => 'A formal outcome was recorded: '.(config('discipline.outcomes')[$payload['type'] ?? ''] ?? 'action').'.',
            'acknowledged' => 'The staff member acknowledged receipt of the notice.',
            'response_submitted' => 'A written response was submitted against the notice.',
            'appeal_raised' => 'An appeal was raised.',
            'appeal_reviewed' => 'An appeal outcome was recorded: '.(config('discipline.appeal_outcomes')[$payload['outcome'] ?? ''] ?? 'reviewed').'.',
            'archived' => 'The case was archived. The record remains available.',
            default => $event->typeLabel().'.',
        };
    }

    private static function initials(User $user): string
    {
        $parts = preg_split('/\s+/', trim($user->name ?: $user->email)) ?: [];
        $letters = collect($parts)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');

        return $letters !== '' ? $letters : 'I';
    }
}
