<?php

namespace App\Support\Hr;

use App\Models\ChecklistInstance;
use App\Models\CompensationRecord;
use App\Models\DisciplinaryRecord;
use App\Models\HrProfile;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\PerformanceNote;
use App\Models\StaffDocument;
use App\Models\User;

class HrPresenter
{
    /**
     * Compact row for the HR directory.
     *
     * @return array<string, mixed>
     */
    public static function directoryRow(User $user): array
    {
        $profile = $user->hrProfile;
        $onLeave = $profile && ! $profile->isExited() && self::isOnLeave($user);

        return [
            'id' => $user->id,
            'name' => $user->name ?: $user->email,
            'email' => $user->email,
            'initials' => self::initials($user),
            'position' => $profile?->position,
            'department' => $profile?->department,
            'role_label' => $user->roleLabel(),
            'role_key' => $user->roleKey(),
            'employment_status' => $profile?->employment_status ?? 'unmanaged',
            'display_status' => self::displayStatus($profile, $onLeave),
            'on_leave' => $onLeave,
            'start_date' => $profile?->start_date?->toDateString(),
            'start_date_label' => $profile?->start_date?->format('j M Y'),
            'has_profile' => $profile !== null,
            'is_super_admin' => $user->isSuperAdmin(),
        ];
    }

