<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class StorePayslipRequest extends FormRequest
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
            'period_label' => ['required', 'string', 'max:60'],
            'period_start' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:2100-12-31'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:2100-12-31'],
            'notes' => ['nullable', 'string', 'max:500'],
            'allowances' => ['array'],
            'allowances.*.label' => ['required_with:allowances', 'string', 'max:80'],
            'allowances.*.amount' => ['required_with:allowances', 'numeric', 'min:0', 'max:999999999'],
            'deductions' => ['array'],
            'deductions.*.label' => ['required_with:deductions', 'string', 'max:80'],
            'deductions.*.amount' => ['required_with:deductions', 'numeric', 'min:0', 'max:999999999'],
            'base_pay' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
