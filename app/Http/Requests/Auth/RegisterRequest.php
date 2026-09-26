<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\JobCategories;
use App\Support\NigeriaLocations;
use App\Support\ProfileSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
        $states = NigeriaLocations::states();
        $state = (string) $this->input('state');
        $lgas = NigeriaLocations::all()[$state] ?? [];

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'business_name' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $slug = ProfileSlug::normalize((string) $value);
                    if ($slug === '') {
                        $fail('Use letters or numbers in your business name so we can build your public URL.');

                        return;
                    }
                    if (ProfileSlug::isReserved($slug)) {
                        $fail('That name is reserved. Try a different business name.');
                    }
                },
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'job_category' => ['nullable', 'string', 'max:160', Rule::in(JobCategories::parents())],
            'trades' => ['required', 'array', 'min:1', 'max:12'],
            'trades.*' => ['required', 'string', 'max:120'],
            'trade' => ['required', 'string', 'max:120'],
            'skills' => ['nullable', 'array', 'max:15'],
            'skills.*' => ['required', 'string', 'max:40'],
            'state' => ['required', 'string', Rule::in($states)],
            'lga' => ['required', 'string', Rule::in($lgas)],
            'office_address' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(?:\+?234|0)[789][01]\d{8}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'ref' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Enter a valid Nigerian WhatsApp number (e.g. 0803… or +234803…).',
            'lga.in' => 'Select a local government that matches the state you chose.',
            'business_name.required' => 'Add a business name — this becomes your public page URL.',
            'trades.required' => 'Pick at least one trade or specialty.',
            'trades.min' => 'Pick at least one trade or specialty.',
            'trades.max' => 'You can select up to 12 trades.',
            'skills.max' => 'You can highlight up to 15 skills.',
            'skills.*.max' => 'Each skill must be 40 characters or fewer.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = preg_replace('/\s+/', '', (string) $this->input('whatsapp'));

        $trades = collect($this->input('trades', []))
            ->map(fn ($trade) => trim((string) $trade))
            ->filter()
            ->unique(fn ($trade) => mb_strtolower($trade))
            ->take(12)
            ->values()
            ->all();

        // Backward-compatible: accept single `trade` when `trades` omitted.
        if ($trades === [] && filled($this->input('trade'))) {
            $trades = [trim((string) $this->input('trade'))];
        }

        $skills = collect($this->input('skills', []))
            ->map(fn ($skill) => trim((string) $skill))
            ->filter()
            ->unique(fn ($skill) => mb_strtolower($skill))
            ->take(15)
            ->values()
            ->all();

        $primary = $trades[0] ?? trim((string) $this->input('trade'));

        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'business_name' => trim((string) $this->input('business_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'whatsapp' => $whatsapp,
            'office_address' => trim((string) $this->input('office_address')),
            'ref' => trim((string) $this->input('ref')) ?: null,
            'job_category' => trim((string) $this->input('job_category')) ?: null,
            'trades' => $trades,
            'trade' => $primary,
            'skills' => $skills,
        ]);
    }
}
