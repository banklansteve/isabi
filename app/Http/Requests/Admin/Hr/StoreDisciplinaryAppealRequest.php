<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplinaryAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->canDo('hr.discipline.manage')
            || $user->isStaff();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grounds' => ['required', 'string', 'max:4000'],
        ];
    }
}
