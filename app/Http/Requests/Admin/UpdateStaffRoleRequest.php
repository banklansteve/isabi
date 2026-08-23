<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffRole;
use App\Support\Staff\AdminPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateStaffRoleRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique(StaffRole::class)->ignore($role->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:64'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(AdminPermissions::keys())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name')) {
            $merge['name'] = trim((string) $this->input('name'));
        }

        if ($this->has('slug')) {
            $merge['slug'] = Str::slug(trim((string) $this->input('slug')));
        }

        if ($this->has('description')) {
            $merge['description'] = trim((string) $this->input('description')) ?: null;
        }

        if ($this->has('icon')) {
            $merge['icon'] = trim((string) $this->input('icon')) ?: null;
        }

        if ($this->has('permissions')) {
            $merge['permissions'] = AdminPermissions::sanitize($this->input('permissions', []));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
