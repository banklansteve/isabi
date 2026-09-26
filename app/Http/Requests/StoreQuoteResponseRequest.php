<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuoteResponseRequest extends FormRequest
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
            'decision' => ['required', Rule::in(['accepted', 'declined', 'adjustments'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $decision = (string) $this->input('decision');
            $message = trim((string) $this->input('message', ''));

            if ($decision === 'adjustments' && mb_strlen($message) < 10) {
                $validator->errors()->add(
                    'message',
                    'Please explain what you would like changed (at least a short note).',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Choose accept, request changes, or decline.',
            'decision.in' => 'Choose accept, request changes, or decline.',
            'message.max' => 'Keep your note under 1000 characters.',
        ];
    }
}
