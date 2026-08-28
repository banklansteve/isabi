<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class AllocateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('hr.leave.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'allowance_days' => ['required_without:remaining_days', 'nullable', 'integer', 'min:0', 'max:365'],
            'remaining_days' => ['required_without:allowance_days', 'nullable', 'integer', 'min:0', 'max:365'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
