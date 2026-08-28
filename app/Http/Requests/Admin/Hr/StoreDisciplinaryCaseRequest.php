<?php

namespace App\Http\Requests\Admin\Hr;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisciplinaryCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('hr.discipline.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'category' => ['required', 'string', Rule::in(array_keys(config('discipline.categories', [])))],
            'category_label' => ['required_if:category,other', 'nullable', 'string', 'max:80'],
            'severity' => ['required', 'string', Rule::in(array_keys(config('discipline.severities', [])))],
            'incident_on' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:8000'],
            'reason' => ['required', 'string', 'max:1000'],
            'return' => ['nullable', 'in:profile'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $staff = User::query()->find($this->integer('user_id'));
            $owner = User::query()->find($this->integer('owner_id'));

            if ($staff && ! $staff->isStaff()) {
                $validator->errors()->add('user_id', 'A case can only be opened for operations staff.');
            }

            if ($owner && ! $owner->isStaff()) {
                $validator->errors()->add('owner_id', 'The case owner must be a staff member.');
            }
        });
    }
}
