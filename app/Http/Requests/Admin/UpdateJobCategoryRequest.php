<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:160', Rule::unique('job_categories', 'name')->ignore($id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
