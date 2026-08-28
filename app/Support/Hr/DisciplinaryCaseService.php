<?php

namespace App\Support\Hr;

use App\Models\DisciplinaryAction;
use App\Models\DisciplinaryAppeal;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryCaseEvent;
use App\Models\DisciplinaryEvidence;
use App\Models\DisciplinaryLetterTemplate;
use App\Models\DisciplinaryNote;
use App\Models\StaffDocument;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisciplinaryCaseService
{
    public function create(User $actor, User $staff, array $data): DisciplinaryCase
    {
        abort_unless($staff->isStaff(), 404);

        return DB::transaction(function () use ($actor, $staff, $data) {
            $case = DisciplinaryCase::query()->create([
                'reference' => $this->nextReference(),
                'user_id' => $staff->id,
                'owner_id' => $data['owner_id'],
                'opened_by' => $actor->id,
                'category' => $data['category'],
                'category_label' => $data['category'] === 'other' ? ($data['category_label'] ?? null) : null,
                'severity' => $data['severity'],
                'incident_on' => $data['incident_on'],
                'description' => $data['description'],
                'status' => DisciplinaryCase::STATUS_REPORTED,
            ]);

            $this->recordEvent($case, $actor, 'created', $data['reason'] ?? 'Case opened.', [
                'status' => $case->status,
                'severity' => $case->severity,
                'category' => $case->category,
            ]);

            AdminAudit::record(
                'discipline.case_opened',
                "{$actor->name} opened disciplinary case {$case->reference} for {$staff->name}.",
                $case,
                null,
                ['reference' => $case->reference, 'reason' => $data['reason'] ?? 'Case opened.'],
                $actor,
            );

            return $case->fresh();
        });
    }

    public function transition(DisciplinaryCase $case, User $actor, string $status, string $reason): DisciplinaryCase
    {
        $this->guardMutable($case);

        if (! in_array($status, $case->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'status' => 'That status change is not available from the current stage.',
            ]);
        }

        return $this->moveStatus($case, $actor, $status, $reason);
    }

    public function addNote(DisciplinaryCase $case, User $actor, string $body, bool $confidential): DisciplinaryNote
    {
        $this->guardMutable($case);

        return DB::transaction(function () use ($case, $actor, $body, $confidential) {
            $note = $case->notes()->create([
                'author_id' => $actor->id,
                'body' => $body,
                'confidential' => $confidential,
                'created_at' => now(),
            ]);

            $this->recordEvent($case, $actor, 'note_added', null, [
                'note_id' => $note->id,
            ], $confidential);

            AdminAudit::record(
                'discipline.note_added',
                "{$actor->name} added a note to {$case->reference}.",
                $case,
                null,
                ['note_id' => $note->id, 'confidential' => $confidential],
                $actor,
            );

            return $note;
        });
    }

    public function attachEvidence(DisciplinaryCase $case, User $actor, UploadedFile $file, string $title): DisciplinaryEvidence
    {
        $this->guardMutable($case);

        return DB::transaction(function () use ($case, $actor, $file, $title) {
            $path = $file->store("hr/discipline/{$case->id}", 'local');

            $document = StaffDocument::query()->create([
                'user_id' => $case->user_id,
                'type' => 'disciplinary_evidence',
                'title' => $title,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $actor->id,
            ]);

            $evidence = $case->evidence()->create([
                'staff_document_id' => $document->id,
                'uploaded_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->recordEvent($case, $actor, 'evidence_attached', null, [
                'evidence_id' => $evidence->id,
                'document_id' => $document->id,
                'title' => $title,
            ]);

            AdminAudit::record(
                'discipline.evidence_attached',
                "{$actor->name} attached evidence to {$case->reference}.",
                $case,
                null,
                ['evidence_id' => $evidence->id, 'title' => $title],
                $actor,
            );

            return $evidence;
        });
    }

    public function issueAction(DisciplinaryCase $case, User $actor, array $data): DisciplinaryAction
    {
        $this->guardMutable($case);

        if ($case->status !== DisciplinaryCase::STATUS_DECISION_PENDING) {
            throw ValidationException::withMessages([
                'type' => 'A formal action can only be recorded when the case is pending a decision.',
            ]);
        }

        $template = isset($data['template_id'])
            ? DisciplinaryLetterTemplate::query()->find($data['template_id'])
            : DisciplinaryLetterTemplate::query()
                ->where('outcome_type', $data['type'])
                ->where('is_active', true)
                ->first();

        return DB::transaction(function () use ($case, $actor, $data, $template) {
            $draft = new DisciplinaryAction([
                'type' => $data['type'],
                'justification' => $data['justification'],
                'suspension_starts_on' => $data['suspension_starts_on'] ?? null,
                'suspension_ends_on' => $data['suspension_ends_on'] ?? null,
                'issued_at' => now(),
            ]);

            $body = ($data['letter_body'] ?? '') !== '' ? $data['letter_body'] : ($template?->body ?? '');
            $rendered = DisciplinaryLetter::render($body, $case, $draft);

            $action = $case->actions()->create([
                'type' => $data['type'],
                'justification' => $data['justification'],
                'suspension_starts_on' => $data['suspension_starts_on'] ?? null,
                'suspension_ends_on' => $data['suspension_ends_on'] ?? null,
                'letter_subject' => ($data['letter_subject'] ?? '') !== ''
                    ? $data['letter_subject']
                    : DisciplinaryLetter::defaultSubject($data['type']),
                'letter_body' => $rendered,
                'template_id' => $template?->id,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            $this->recordEvent($case, $actor, 'action_issued', $data['justification'], [
                'action_id' => $action->id,
                'type' => $action->type,
            ]);

            $this->moveStatus($case, $actor, DisciplinaryCase::STATUS_ACTION_ISSUED, $data['justification']);

            AdminAudit::record(
                'discipline.action_issued',
                "{$actor->name} recorded a {$action->typeLabel()} on {$case->reference}.",
                $case,
                ['status' => DisciplinaryCase::STATUS_DECISION_PENDING],
                ['status' => DisciplinaryCase::STATUS_ACTION_ISSUED, 'type' => $action->type, 'reason' => $data['justification']],
                $actor,
            );

            return $action;
        });
    }

    public function acknowledge(DisciplinaryAction $action, User $actor): DisciplinaryAction
    {
        abort_unless($action->case->user_id === $actor->id, 403);

        if ($action->isAcknowledged()) {
            throw ValidationException::withMessages([
                'acknowledge' => 'This notice has already been acknowledged.',
            ]);
        }

        return DB::transaction(function () use ($action, $actor) {
            $action->update(['acknowledged_at' => now()]);

            $this->recordEvent($action->case, $actor, 'acknowledged', null, [
                'action_id' => $action->id,
            ]);

            AdminAudit::record(
                'discipline.acknowledged',
                "{$actor->name} acknowledged notice {$action->case->reference}.",
                $action->case,
                null,
                ['action_id' => $action->id],
                $actor,
            );

            return $action->fresh();
        });
    }

    public function respond(DisciplinaryAction $action, User $actor, string $body): DisciplinaryAction
    {
        abort_unless($action->case->user_id === $actor->id, 403);

        if (! $action->isAcknowledged()) {
            throw ValidationException::withMessages([
                'response' => 'Acknowledge the notice before submitting a written response.',
            ]);
        }

        if ($action->hasResponse()) {
            throw ValidationException::withMessages([
                'response' => 'A written response has already been recorded for this notice.',
            ]);
        }

        return DB::transaction(function () use ($action, $actor, $body) {
            $action->update([
                'response_body' => $body,
                'response_submitted_at' => now(),
            ]);

            $this->recordEvent($action->case, $actor, 'response_submitted', null, [
                'action_id' => $action->id,
            ]);

            AdminAudit::record(
                'discipline.response_submitted',
                "{$actor->name} submitted a written response on {$action->case->reference}.",
                $action->case,
                null,
                ['action_id' => $action->id],
                $actor,
            );

            return $action->fresh();
        });
    }

    public function raiseAppeal(DisciplinaryCase $case, User $actor, string $grounds): DisciplinaryAppeal
    {
        $this->guardMutable($case);

        $action = $case->latestAction();

        if (! $action || $case->status !== DisciplinaryCase::STATUS_ACTION_ISSUED) {
            throw ValidationException::withMessages([
                'grounds' => 'An appeal can only be raised after a formal action has been issued.',
            ]);
        }

        if ($case->appeals()->whereNull('reviewed_at')->exists()) {
            throw ValidationException::withMessages([
                'grounds' => 'An appeal is already waiting for review.',
            ]);
        }

        $isSubject = $actor->id === $case->user_id;
        $isHandler = $actor->canDo('hr.discipline.manage') || $actor->isSuperAdmin();

        abort_unless($isSubject || $isHandler, 403);

        return DB::transaction(function () use ($case, $action, $actor, $grounds) {
            $appeal = $case->appeals()->create([
                'disciplinary_action_id' => $action->id,
                'grounds' => $grounds,
                'raised_by' => $actor->id,
                'raised_at' => now(),
            ]);

            $this->recordEvent($case, $actor, 'appeal_raised', $grounds, [
                'appeal_id' => $appeal->id,
                'action_id' => $action->id,
            ]);

            $this->moveStatus($case, $actor, DisciplinaryCase::STATUS_APPEALED, $grounds);

            AdminAudit::record(
                'discipline.appeal_raised',
                "{$actor->name} raised an appeal on {$case->reference}.",
                $case,
                ['status' => DisciplinaryCase::STATUS_ACTION_ISSUED],
                ['status' => DisciplinaryCase::STATUS_APPEALED, 'reason' => $grounds],
                $actor,
            );

            return $appeal;
        });
    }

    public function reviewAppeal(DisciplinaryAppeal $appeal, User $actor, string $outcome, string $reason): DisciplinaryAppeal
    {
        abort_unless($actor->isSuperAdmin(), 403);

        if ($appeal->isReviewed()) {
            throw ValidationException::withMessages([
                'outcome' => 'This appeal has already been reviewed.',
            ]);
        }

        $case = $appeal->case;
        $this->guardMutable($case);

        $next = $outcome === DisciplinaryAppeal::OUTCOME_UPHELD
            ? DisciplinaryCase::STATUS_ACTION_ISSUED
            : DisciplinaryCase::STATUS_DECISION_PENDING;

        return DB::transaction(function () use ($appeal, $actor, $outcome, $reason, $case, $next) {
            $appeal->update([
                'outcome' => $outcome,
                'outcome_reason' => $reason,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            $this->recordEvent($case, $actor, 'appeal_reviewed', $reason, [
                'appeal_id' => $appeal->id,
                'outcome' => $outcome,
            ]);

            $this->moveStatus($case, $actor, $next, $reason);

            AdminAudit::record(
                'discipline.appeal_reviewed',
                "{$actor->name} recorded an appeal outcome on {$case->reference}.",
                $case,
                ['status' => DisciplinaryCase::STATUS_APPEALED],
                ['status' => $next, 'outcome' => $outcome, 'reason' => $reason],
                $actor,
            );

            return $appeal->fresh();
        });
    }

    public function archive(DisciplinaryCase $case, User $actor, string $reason): DisciplinaryCase
    {
        if (! in_array($case->status, [DisciplinaryCase::STATUS_RESOLVED, DisciplinaryCase::STATUS_CLOSED], true)) {
            throw ValidationException::withMessages([
                'reason' => 'A case can only be archived after it is resolved or closed.',
            ]);
        }

        if ($case->isArchived()) {
            return $case;
        }

        return DB::transaction(function () use ($case, $actor, $reason) {
            $case->update(['archived_at' => now()]);

            $this->recordEvent($case, $actor, 'archived', $reason);

            AdminAudit::record(
                'discipline.case_archived',
                "{$actor->name} archived {$case->reference}.",
                $case,
                null,
                ['reason' => $reason],
                $actor,
            );

            return $case->fresh();
        });
    }

    public function updateTemplate(DisciplinaryLetterTemplate $template, User $actor, array $data): DisciplinaryLetterTemplate
    {
        $template->update([
            'name' => $data['name'],
            'body' => $data['body'],
            'is_active' => $data['is_active'] ?? $template->is_active,
            'updated_by' => $actor->id,
        ]);

        AdminAudit::record(
            'discipline.template_updated',
            "{$actor->name} updated the {$template->name} letter template.",
            $template,
            null,
            ['slug' => $template->slug],
            $actor,
        );

        return $template->fresh();
    }

    public function canViewConfidential(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    private function moveStatus(DisciplinaryCase $case, User $actor, string $status, string $reason): DisciplinaryCase
    {
        if ($case->status === $status) {
            return $case;
        }

        $from = $case->status;
        $case->update(['status' => $status]);

        $this->recordEvent($case, $actor, 'status_changed', $reason, [
            'from' => $from,
            'to' => $status,
        ]);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordEvent(
        DisciplinaryCase $case,
        User $actor,
        string $type,
        ?string $reason,
        array $payload = [],
        bool $confidential = false,
    ): DisciplinaryCaseEvent {
        return $case->events()->create([
            'actor_id' => $actor->id,
            'type' => $type,
            'reason' => $reason,
            'confidential' => $confidential,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    private function guardMutable(DisciplinaryCase $case): void
    {
        if ($case->isArchived()) {
            throw ValidationException::withMessages([
                'case' => 'This case is archived and cannot be changed.',
            ]);
        }
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $prefix = "DC-{$year}-";

        $latest = DisciplinaryCase::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
