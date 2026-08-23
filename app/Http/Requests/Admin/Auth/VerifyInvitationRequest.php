<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $length = (int) config('admin.invite.code_length', 6);

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:'.$length, 'regex:/^\d+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $length = (int) config('admin.invite.code_length', 6);

        return [
            'code.size' => "Enter the {$length}-digit code from your email.",
            'code.regex' => "Enter the {$length}-digit code from your email.",
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'code' => preg_replace('/\s+/', '', (string) $this->input('code')),
        ]);
    }
}
