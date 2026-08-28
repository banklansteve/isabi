<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendStaffChatMessageRequest extends FormRequest
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
        $maxKb = (int) config('support.max_attachment_kb', 8192);

        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:'.$maxKb],
            'gif_url' => ['nullable', 'url', 'max:2048'],
            'gif_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $body = trim((string) $this->input('body', ''));
            $hasMedia = $this->file('attachment') || filled($this->input('gif_url'));

            if ($body === '' && ! $hasMedia) {
                $validator->errors()->add('body', 'Write a message, attach a file, or pick a GIF.');
            }
        });
    }
}
