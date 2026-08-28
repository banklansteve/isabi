<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisciplinaryLetterTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
