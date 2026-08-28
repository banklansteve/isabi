<?php

namespace App\Http\Requests\Admin;

use App\Support\SupportChat\SupportChatTemplates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCannedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDo('admin.support.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = collect(config('support.starters', []))->pluck('key')->all();

        return [
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:5000'],
            'topic_key' => ['nullable', 'string', Rule::in($keys)],
            'moment' => ['nullable', 'string', Rule::in(SupportChatTemplates::moments())],
            'scope' => ['nullable', 'string', Rule::in([SupportChatTemplates::SCOPE_PERSONAL, SupportChatTemplates::SCOPE_TEAM])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('scope') === SupportChatTemplates::SCOPE_TEAM && ! $this->user()?->isSuperAdmin()) {
                $validator->errors()->add('scope', 'Only a Super Admin can save team templates.');
            }
        });
    }
}
