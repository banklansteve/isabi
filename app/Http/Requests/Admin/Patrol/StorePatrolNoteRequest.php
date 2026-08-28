<?php

namespace App\Http\Requests\Admin\Patrol;

use Illuminate\Foundation\Http\FormRequest;

class StorePatrolNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('patrol.investigate') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:8000'],
        ];
    }
}
