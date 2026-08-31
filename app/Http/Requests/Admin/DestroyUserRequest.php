<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DestroyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isSuperAdmin() || $user?->canDo('admin.users.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:4', 'max:500'],
            'confirmation' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            if (! $user instanceof User) {
                return;
            }

            $expected = mb_strtolower(trim($user->displayBusinessName()));
            $given = mb_strtolower(trim((string) $this->input('confirmation')));

            if ($expected === '' || $given !== $expected) {
                $validator->errors()->add('confirmation', 'Type the user’s display name exactly to confirm.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
            'confirmation' => trim((string) $this->input('confirmation')),
        ]);
    }
}
