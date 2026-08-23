<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewAnnouncementRequest extends FormRequest
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
            'segment' => ['nullable', 'array'],
            'segment.plan' => ['nullable', 'string', Rule::in(['', 'free', 'payg', 'annual'])],
            'segment.expiring_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'segment.trade' => ['nullable', 'string', 'max:120'],
            'segment.state' => ['nullable', 'string', 'max:80'],
            'segment.lga' => ['nullable', 'string', 'max:80'],
        ];
    }
}
