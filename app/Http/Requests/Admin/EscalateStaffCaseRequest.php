<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffCaseReferral;
use App\Support\Admin\StaffCaseReferralService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscalateStaffCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->isStaff() || $user->isRestrictedStaff() || $user->isSuperAdmin()) {
            return false;
        }

        $type = (string) $this->input('subject_type');

        return app(StaffCaseReferralService::class)->canEscalate($user, $type);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'string', Rule::in(StaffCaseReferral::SUBJECT_TYPES)],
            'subject_uid' => ['required', 'string', 'max:64'],
            'note' => ['required', 'string', 'min:4', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => 'Add context so Super Admin knows why this was escalated.',
            'note.min' => 'A short note is enough — a few words.',
            'subject_type.required' => 'Pick a case type to escalate.',
            'subject_uid.required' => 'Pick a case to escalate.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => trim((string) $this->input('note')),
            'subject_type' => strtolower(trim((string) $this->input('subject_type'))),
            'subject_uid' => trim((string) $this->input('subject_uid')),
        ]);
    }
}
