<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffCaseReferral;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReassignStaffCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var StaffCaseReferral|null $referral */
        $referral = $this->route('referral');

        if (! $user || ! $referral) {
            return false;
        }

        return $user->isSuperAdmin()
            || (int) $referral->assignee_user_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['required', 'string', 'min:4', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assignee_id.required' => 'Pick who should own this case next.',
            'note.required' => 'Add a short note for the hand-off.',
            'note.min' => 'A short note is enough — a few words.',
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
                $validator->errors()->add('assignee_id', 'Pick an active staff member.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => trim((string) $this->input('note')),
        ]);
    }
}
