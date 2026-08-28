<?php

namespace App\Http\Requests\Admin\Hr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionDisciplinaryCaseRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(array_keys(config('discipline.statuses', [])))],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
