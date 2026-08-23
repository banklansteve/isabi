<?php

namespace App\Http\Requests\Admin\Hr;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:200'],
            'allowance_days' => ['required', 'integer', 'min:0', 'max:365'],
            'color' => ['required', Rule::in(LeaveType::colorTokens())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
