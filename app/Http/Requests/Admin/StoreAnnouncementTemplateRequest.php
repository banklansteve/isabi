<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAnnouncementTemplateRequest extends FormRequest
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
        return [
            'audience' => ['required', Rule::in([Announcement::AUDIENCE_USERS, Announcement::AUDIENCE_STAFF])],
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:8000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in([
                Announcement::CHANNEL_IN_APP,
                Announcement::CHANNEL_EMAIL,
                Announcement::CHANNEL_WHATSAPP,
            ])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('audience') === Announcement::AUDIENCE_STAFF
                && in_array(Announcement::CHANNEL_WHATSAPP, $this->input('channels', []), true)) {
                $validator->errors()->add('channels', 'Staff templates can only use in-app and email.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'subject' => trim((string) $this->input('subject')),
            'body' => trim((string) $this->input('body')),
            'channels' => array_values(array_unique($this->input('channels', []))),
        ]);
    }
}
