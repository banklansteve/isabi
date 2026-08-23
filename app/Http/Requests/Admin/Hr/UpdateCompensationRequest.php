<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompensationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('hr.payroll.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'max:8'],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'pay_frequency' => ['required', 'string', 'in:monthly,weekly,biweekly,annual'],
            'effective_from' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:2100-12-31'],
            'note' => ['nullable', 'string', 'max:500'],
            'reason' => ['required', 'string', 'max:500'],
            'allowances' => ['array'],
            'allowances.*.label' => ['required_with:allowances', 'string', 'max:80'],
            'allowances.*.amount' => ['required_with:allowances', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required — compensation edits are logged.',
        ];
    }
}
