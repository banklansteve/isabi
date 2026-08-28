<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendOpsTemplatedMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && (
            $user->canDo('admin.ops_messages.send')
            || $user->canDo('admin.users.manage')
            || $user->canDo('admin.messaging.manage')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'template_uid' => ['required', 'string', Rule::exists('ops_message_templates', 'uid')],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:5000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in([Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL])],
        ];
    }
}
