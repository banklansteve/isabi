<?php

namespace App\Http\Requests\Admin\Concerns;

trait AuthorizesStaffManagement
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isSuperAdmin() ?? false) && $user->canDo('admin.staff.manage');
    }
}
