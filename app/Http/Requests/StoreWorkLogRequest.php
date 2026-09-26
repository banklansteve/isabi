<?php

namespace App\Http\Requests;

use App\Support\JobCategories;
use App\Support\NigeriaLocations;
use App\Support\WorkLog\JobSubjectPrivacy;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
class StoreWorkLogRequest extends FormRequest
{
    /** How far back a job may be logged (days), inclusive of today. */
    public const MAX_LOOKBACK_DAYS = 14;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minDate = Carbon::today()->subDays(self::MAX_LOOKBACK_DAYS)->toDateString();
        $maxDate = Carbon::today()->toDateString();
        $states = NigeriaLocations::states();
        $state = (string) $this->input('service_state', '');
        $lgas = NigeriaLocations::all()[$state] ?? [];
        $allowedSubs = JobCategories::workLogTradeOptions(
            $this->user()?->trades,
            $this->user()?->trade,
        );

        return [
            'subject' => ['required', 'string', 'min:3', 'max:'.\App\Models\WorkLog::SUBJECT_MAX],
            'description' => ['required', 'string', 'min:3', 'max:'.\App\Models\WorkLog::DESCRIPTION_MAX],
            'worked_on' => ['required', 'date', "after_or_equal:{$minDate}", "before_or_equal:{$maxDate}"],
            'client_name' => ['nullable', 'string', 'max:120'],
            'job_category' => ['nullable', 'string', 'max:160'],
            'job_subcategory' => ['required', 'string', 'max:160', Rule::in($allowedSubs)],
            'service_state' => ['nullable', 'string', Rule::in($states)],
            'service_lga' => array_values(array_filter([
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => filled($this->input('service_state'))),
                filled($state) ? Rule::in($lgas) : null,
            ])),
            'service_city' => ['nullable', 'string', 'max:120'],
            'client_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^(?:\+?234|0)[789][01]\d{8}$/'],
            'from_quote_uid' => ['nullable', 'string', 'max:64'],
            'amount_charged' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'media' => ['nullable', 'array', 'max:8'],
            'media.*' => [
                'file',
                'max:5120', // 5MB in kilobytes
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $days = self::MAX_LOOKBACK_DAYS;

        return [
            'description.required' => 'Tell us what was done — even a short line is enough.',
            'description.min' => 'Add a bit more detail so this entry is meaningful.',
            'subject.required' => 'Add a short subject — this is the title clients and search engines see.',
            'subject.min' => 'Make the subject a little more specific.',
            'subject.max' => 'Keep the subject under '.\App\Models\WorkLog::SUBJECT_MAX.' characters.',
            'worked_on.after_or_equal' => "You can only log jobs from the last {$days} days.",
            'worked_on.before_or_equal' => 'The job date can’t be in the future.',
            'job_category.in' => 'Pick a job category from the list.',
            'job_subcategory.required' => 'Pick which trade this job falls under.',
            'job_subcategory.in' => 'Pick one of the trades from your profile.',
            'service_state.in' => 'Pick a valid Nigerian state.',
            'service_lga.required' => 'Choose the LGA for this job location.',
            'service_lga.in' => 'Pick a valid LGA for the selected state.',
            'client_whatsapp.regex' => 'Enter a valid Nigerian WhatsApp number (e.g. 0803… or +234803…).',
            'media.max' => 'You can attach up to 8 photos or videos.',
            'media.*.max' => 'Each file must be smaller than 5MB.',
            'media.*.mimetypes' => 'Only images (JPG, PNG, WebP, GIF) and videos (MP4, MOV, WebM) are allowed.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('client_whatsapp');

        if (is_string($whatsapp)) {
            $whatsapp = preg_replace('/\s+/', '', $whatsapp) ?: null;
        }

        $amount = $this->input('amount_charged');
        if ($amount === '' || $amount === null) {
            $amount = null;
        }

        $nullable = static fn ($value) => is_string($value) ? (trim($value) ?: null) : $value;

        $sub = $nullable($this->input('job_subcategory'));
        $category = $nullable($this->input('job_category'));
        if ($sub && ! $category) {
            $category = JobCategories::parentFor($sub) ?? 'Other';
        }

        $this->merge([
            'subject' => $nullable($this->input('subject')),
            'description' => trim((string) $this->input('description')),
            'client_name' => $nullable($this->input('client_name')),
            'job_category' => $category,
            'job_subcategory' => $sub,
            'service_state' => $nullable($this->input('service_state')),
            'service_lga' => $nullable($this->input('service_lga')),
            'service_city' => $nullable($this->input('service_city')),
            'client_whatsapp' => $whatsapp ?: null,
            'amount_charged' => $amount,
        ]);
    }

    public function amountInKobo(): ?int
    {
        $amount = $this->input('amount_charged');
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round(((float) $amount) * 100);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('subject')) {
                return;
            }

            $message = JobSubjectPrivacy::violation(
                $this->input('subject'),
                $this->input('client_name'),
                $this->input('client_whatsapp'),
            );

            if ($message) {
                $validator->errors()->add('subject', $message);
            }
        });
    }
}
