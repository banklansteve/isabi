<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewMessageSettingsRequest extends FormRequest
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
        $max = (int) config('review_messages.max_template_length', 700);

        return [
            'review_invite_template' => ['nullable', 'string', 'max:'.$max],
            'review_reminder_template' => ['nullable', 'string', 'max:'.$max],
            'review_reminder_days' => ['nullable', 'integer', 'min:0', 'max:14'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'review_invite_template.max' => 'Keep the invite message under :max characters so it fits WhatsApp comfortably.',
            'review_reminder_template.max' => 'Keep the reminder message under :max characters so it fits WhatsApp comfortably.',
            'review_reminder_days.max' => 'Reminders should go out within two weeks of the first invite.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $invite = trim((string) $this->input('review_invite_template', ''));
        $reminder = trim((string) $this->input('review_reminder_template', ''));
        $days = $this->input('review_reminder_days');

        $this->merge([
            'review_invite_template' => $invite !== '' ? $invite : null,
            'review_reminder_template' => $reminder !== '' ? $reminder : null,
            'review_reminder_days' => $days === '' || $days === null ? null : (int) $days,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['review_invite_template', 'review_reminder_template'] as $field) {
                $value = (string) ($this->input($field) ?? '');
                if ($value !== '' && ! str_contains($value, '{link}')) {
                    $validator->errors()->add(
                        $field,
                        'Include {link} so the client can open their review page.',
                    );
                }
            }
        });
    }
}
