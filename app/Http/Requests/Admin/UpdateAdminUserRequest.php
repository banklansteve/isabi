<?php

namespace App\Http\Requests\Admin;

use App\Support\NigeriaLocations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
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
        $states = NigeriaLocations::states();
        $state = (string) $this->input('state');
        $lgas = NigeriaLocations::all()[$state] ?? [];

        return [
            'reason' => ['required', 'string', 'min:4', 'max:500'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'business_name' => ['required', 'string', 'max:120'],
            'trade' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^(?:\+?234|0)[789][01]\d{8}$/'],
            'state' => ['nullable', 'string', Rule::in($states)],
            'lga' => ['nullable', 'string', Rule::in($lgas)],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Add a reason so this profile edit is auditable.',
            'whatsapp.regex' => 'Enter a valid Nigerian WhatsApp number (e.g. 0803… or +234803…).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = preg_replace('/\s+/', '', (string) $this->input('whatsapp'));

        $this->merge([
            'reason' => trim((string) $this->input('reason')),
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'business_name' => trim((string) $this->input('business_name')),
            'bio' => trim((string) $this->input('bio')) ?: null,
            'whatsapp' => $whatsapp ?: null,
            'slug' => strtolower(trim((string) $this->input('slug'))) ?: null,
        ]);
    }
}
