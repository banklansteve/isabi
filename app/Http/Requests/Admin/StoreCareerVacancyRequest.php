<?php

namespace App\Http\Requests\Admin;

use App\Models\CareerVacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCareerVacancyRequest extends FormRequest
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
            'employment_type' => ['required', 'string', Rule::in(CareerVacancy::EMPLOYMENT_TYPES)],
            'work_mode' => ['required', 'string', Rule::in(CareerVacancy::WORK_MODES)],
            'status' => ['required', 'string', Rule::in(CareerVacancy::STATUSES)],
            'openings' => ['required', 'integer', 'min:1', 'max:100'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:12000'],
            'requirements' => ['nullable', 'string', 'max:8000'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:999999999', 'gte:salary_min'],
            'salary_currency' => ['nullable', 'string', 'max:8'],
            'salary_is_public' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:8000'],
            'apply_email' => ['nullable', 'email', 'max:190'],
            'apply_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'published_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'hiring_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'staff_role_id' => ['nullable', 'integer', 'exists:staff_roles,id'],
        ];
    }
}
