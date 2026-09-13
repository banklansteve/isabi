<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareerVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'department' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:120'],
            'employment_type' => [
                'nullable',
                'string',
                Rule::in(['full-time', 'part-time', 'contract', 'internship', 'other']),
            ],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:8000'],
            'apply_email' => ['nullable', 'email', 'max:190'],
            'apply_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
