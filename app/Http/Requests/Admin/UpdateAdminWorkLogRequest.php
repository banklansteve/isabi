<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:4', 'max:500'],
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:120'],
            'worked_on' => ['required', 'date'],
            'job_category' => ['nullable', 'string', 'max:160'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
            'description' => trim((string) $this->input('description')),
            'client_name' => trim((string) $this->input('client_name')) ?: null,
        ]);
    }
}
