<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'rating' => ['required', 'numeric', 'min:0.5', 'max:5'],
            'would_recommend' => ['required', 'boolean'],
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
            'would_recommend.required' => 'Please tell us if you’d recommend them.',
            'would_recommend.boolean' => 'Please tell us if you’d recommend them.',
            'photo.max' => 'Photo must be 5MB or smaller.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rating = $this->input('rating');
            if ($rating === null || $rating === '') {
                return;
            }

            $value = (float) $rating;
            // Must be a half-star step: 0.5, 1.0, 1.5 … 5.0
            if (abs(($value * 2) - round($value * 2)) > 0.001) {
                $validator->errors()->add('rating', 'Please choose a full or half star rating.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $recommend = $this->input('would_recommend');
        if ($recommend === '1' || $recommend === 1 || $recommend === true || $recommend === 'true') {
            $recommend = true;
        } elseif ($recommend === '0' || $recommend === 0 || $recommend === false || $recommend === 'false') {
            $recommend = false;
        } else {
            $recommend = null;
        }

        $this->merge([
            'client_display_name' => trim((string) $this->input('client_display_name')) ?: null,
            'referred_by' => trim((string) $this->input('referred_by')) ?: null,
            'comment' => trim((string) $this->input('comment')) ?: null,
            'would_recommend' => $recommend,
            'rating' => $this->input('rating') !== null && $this->input('rating') !== ''
                ? round((float) $this->input('rating'), 1)
                : null,
        ]);
    }
}
