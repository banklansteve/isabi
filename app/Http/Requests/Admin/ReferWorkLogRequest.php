<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReferWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('admin.content.manage') ?? false;
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
            'assignee_id.required' => 'Pick an operations staff member.',
            'note.required' => 'Add a short note so they know why this was referred.',
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
                $validator->errors()->add('assignee_id', 'Pick an active operations staff member, not an artisan.');
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
