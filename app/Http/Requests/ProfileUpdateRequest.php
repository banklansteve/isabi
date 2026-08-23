<?php

namespace App\Http\Requests;

use App\Support\NigeriaLocations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
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
        $section = (string) $this->input('section', 'basics');
        $locations = NigeriaLocations::all();
        $states = NigeriaLocations::states();
        $state = (string) $this->input('state');
        $lgas = $locations[$state] ?? [];
        $allLgas = collect($locations)->flatten()->unique()->values()->all();
        $maxCredentials = (int) config('credentials.max', 6);
        $currentYear = (int) now()->year;

        $sectionRule = ['required', 'string', Rule::in(['basics', 'expertise', 'contact'])];

        return match ($section) {
            'expertise' => [
                'section' => $sectionRule,
                'skills' => ['nullable', 'array', 'max:8'],
                'skills.*' => ['required', 'string', 'max:40'],
                'credentials' => ['nullable', 'array', 'max:'.$maxCredentials],
                'credentials.*.title' => ['required', 'string', 'max:90'],
                'credentials.*.issuer' => ['nullable', 'string', 'max:90'],
                'credentials.*.reference' => ['nullable', 'string', 'max:60'],
                'credentials.*.year' => ['nullable', 'integer', 'min:1960', 'max:'.$currentYear],
                'experience_started_year' => ['nullable', 'integer', 'min:1960', 'max:'.$currentYear],
            ],
            'contact' => [
                'section' => $sectionRule,
                'state' => ['required', 'string', Rule::in($states)],
                'lga' => ['required', 'string', Rule::in($lgas)],
                'office_address' => ['required', 'string', 'max:255'],
                'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(?:\+?234|0)[789][01]\d{8}$/'],
                'coverage_areas' => ['nullable', 'array', 'max:10'],
                'coverage_areas.*' => ['required', 'string', Rule::in($allLgas)],
                'coverage_note' => ['nullable', 'string', 'max:180'],
            ],
            default => [
                'section' => $sectionRule,
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'business_name' => ['required', 'string', 'max:120'],
                'trade' => ['required', 'string', 'max:120'],
                'bio' => ['nullable', 'string', 'max:500'],
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Enter a valid Nigerian WhatsApp number (e.g. 0803… or +234803…).',
            'lga.in' => 'Select a local government that matches the state you chose.',
            'skills.max' => 'You can highlight up to 8 skills on your page.',
            'skills.*.max' => 'Each skill must be 40 characters or fewer.',
            'credentials.max' => 'You can list up to :max credentials on your page.',
            'credentials.*.title.required' => 'Give the credential a name.',
            'credentials.*.year.min' => 'Enter a realistic year.',
            'credentials.*.year.max' => 'The year cannot be in the future.',
            'experience_started_year.max' => 'The year you started cannot be in the future.',
            'coverage_areas.max' => 'You can list up to 10 coverage areas.',
            'coverage_areas.*.in' => 'Choose coverage areas from the list of Nigerian LGAs.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $section = (string) $this->input('section', 'basics');
        $merge = ['section' => $section];

        if ($section === 'basics') {
            $merge += [
                'first_name' => trim((string) $this->input('first_name')),
                'last_name' => trim((string) $this->input('last_name')),
                'business_name' => trim((string) $this->input('business_name')),
                'bio' => trim((string) $this->input('bio')) ?: null,
            ];
        }

        if ($section === 'expertise') {
            $skills = collect($this->input('skills', []))
                ->map(fn ($skill) => trim((string) $skill))
                ->filter()
                ->unique(fn ($skill) => mb_strtolower($skill))
                ->take(8)
                ->values()
                ->all();

            $credentials = collect($this->input('credentials', []))
                ->map(fn ($entry) => [
                    'title' => trim((string) ($entry['title'] ?? '')),
                    'issuer' => trim((string) ($entry['issuer'] ?? '')) ?: null,
                    'reference' => trim((string) ($entry['reference'] ?? '')) ?: null,
                    'year' => filled($entry['year'] ?? null) ? (int) $entry['year'] : null,
                ])
                ->filter(fn (array $entry) => $entry['title'] !== '')
                ->unique(fn (array $entry) => mb_strtolower($entry['title'].'|'.$entry['issuer']))
                ->take((int) config('credentials.max', 6))
                ->values()
                ->all();

            $merge += [
                'skills' => $skills,
                'credentials' => $credentials,
                'experience_started_year' => filled($this->input('experience_started_year'))
                    ? (int) $this->input('experience_started_year')
                    : null,
            ];
        }

        if ($section === 'contact') {
            $whatsapp = preg_replace('/\s+/', '', (string) $this->input('whatsapp'));
            $coverageAreas = collect($this->input('coverage_areas', []))
                ->map(fn ($area) => trim((string) $area))
                ->filter()
                ->unique()
                ->take(10)
                ->values()
                ->all();

            $merge += [
                'whatsapp' => $whatsapp,
                'office_address' => trim((string) $this->input('office_address')),
                'coverage_areas' => $coverageAreas,
                'coverage_note' => trim((string) $this->input('coverage_note')) ?: null,
            ];
        }

        $this->merge($merge);
    }
}
