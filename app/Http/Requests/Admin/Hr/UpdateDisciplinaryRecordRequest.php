<?php

namespace App\Http\Requests\Admin\Hr;

use App\Models\DisciplinaryRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDisciplinaryRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('hr.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('staff_document_id') === '' || $this->input('staff_document_id') === '0') {
            $this->merge(['staff_document_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(DisciplinaryRecord::types())],
            'status' => ['required', 'string', Rule::in(DisciplinaryRecord::statuses())],
            'occurred_on' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:2100-12-31'],
            'summary' => ['required', 'string', 'max:180'],
            'details' => ['nullable', 'string', 'max:4000'],
            'follow_up_on' => ['nullable', 'date', 'after_or_equal:occurred_on', 'before_or_equal:2100-12-31'],
            'outcome' => [
                Rule::requiredIf(fn () => $this->input('status') === DisciplinaryRecord::STATUS_CLOSED),
                'nullable',
                'string',
                'max:2000',
            ],
            'staff_document_id' => ['nullable', 'integer', 'exists:staff_documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'outcome.required' => 'Record an outcome when closing a case.',
        ];
    }
}
