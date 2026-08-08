<?php

namespace App\Http\Requests;

use App\Support\JobCategories;
use App\Support\NigeriaLocations;
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
        $parent = (string) $this->input('job_category', '');
        $subs = JobCategories::subcategoriesFor($parent);

        return [
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'worked_on' => ['required', 'date', "after_or_equal:{$minDate}", "before_or_equal:{$maxDate}"],
            'client_name' => ['nullable', 'string', 'max:120'],
            'job_category' => ['nullable', 'string', 'max:160', Rule::in(JobCategories::parents())],
            'job_subcategory' => array_values(array_filter([
                'nullable',
                'string',
                'max:160',
                Rule::requiredIf(fn () => filled($this->input('job_category'))),
                filled($parent) ? Rule::in($subs) : null,
            ])),
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
            'worked_on.after_or_equal' => "You can only log jobs from the last {$days} days.",
            'worked_on.before_or_equal' => 'The job date can’t be in the future.',
            'job_category.in' => 'Pick a job category from the list.',
            'job_subcategory.required' => 'Pick a subcategory for this job type.',
            'job_subcategory.in' => 'Pick a subcategory that matches the category you chose.',
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

        $this->merge([
            'description' => trim((string) $this->input('description')),
            'client_name' => $nullable($this->input('client_name')),
            'job_category' => $nullable($this->input('job_category')),
            'job_subcategory' => $nullable($this->input('job_subcategory')),
            'service_state' => $nullable($this->input('service_state')),
            'service_lga' => $nullable($this->input('service_lga')),
            'service_city' => $nullable($this->input('service_city')),
            'client_whatsapp' => $whatsapp ?: null,
            'amount_charged' => $amount,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $files = $this->file('media', []);

            foreach ($files as $index => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $mime = (string) $file->getMimeType();
                $isImage = str_starts_with($mime, 'image/');
                $isVideo = str_starts_with($mime, 'video/');

                if (! $isImage && ! $isVideo) {
                    $validator->errors()->add("media.{$index}", 'Only images and videos are allowed.');
                }
            }

            if (filled($this->input('service_lga')) && blank($this->input('service_state'))) {
                $validator->errors()->add('service_state', 'Choose a state before picking an LGA.');
            }

            if (filled($this->input('job_subcategory')) && blank($this->input('job_category'))) {
                $validator->errors()->add('job_category', 'Choose a category before picking a subcategory.');
            }
        });
    }

    /**
     * Amount in kobo for storage, or null.
     */
    public function amountInKobo(): ?int
    {
        $amount = $this->validated('amount_charged');

        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round(((float) $amount) * 100);
    }
}
