<?php

namespace App\Http\Requests;

use App\Models\CareerVacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCareerApplicationRequest extends FormRequest
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
        return [
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'city' => ['required', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'portfolio_url' => ['nullable', 'url', 'max:500'],

            'education' => ['required', 'array', 'min:1'],
            'education.*.qualification' => ['required', 'string', 'max:160'],
            'education.*.institution' => ['required', 'string', 'max:190'],
            'education.*.field_of_study' => ['nullable', 'string', 'max:160'],
            'education.*.graduation_year' => ['nullable', 'integer', 'min:1960', 'max:2100'],
            'education.*.class_of_degree' => ['nullable', 'string', 'max:80'],
            'certifications' => ['nullable', 'string', 'max:4000'],
            'secondary_education' => ['nullable', 'string', 'max:1000'],
            'primary_education' => ['nullable', 'string', 'max:1000'],

            'no_work_experience' => ['sometimes', 'boolean'],
            'work_experience' => ['nullable', 'array'],
            'work_experience.*.company' => ['nullable', 'string', 'max:160'],
            'work_experience.*.role' => ['nullable', 'string', 'max:160'],
            'work_experience.*.duration' => ['nullable', 'string', 'max:120'],
            'work_experience.*.description' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:80'],
            'skills_other' => ['nullable', 'string', 'max:1000'],
            'achievements' => ['nullable', 'string', 'max:4000'],

            'nysc_status' => [
                'required',
                'string',
                Rule::in(['completed', 'exempted', 'currently_serving', 'not_yet_due']),
            ],
            'willing_to_relocate' => ['nullable', 'boolean'],
            'preferred_work_mode' => ['nullable', 'string', Rule::in(CareerVacancy::WORK_MODES)],
            'earliest_availability' => ['nullable', 'string', 'max:160'],

            'expected_salary' => ['nullable', 'string', 'max:120'],
            'notice_period' => ['nullable', 'string', 'max:120'],
            'why_this_role' => ['nullable', 'string', 'max:800'],

            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'work_sample_url' => ['nullable', 'url', 'max:500'],
            'references' => ['nullable', 'array', 'max:2'],
            'references.*.name' => ['nullable', 'string', 'max:120'],
            'references.*.relationship' => ['nullable', 'string', 'max:120'],
            'references.*.contact' => ['nullable', 'string', 'max:160'],

            'ndpr_consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ndpr_consent.accepted' => 'You must consent to data processing under the Nigeria Data Protection Act (NDPR) to submit this application.',
            'cv.required' => 'Please upload your CV or resume (PDF or Word).',
            'education.required' => 'Add at least one academic qualification.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('no_work_experience')) {
                return;
            }

            $entries = collect($this->input('work_experience', []))
                ->filter(fn ($row) => filled($row['company'] ?? null) || filled($row['role'] ?? null));

            if ($entries->isEmpty()) {
                $validator->errors()->add(
                    'work_experience',
                    'Add at least one work experience entry, or tick “No prior work experience”.',
                );
            }
        });
    }
}
