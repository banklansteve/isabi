<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplinaryNoteRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:8000'],
            'confidential' => ['sometimes', 'boolean'],
        ];
    }
}
