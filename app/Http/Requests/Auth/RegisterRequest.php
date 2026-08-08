<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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
            'trade' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', Rule::in($states)],
            'lga' => ['required', 'string', Rule::in($lgas)],
            'office_address' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(?:\+?234|0)[789][01]\d{8}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = preg_replace('/\s+/', '', (string) $this->input('whatsapp'));

        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'business_name' => trim((string) $this->input('business_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'whatsapp' => $whatsapp,
            'office_address' => trim((string) $this->input('office_address')),
        ]);
    }
}
