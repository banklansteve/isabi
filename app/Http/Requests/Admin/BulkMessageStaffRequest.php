<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesStaffManagement;
use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkMessageStaffRequest extends FormRequest
{
    use AuthorizesStaffManagement;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:4', 'max:8000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in([
                Announcement::CHANNEL_IN_APP,
                Announcement::CHANNEL_EMAIL,
            ])],
            'reason' => ['required', 'string', 'min:4', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one staff member.',
            'subject.required' => 'Add a subject for this message.',
            'body.required' => 'Write the message before sending.',
            'reason.required' => 'Add a short reason for the audit log.',
            'channels.required' => 'Pick in-app, email, or both.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ids' => array_values(array_unique(array_map('intval', (array) $this->input('ids', [])))),
            'subject' => trim((string) $this->input('subject')),
            'body' => trim((string) $this->input('body')),
            'reason' => trim((string) $this->input('reason')),
            'channels' => array_values(array_unique(array_filter((array) $this->input('channels', [])))),
        ]);
    }
}
