<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncStaffAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isSuperAdmin() ?? false) && $user->canDo('admin.staff.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', Rule::exists('staff_roles', 'id')->where('is_active', true)],
            'is_super' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_ids' => array_values(array_unique(array_map('intval', (array) $this->input('role_ids', [])))),
        ]);
    }
}
