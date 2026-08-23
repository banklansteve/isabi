<?php

namespace App\Http\Requests\Admin;

use App\Enums\StaffStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $staff */
        $staff = $this->route('staff');

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($staff->id),
            ],
            'staff_status' => ['sometimes', Rule::enum(StaffStatus::class)],
            'suspension_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('first_name')) {
            $merge['first_name'] = trim((string) $this->input('first_name'));
        }

        if ($this->has('last_name')) {
            $merge['last_name'] = trim((string) $this->input('last_name'));
        }

        if ($this->has('email')) {
            $merge['email'] = strtolower(trim((string) $this->input('email')));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
