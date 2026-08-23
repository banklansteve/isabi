<?php

namespace App\Http\Requests\Admin;

use App\Support\Auth\SessionLifetime;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $settings = $this->input('settings', []);

            if (! is_array($settings)) {
                return;
            }

            $labels = [
                SessionLifetime::KEY_USERS => 'Session lifetime for artisans / users',
                SessionLifetime::KEY_OPERATIONS => 'Session lifetime for operations / staff',
                SessionLifetime::KEY_SUPER_ADMIN => 'Session lifetime for super admin',
            ];

            foreach ($labels as $key => $label) {
                if (! array_key_exists($key, $settings)) {
                    continue;
                }

                $value = $settings[$key];

                if (! is_numeric($value) || (int) $value != $value) {
                    $validator->errors()->add($key, "{$label} must be a whole number of minutes.");

                    continue;
                }

                $minutes = (int) $value;

                if ($minutes < SessionLifetime::MIN_MINUTES || $minutes > SessionLifetime::MAX_MINUTES) {
                    $validator->errors()->add(
                        $key,
                        "{$label} must be between 30 minutes and 365 days.",
                    );
                }
            }
        });
    }
}
