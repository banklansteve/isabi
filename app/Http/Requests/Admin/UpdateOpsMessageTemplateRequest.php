<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpsMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
            'whatsapp_body' => ['nullable', 'string', 'max:2000'],
            'editable_keys' => ['nullable', 'array'],
            'editable_keys.*' => ['string', 'in:subject,body,whatsapp_body'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
