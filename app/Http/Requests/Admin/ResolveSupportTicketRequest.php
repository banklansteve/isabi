<?php

namespace App\Http\Requests\Admin;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ResolveSupportTicketRequest extends FormRequest
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
        return [
            'outcome' => ['nullable', Rule::in([
                SupportTicket::OUTCOME_COMPLETED,
                SupportTicket::OUTCOME_ABANDONED,
            ])],
            'body' => ['nullable', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var SupportTicket|null $ticket */
            $ticket = $this->route('ticket');

            if (! $ticket instanceof SupportTicket || $ticket->isClosed()) {
                return;
            }

            $body = trim((string) $this->input('body', ''));
            $recentClose = $ticket->close_message_sent_at
                && $ticket->close_message_sent_at->gt(now()->subMinutes(30));

            if ($body === '' && ! $recentClose) {
                $validator->errors()->add(
                    'body',
                    'Send a closing message to the customer before you close this chat.',
                );
            }
        });
    }
}
