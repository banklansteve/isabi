<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqRequest extends FormRequest
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
            'question' => ['sometimes', 'required', 'string', 'max:255'],
            'answer' => ['sometimes', 'required', 'string', 'max:5000'],
            'category' => ['sometimes', 'required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_published' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'featured_sort' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }
}
