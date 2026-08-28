<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveDisciplinaryCaseRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
