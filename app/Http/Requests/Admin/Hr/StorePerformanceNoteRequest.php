<?php

namespace App\Http\Requests\Admin\Hr;

use App\Models\PerformanceNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerformanceNoteRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:2000'],
            'rating' => ['nullable', Rule::in(PerformanceNote::ratings())],
            'noted_on' => ['required', 'date'],
        ];
    }
}
