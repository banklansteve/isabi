<?php

namespace App\Http\Requests\Admin;

use App\Support\SupportChat\SupportChatTemplates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCannedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $reply = $this->route('reply');

        if (! $user?->canDo('admin.support.manage') || ! $reply) {
            return false;
        }

        if ($reply->scope === SupportChatTemplates::SCOPE_TEAM || $reply->is_system) {
            return $user->isSuperAdmin();
        }

        return $reply->user_id === $user->id || $user->isSuperAdmin();
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
            'moment' => ['required', 'string', Rule::in(SupportChatTemplates::moments())],
            'scope' => ['required', 'string', Rule::in([SupportChatTemplates::SCOPE_PERSONAL, SupportChatTemplates::SCOPE_TEAM])],
        ];
    }
}
