<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffRole;
use App\Support\Staff\AdminPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignStaffRoleRequest extends FormRequest
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
            'role' => ['nullable', 'string', Rule::in([AdminPermissions::SUPER_KEY])],
            'role_id' => [
                'nullable',
                'required_without:role',
                'integer',
                Rule::exists(StaffRole::class, 'id')->where('is_active', true),
            ],
        ];
    }
}
