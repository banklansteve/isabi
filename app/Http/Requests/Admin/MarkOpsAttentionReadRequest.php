<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkOpsAttentionReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isOperationsAdmin() && ! $user->isRestrictedStaff();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:80'],
            'signature' => ['required', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => trim((string) $this->input('key')),
            'signature' => trim((string) $this->input('signature')),
        ]);
    }
}
