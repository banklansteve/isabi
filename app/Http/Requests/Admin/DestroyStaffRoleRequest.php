<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestroyStaffRoleRequest extends FormRequest
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
        /** @var StaffRole $role */
        $role = $this->route('role');

        return [
            'reason' => ['required', 'string', 'min:4', 'max:500'],
            'reassign_to' => [
                'nullable',
                'integer',
                Rule::exists(StaffRole::class, 'id')
                    ->where('is_active', true)
                    ->whereNot('id', $role?->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
        ]);
    }
}
