<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DestroyStaffRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:4', 'max:500'],
            'confirmation' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $staff = $this->route('staff');
            if (! $staff instanceof User) {
                return;
            }

            $expected = mb_strtolower(trim($staff->name));
            $given = mb_strtolower(trim((string) $this->input('confirmation')));

            if ($expected === '' || $given !== $expected) {
                $validator->errors()->add('confirmation', 'Type the staff member’s name exactly to confirm.');
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
