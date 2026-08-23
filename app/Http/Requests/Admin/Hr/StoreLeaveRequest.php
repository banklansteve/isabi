<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->canDo('hr.manage') || $this->user()?->canDo('hr.leave.manage')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:2100-12-31'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:2100-12-31'],
            'note' => ['nullable', 'string', 'max:500'],
            'approve_now' => ['sometimes', 'boolean'],
            'override_negative' => ['sometimes', 'boolean'],
        ];
    }
}
