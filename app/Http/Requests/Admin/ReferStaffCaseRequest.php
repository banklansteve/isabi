<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffCaseReferral;
use App\Models\User;
use App\Support\Admin\StaffCaseReferralService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReferStaffCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $type = (string) $this->input('subject_type');

        return app(StaffCaseReferralService::class)->canRefer($user, $type);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'string', Rule::in(StaffCaseReferral::SUBJECT_TYPES)],
            'subject_uid' => ['required', 'string', 'max:64'],
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['required', 'string', 'min:4', 'max:2000'],
            'queue' => ['nullable', 'string', Rule::in(StaffCaseReferral::QUEUES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assignee_id.required' => 'Pick an operations staff member.',
            'note.required' => 'Add a short note so they know why this was referred.',
            'note.min' => 'A short note is enough — a few words.',
            'subject_type.required' => 'Pick a case type to refer.',
            'subject_uid.required' => 'Pick a case to refer.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $assigneeId = (int) $this->input('assignee_id');
            if ($assigneeId < 1) {
                return;
            }

            $assignee = User::query()->find($assigneeId);
            if (! $assignee?->isStaff() || $assignee->isSuspended()) {
                $validator->errors()->add('assignee_id', 'Pick an active operations staff member, not an artisan.');
            }

            if ($this->user() && (int) $assigneeId === (int) $this->user()->id) {
                $validator->errors()->add('assignee_id', 'Refer this case to another staff member.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => trim((string) $this->input('note')),
            'subject_type' => strtolower(trim((string) $this->input('subject_type'))),
            'subject_uid' => trim((string) $this->input('subject_uid')),
            'queue' => filled($this->input('queue'))
                ? strtolower(trim((string) $this->input('queue')))
                : null,
        ]);
    }
}
