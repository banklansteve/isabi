<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisciplinaryActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('hr.discipline.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(config('discipline.outcomes', [])))],
            'justification' => ['required', 'string', 'max:4000'],
            'letter_subject' => ['nullable', 'string', 'max:180'],
            'letter_body' => ['required', 'string', 'max:20000'],
            'template_id' => ['nullable', 'integer', 'exists:disciplinary_letter_templates,id'],
            'suspension_starts_on' => ['required_if:type,suspension', 'nullable', 'date'],
            'suspension_ends_on' => ['required_if:type,suspension', 'nullable', 'date', 'after_or_equal:suspension_starts_on'],
        ];
    }
}
