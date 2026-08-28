<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('admin.support.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('assigned_to_user_id') === '' || $this->input('assigned_to_user_id') === 'null') {
            $this->merge(['assigned_to_user_id' => null]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $agentId = $this->input('assigned_to_user_id');

            if (! $user || $user->isSuperAdmin() || $agentId === null || $agentId === '') {
                return;
            }

            // Ops staff may only claim for themselves — never hand chats to another agent.
            if ((int) $agentId !== (int) $user->id) {
                $validator->errors()->add(
                    'assigned_to_user_id',
                    'You can only assign chats to yourself.',
                );
            }
        });
    }

    public function agent(): ?User
    {
        $id = $this->validated('assigned_to_user_id');

        return $id ? User::query()->find($id) : null;
    }
}
