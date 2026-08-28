<?php

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRegularUser() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('body') === '') {
            $this->merge(['body' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('support.max_attachment_kb', 8192);
        $mimes = collect(config('support.allowed_mimes', []))
            ->map(fn ($mime) => match ($mime) {
                'image/jpeg' => 'jpg,jpeg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'application/pdf' => 'pdf',
                default => '',
            })
            ->filter()
            ->unique()
            ->implode(',');

        return [
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'topic_key' => ['nullable', 'string', Rule::in(collect(config('support.starters', []))->pluck('key')->all())],
            'attachment' => ['nullable', 'file', 'max:'.$maxKb, $mimes ? 'mimes:'.$mimes : 'file'],
        ];
    }
}
