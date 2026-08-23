<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
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
        return [
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:Delete'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type Delete exactly to confirm.',
            'confirmation.required' => 'Type Delete to confirm you want to close this account.',
            'password.required' => 'Enter your password to confirm.',
        ];
    }
}
