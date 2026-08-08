<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1200'],
            'client_display_name' => ['nullable', 'string', 'max:120'],
            'referred_by' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Please choose a star rating.',
            'rating.min' => 'Please choose a star rating.',
            'photo.max' => 'Photo must be 5MB or smaller.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_display_name' => trim((string) $this->input('client_display_name')) ?: null,
            'referred_by' => trim((string) $this->input('referred_by')) ?: null,
            'comment' => trim((string) $this->input('comment')) ?: null,
        ]);
    }
}
