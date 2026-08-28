<?php

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSupportCsatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRegularUser() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'score' => ['nullable', 'integer', 'min:1', 'max:5', 'required_without:dismiss'],
            'comment' => ['nullable', 'string', 'max:500'],
            'dismiss' => ['sometimes', 'boolean'],
        ];
    }
}
