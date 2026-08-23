<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class ExitStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('hr.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exit_date' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:2100-12-31'],
            'exit_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
