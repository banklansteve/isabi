<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUsersRequest extends FormRequest
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
        $rules = [
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:users,id'],
        ];

        if ($this->routeIs('admin.users.bulk-suspend') || $this->routeIs('admin.users.bulk-message')) {
            $rules['reason'] = ['required', 'string', 'min:4', 'max:500'];
        }

        if ($this->routeIs('admin.users.bulk-message')) {
            $rules['subject'] = ['required', 'string', 'max:160'];
            $rules['body'] = ['required', 'string', 'max:8000'];
            $rules['channels'] = ['required', 'array', 'min:1'];
            $rules['channels.*'] = ['in:in_app,email'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'ids' => array_values(array_unique(array_map('intval', $this->input('ids', [])))),
        ];

        if ($this->has('reason')) {
            $merge['reason'] = trim((string) $this->input('reason'));
        }

        $this->merge($merge);
    }
}
