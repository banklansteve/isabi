<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesStaffManagement;
use App\Models\Announcement;
use App\Models\AnnouncementTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendStaffAnnouncementRequest extends FormRequest
{
    use AuthorizesStaffManagement;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', Rule::exists(AnnouncementTemplate::class, 'id')],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:4', 'max:8000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in([
                Announcement::CHANNEL_IN_APP,
                Announcement::CHANNEL_EMAIL,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_id.required' => 'Pick a template first.',
            'subject.required' => 'Add a subject for this notice.',
            'body.required' => 'Write or keep the template body before sending.',
            'channels.required' => 'Pick in-app, email, or both.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => trim((string) $this->input('subject')),
            'body' => trim((string) $this->input('body')),
            'channels' => array_values(array_unique(array_filter((array) $this->input('channels', [])))),
        ]);
    }
}
