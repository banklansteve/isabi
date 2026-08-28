<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageWorkLogArtisanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->canDo('admin.content.manage') || $user->canDo('admin.messaging.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $whatsapp = $this->input('channel') === Announcement::CHANNEL_WHATSAPP;

        return [
            'channel' => ['required', Rule::in([
                Announcement::CHANNEL_IN_APP,
                Announcement::CHANNEL_EMAIL,
                Announcement::CHANNEL_WHATSAPP,
            ])],
            'subject' => [$whatsapp ? 'nullable' : 'required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:4', 'max:8000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Pick email, WhatsApp, or in-app.',
            'subject.required' => 'Add a subject for this message.',
            'body.required' => 'Write the message before sending.',
            'body.min' => 'A short message is enough — a few words.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => trim((string) $this->input('subject')),
            'body' => trim((string) $this->input('body')),
            'channel' => trim((string) $this->input('channel')),
        ]);
    }
}
