<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DestroyStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isSuperAdmin() ?? false) && $user->canDo('admin.staff.manage');
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

            $given = mb_strtolower(trim((string) $this->input('confirmation')));
            $name = mb_strtolower(trim((string) $staff->name));
            $email = mb_strtolower(trim((string) $staff->email));

            if ($given === '' || ($given !== $name && $given !== $email)) {
                $validator->errors()->add('confirmation', 'Type the staff member’s name or email exactly to confirm.');
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
