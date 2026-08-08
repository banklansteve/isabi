<?php

namespace App\Http\Requests;

use App\Rules\AvailableProfileSlug;
use App\Support\ProfileSlug;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSlugRequest extends FormRequest
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
            'slug' => [
                'required',
                'string',
                'max:140',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                new AvailableProfileSlug($this->user()?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers, and hyphens only.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => ProfileSlug::normalize((string) $this->input('slug')),
        ]);
    }
}
