<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartStaffDirectChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereIn('role', [
                    UserRole::SuperAdmin->value,
                    UserRole::OperationsAdmin->value,
                ]),
                Rule::notIn([(int) $this->user()?->id]),
            ],
        ];
    }
}
