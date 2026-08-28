<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveBillingIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('admin.billing_issues.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['resolved', 'dismissed'])],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
