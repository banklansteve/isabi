<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffDocumentRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:40'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }
}
