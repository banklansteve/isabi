<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        $sub = $this->route('subcategory');
        $categoryId = $this->input('job_category_id', $sub?->job_category_id);

        return [
            'job_category_id' => ['sometimes', 'integer', 'exists:job_categories,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:160',
                Rule::unique('job_subcategories', 'name')
                    ->where(fn ($q) => $q->where('job_category_id', $categoryId))
                    ->ignore($sub?->id),
            ],
            'review_phrase' => ['nullable', 'string', 'max:160'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