    /**
     * Full profile overview payload.
     *
     * @return array<string, mixed>
     */
    public static function profile(User $user): array
    {
        $profile = $user->hrProfile;
        $onLeave = $profile && ! $profile->isExited() && self::isOnLeave($user);

        return [
            'id' => $user->id,
            'name' => $user->name ?: $user->email,
            'email' => $user->email,
            'whatsapp' => $user->whatsapp,
            'initials' => self::initials($user),
            'avatar_url' => $user->avatar_url,
            'role_label' => $user->roleLabel(),
            'role_key' => $user->roleKey(),
            'is_super_admin' => $user->isSuperAdmin(),
            'has_profile' => $profile !== null,
            'employment_status' => $profile?->employment_status ?? 'unmanaged',
            'display_status' => self::displayStatus($profile, $onLeave),
            'on_leave' => $onLeave,
            'position' => $profile?->position,
            'department' => $profile?->department,
            'employment_type' => $profile?->employment_type,
            'start_date' => $profile?->start_date?->toDateString(),
            'start_date_label' => $profile?->start_date?->format('j M Y'),
            'exit_date' => $profile?->exit_date?->toDateString(),
            'exit_date_label' => $profile?->exit_date?->format('j M Y'),
            'exit_reason' => $profile?->exit_reason,
            'personal_email' => $profile?->personal_email,
            'personal_phone' => $profile?->personal_phone,
            'home_address' => $profile?->home_address,
            'date_of_birth' => $profile?->date_of_birth?->toDateString(),
            'emergency_contact_name' => $profile?->emergency_contact_name,
            'emergency_contact_phone' => $profile?->emergency_contact_phone,
            'emergency_contact_relationship' => $profile?->emergency_contact_relationship,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function leaveRequestRow(LeaveRequest $request): array
    {
        $remaining = null;
        if ($request->user && $request->leaveType) {
            $remaining = LeaveManager::remainingAfter(
                $request->user,
                $request->leaveType,
                $request->days,
            );
        }

        return [
            'id' => $request->id,
            'user_id' => $request->user_id,
            'staff_name' => $request->user?->name ?: $request->user?->email,
            'staff_initials' => $request->user ? self::initials($request->user) : '—',
            'leave_type_id' => $request->leave_type_id,
            'leave_type' => $request->leaveType?->name,
            'leave_color' => $request->leaveType?->color ?? 'base',
            'start_date' => $request->start_date->toDateString(),
            'end_date' => $request->end_date->toDateString(),
            'start_label' => $request->start_date->format('j M Y'),
            'end_label' => $request->end_date->format('j M Y'),
            'days' => $request->days,
            'note' => $request->note,
            'status' => $request->status,
            'decision_comment' => $request->decision_comment,
            'decided_by_name' => $request->decider?->name,
            'decided_at_label' => $request->decided_at?->diffForHumans(),
            'override_negative' => $request->override_negative,
            'remaining_after' => $remaining,
            'would_go_negative' => $remaining !== null && $remaining < 0 && $request->isPending(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payslipRow(Payslip $payslip): array
    {
        $items = $payslip->relationLoaded('items') ? $payslip->items : collect();

        return [
            'id' => $payslip->id,
            'period_label' => $payslip->period_label,
            'period_start' => $payslip->period_start->toDateString(),
            'period_end' => $payslip->period_end->toDateString(),
            'period_range_label' => $payslip->period_start->format('j M').' – '.$payslip->period_end->format('j M Y'),
            'currency' => $payslip->currency,
            'base_pay' => (float) $payslip->base_pay,
            'allowances_total' => (float) $payslip->allowances_total,
            'deductions_total' => (float) $payslip->deductions_total,
            'gross_pay' => (float) $payslip->gross_pay,
            'net_pay' => (float) $payslip->net_pay,
            'status' => $payslip->status,
            'issued_at_label' => $payslip->issued_at?->format('j M Y'),
            'paid_at_label' => $payslip->paid_at?->format('j M Y'),
            'notes' => $payslip->notes,
            'allowance_items' => $items
                ->where('kind', PayslipItem::KIND_ALLOWANCE)
                ->values()
                ->map(fn (PayslipItem $item) => [
                    'label' => $item->label,
                    'amount' => (float) $item->amount,
                ]),
            'deduction_items' => $items
                ->where('kind', PayslipItem::KIND_DEDUCTION)
                ->values()
                ->map(fn (PayslipItem $item) => [
                    'label' => $item->label,
                    'amount' => (float) $item->amount,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function compensationRow(CompensationRecord $record): array
    {
        return [
            'id' => $record->id,
            'currency' => $record->currency,
            'base_salary' => (float) $record->base_salary,
            'pay_frequency' => $record->pay_frequency,
            'effective_from' => $record->effective_from?->toDateString(),
            'effective_from_label' => $record->effective_from?->format('j M Y'),
            'note' => $record->note,
            'reason' => $record->reason,
            'allowances' => $record->allowances->map(fn ($allowance) => [
                'id' => $allowance->id,
                'label' => $allowance->label,
                'amount' => (float) $allowance->amount,
            ])->values(),
            'allowances_total' => $record->allowancesTotal(),
            'gross_total' => $record->grossTotal(),
            'updated_by_name' => $record->updater?->name,
            'updated_at_label' => $record->updated_at?->format('j M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function disciplineRow(DisciplinaryRecord $record): array
    {
        return [
            'id' => $record->id,
            'user_id' => $record->user_id,
            'staff_name' => $record->user?->name ?: $record->user?->email,
            'staff_initials' => $record->user ? self::initials($record->user) : '—',
            'type' => $record->type,
            'type_label' => $record->typeLabel(),
            'status' => $record->status,
            'status_label' => $record->statusLabel(),
            'occurred_on' => $record->occurred_on->toDateString(),
            'occurred_label' => $record->occurred_on->format('j M Y'),
            'summary' => $record->summary,
            'details' => $record->details,
            'issued_by' => $record->issued_by,
            'issued_by_name' => $record->issuedBy?->name,
            'follow_up_on' => $record->follow_up_on?->toDateString(),
            'follow_up_label' => $record->follow_up_on?->format('j M Y'),
            'outcome' => $record->outcome,
            'staff_document_id' => $record->staff_document_id,
            'document_title' => $record->document?->title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function performanceNoteRow(PerformanceNote $note): array
    {
        return [
            'id' => $note->id,
            'rating' => $note->rating,
            'body' => $note->body,
            'noted_on' => $note->noted_on->toDateString(),
            'noted_on_label' => $note->noted_on->format('j M Y'),
            'author_name' => $note->author?->name ?? 'System',
            'created_at_label' => $note->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function documentRow(StaffDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type,
            'title' => $document->title,
            'original_name' => $document->original_name,
            'size' => $document->size,
            'expiry_date' => $document->expiry_date?->toDateString(),
            'expiry_label' => $document->expiry_date?->format('j M Y'),
            'expiry_state' => $document->expiryState(),
            'days_until_expiry' => $document->daysUntilExpiry(),
            'uploaded_by_name' => $document->uploader?->name,
            'created_at_label' => $document->created_at?->format('j M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function checklistInstance(ChecklistInstance $instance): array
    {
        return [
            'id' => $instance->id,
            'kind' => $instance->kind,
            'name' => $instance->template?->name ?? ucfirst($instance->kind).' checklist',
            'progress' => $instance->progress(),
            'completed_at_label' => $instance->completed_at?->format('j M Y'),
            'items' => $instance->items->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'is_done' => $item->is_done,
                'done_by_name' => $item->doneBy?->name,
                'done_at_label' => $item->done_at?->format('j M Y'),
            ])->values(),
        ];
    }

    public static function displayStatus(?HrProfile $profile, bool $onLeave): string
    {
        if (! $profile) {
            return 'unmanaged';
        }

        if ($profile->isExited()) {
            return 'exited';
        }

        return $onLeave ? 'on_leave' : 'active';
    }

    private static function isOnLeave(User $user): bool
    {
        if (array_key_exists('on_approved_leave', $user->getAttributes())) {
            return (bool) $user->getAttribute('on_approved_leave');
        }

        return $user->isOnLeaveOn();
    }

    public static function initials(User $user): string
    {
        if ($user->first_name || $user->last_name) {
            return strtoupper(
                mb_substr((string) $user->first_name, 0, 1)
                .mb_substr((string) $user->last_name, 0, 1)
            ) ?: 'IW';
        }

        $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr((string) ($parts[0] ?? 'I'), 0, 1)) ?: 'IW';
    }
}
