<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAnnouncementRequest extends FormRequest
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
            'announcement_template_id' => ['nullable', 'integer', 'exists:announcement_templates,id'],
            'title' => ['required', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:8000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in([
                Announcement::CHANNEL_IN_APP,
                Announcement::CHANNEL_EMAIL,
                Announcement::CHANNEL_WHATSAPP,
            ])],
            'segment' => ['nullable', 'array'],
            'segment.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'segment.user_ids' => ['nullable', 'array', 'max:200'],
            'segment.user_ids.*' => ['integer', 'exists:users,id'],
            'segment.plan' => ['nullable', 'string', Rule::in(['', 'free', 'payg', 'annual'])],
            'segment.expiring_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'segment.trade' => ['nullable', 'string', 'max:120'],
            'segment.state' => ['nullable', 'string', 'max:80'],
            'segment.lga' => ['nullable', 'string', 'max:80'],
            'action' => ['required', Rule::in(['draft', 'send', 'schedule'])],
            'send_at' => ['nullable', 'required_if:action,schedule', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $audience = $this->input('audience');
            $channels = $this->input('channels', []);

            if ($audience === Announcement::AUDIENCE_STAFF && in_array(Announcement::CHANNEL_WHATSAPP, $channels, true)) {
                $validator->errors()->add('channels', 'Staff announcements can only use in-app and email.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $segment = $this->input('segment', []);

        if (! is_array($segment)) {
            $segment = [];
        }

        foreach (['plan', 'trade', 'state', 'lga'] as $key) {
            $segment[$key] = trim((string) ($segment[$key] ?? ''));
        }

        if (($segment['expiring_days'] ?? '') === '' || ($segment['expiring_days'] ?? null) === null) {
            unset($segment['expiring_days']);
        }

        $sendAt = $this->input('send_at') ?: null;
        if (is_string($sendAt) && $sendAt !== '') {
            $sendAt = Carbon::parse($sendAt, config('app.display_timezone'))
                ->timezone(config('app.timezone'))
                ->toDateTimeString();
        } else {
            $sendAt = null;
        }

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'subject' => trim((string) $this->input('subject')),
            'body' => trim((string) $this->input('body')),
            'segment' => $segment,
            'channels' => array_values(array_unique($this->input('channels', []))),
            'send_at' => $sendAt,
        ]);
    }
}
