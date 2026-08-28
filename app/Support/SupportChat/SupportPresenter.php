<?php

namespace App\Support\SupportChat;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Support\Chat\MessageReactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SupportPresenter
{
    public function __construct(
        private readonly SupportPresence $presence,
        private readonly MessageReactionService $reactions,
    ) {}

    /**
     * Customer-safe conversation. Never includes notes, tags, or routing metadata.
     *
     * @return array<string, mixed>
     */
    public function customer(?SupportTicket $ticket, User $customer): array
    {
        $ticket?->loadMissing(['assignedTo:id,uid,name,first_name']);

        $available = $this->presence->teamAvailable();
        $state = $this->customerState($ticket, $available);
        $messages = $ticket
            ? $ticket->messages()
                ->where('kind', SupportTicketMessage::KIND_MESSAGE)
                ->with('reactions')
                ->orderBy('id')
                ->get()
            : collect();

        $lastOwn = $messages->last(fn (SupportTicketMessage $message) => ! $message->is_staff);
        $seen = $lastOwn && $ticket?->staff_last_read_at
            && $ticket->staff_last_read_at->gte($lastOwn->created_at);

        return [
            'id' => $ticket?->id,
            'state' => $state,
            'state_label' => $this->customerStateLabel($state),
            'staff_available' => $available,
            'offline_copy' => (string) config('support.offline_copy'),
            'availability_copy' => $available
                ? (string) config('support.online_copy')
                : (string) config('support.offline_copy'),
            'agent_first_name' => $this->visibleAgentName($ticket, $state),
            'messages' => $messages->map(fn (SupportTicketMessage $message) => $this->customerMessage($message, $customer))->all(),
            'seen' => (bool) $seen,
            'typing' => $ticket ? $this->presence->isTyping($ticket->id, 'staff') : false,
            'csat' => $this->customerCsat($ticket),
            'starters' => $this->starters(),
            'poll_ms' => (int) config('support.poll_interval_ms', 8000),
            'max_attachment_kb' => (int) config('support.max_attachment_kb', 8192),
            'allowed_mimes' => config('support.allowed_mimes', []),
            'customer_name' => $customer->first_name ?: str($customer->name)->before(' ')->toString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inboxItem(SupportTicket $ticket): array
    {
        $ticket->loadMissing(['user:id,uid,name,email,business_name,avatar_url,first_name,last_name', 'assignedTo:id,uid,name']);
        $preview = $ticket->relationLoaded('messages')
            ? $ticket->messages->where('kind', SupportTicketMessage::KIND_MESSAGE)->last()
            : $ticket->messages()->where('kind', SupportTicketMessage::KIND_MESSAGE)->latest('id')->first();

        $waiting = $this->waitingSeconds($ticket);

        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject ?: 'Support chat',
            'preview' => $preview?->body
                ? str($preview->body)->limit(90)->toString()
                : ($preview?->attachment_name ? 'Attachment' : 'New conversation'),
            'status' => $ticket->status,
            'queue_status' => $this->queueStatus($ticket),
            'unread' => $this->unreadForStaff($ticket),
            'waiting_seconds' => $waiting,
            'waiting_label' => $this->waitingLabel($waiting),
            'waiting_hot' => $waiting >= ((int) config('support.hot_wait_minutes', 30) * 60),
            'last_activity_iso' => ($ticket->last_reply_at ?? $ticket->updated_at)?->toIso8601String(),
            'last_activity' => ($ticket->last_reply_at ?? $ticket->updated_at)
                ?->timezone((string) config('app.display_timezone'))
                ->diffForHumans(),
            'topic_key' => $ticket->topic_key,
            'tags' => $ticket->tags ?? [],
            'user' => $ticket->user ? [
                'id' => $ticket->user->id,
                'uid' => $ticket->user->uid,
                'name' => $ticket->user->displayBusinessName(),
                'email' => $ticket->user->email,
                'avatar_url' => $ticket->user->avatar_url,
            ] : null,
            'assigned' => $ticket->assignedTo ? [
                'id' => $ticket->assignedTo->id,
                'uid' => $ticket->assignedTo->uid,
                'name' => $ticket->assignedTo->name,
                'online' => $this->presence->staffOnline($ticket->assignedTo),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function staffThread(SupportTicket $ticket): array
    {
        $ticket->load([
            'user:id,uid,name,email,business_name,avatar_url,first_name,last_name,whatsapp',
            'assignedTo:id,uid,name',
            'messages.user:id,name',
            'messages.reactions',
        ]);

        $public = $ticket->messages
            ->where('kind', SupportTicketMessage::KIND_MESSAGE)
            ->values();
        $notes = $ticket->messages
            ->where('kind', SupportTicketMessage::KIND_NOTE)
            ->values();

        $lastCustomer = $public->last(fn (SupportTicketMessage $message) => ! $message->is_staff);
        $seen = $lastCustomer && $ticket->customer_last_read_at
            && $ticket->customer_last_read_at->gte($lastCustomer->created_at);

        $viewer = Auth::user();

        return [
            ...$this->inboxItem($ticket),
            'messages' => $public->map(fn (SupportTicketMessage $message) => $this->staffMessage($message, $viewer))->all(),
            'notes' => $notes->map(fn (SupportTicketMessage $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'author' => $message->user?->name ?? 'Staff',
                'when' => $this->when($message->created_at),
                'iso' => $message->created_at?->toIso8601String(),
            ])->all(),
            'seen' => (bool) $seen,
            'typing' => $this->presence->isTyping($ticket->id, 'customer'),
            'csat_score' => $ticket->csat_score,
            'csat_comment' => $ticket->csat_comment,
            'user' => $ticket->user ? [
                'id' => $ticket->user->id,
                'uid' => $ticket->user->uid,
                'name' => $ticket->user->displayBusinessName(),
                'email' => $ticket->user->email,
                'avatar_url' => $ticket->user->avatar_url,
                'whatsapp' => $ticket->user->whatsapp,
            ] : null,
        ];
    }

    /**
     * @return list<array{key: string, label: string, message: ?string}>
     */
    public function starters(): array
    {
        return collect(config('support.starters', []))
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'label' => $item['label'],
                'message' => $item['message'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function topicOptions(): array
    {
        return collect(config('support.starters', []))
            ->filter(fn (array $item) => ($item['key'] ?? '') !== 'other')
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'label' => $item['label'],
            ])
            ->values()
            ->all();
    }

    private function customerMessage(SupportTicketMessage $message, User $viewer): array
    {
        return [
            'id' => $message->id,
            'role' => $message->is_staff ? 'support' : 'user',
            'body' => (string) $message->body,
            'time' => $this->clock($message->created_at),
            'iso' => $message->created_at?->toIso8601String(),
            'attachment' => $this->attachment($message),
            'reactions' => $this->reactions->present($message, $viewer),
        ];
    }

    private function staffMessage(SupportTicketMessage $message, ?User $viewer): array
    {
        return [
            'id' => $message->id,
            'role' => $message->is_staff ? 'support' : 'user',
            'body' => (string) $message->body,
            'author' => $message->user?->name,
            'time' => $this->clock($message->created_at),
            'when' => $this->when($message->created_at),
            'iso' => $message->created_at?->toIso8601String(),
            'attachment' => $this->attachment($message),
            'reactions' => $this->reactions->present($message, $viewer),
        ];
    }

    private function attachment(SupportTicketMessage $message): ?array
    {
        if (! $message->attachment_url && ! $message->attachment_path) {
            return null;
        }

        return [
            'url' => $message->attachment_url,
            'name' => $message->attachment_name,
            'mime' => $message->attachment_mime,
            'image' => str_starts_with((string) $message->attachment_mime, 'image/'),
        ];
    }

    private function customerState(?SupportTicket $ticket, bool $available): string
    {
        if (! $ticket || $ticket->status === SupportTicket::STATUS_RESOLVED) {
            return $ticket?->status === SupportTicket::STATUS_RESOLVED ? 'resolved' : 'idle';
        }

        if (! $available) {
            return 'offline';
        }

        $assignee = $ticket->assignedTo;

        if ($assignee && $this->presence->staffOnline($assignee)) {
            return 'connected';
        }

        if ($ticket->assigned_to_user_id && ! ($assignee && $this->presence->staffOnline($assignee))) {
            return 'next_agent';
        }

        return 'waiting';
    }

    private function customerStateLabel(string $state): string
    {
        return match ($state) {
            'connected' => (string) config('support.connected_copy'),
            'resolved' => (string) config('support.resolved_copy'),
            'next_agent' => (string) config('support.next_agent_copy'),
            'offline' => 'Team offline',
            default => (string) config('support.waiting_copy'),
        };
    }

    private function visibleAgentName(?SupportTicket $ticket, string $state): ?string
    {
        if ($state !== 'connected' || ! $ticket?->assignedTo) {
            return null;
        }

        return $ticket->assignedTo->first_name
            ?: str($ticket->assignedTo->name)->before(' ')->toString();
    }

    private function customerCsat(?SupportTicket $ticket): array
    {
        if (! $ticket || $ticket->status !== SupportTicket::STATUS_RESOLVED) {
            return ['prompt' => false, 'score' => null];
        }

        return [
            'prompt' => $ticket->csat_score === null && $ticket->csat_dismissed_at === null,
            'score' => $ticket->csat_score,
        ];
    }

    private function queueStatus(SupportTicket $ticket): string
    {
        if ($ticket->status === SupportTicket::STATUS_RESOLVED) {
            return 'resolved';
        }

        if ($ticket->status === SupportTicket::STATUS_PENDING) {
            return 'awaiting_customer';
        }

        if ($ticket->status === SupportTicket::STATUS_NEW || ! $ticket->first_response_at) {
            return 'new';
        }

        return 'open';
    }

    private function unreadForStaff(SupportTicket $ticket): bool
    {
        if (! $ticket->last_customer_message_at) {
            return $ticket->status !== SupportTicket::STATUS_RESOLVED;
        }

        if (! $ticket->staff_last_read_at) {
            return true;
        }

        return $ticket->last_customer_message_at->gt($ticket->staff_last_read_at);
    }

    private function waitingSeconds(SupportTicket $ticket): int
    {
        if (in_array($ticket->status, [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_PENDING], true)) {
            return 0;
        }

        $from = $ticket->last_customer_message_at ?? $ticket->created_at;

        return max(0, (int) ($from?->diffInSeconds(now()) ?? 0));
    }

    private function waitingLabel(int $seconds): string
    {
        if ($seconds <= 0) {
            return '';
        }

        if ($seconds < 60) {
            return 'Just now';
        }

        if ($seconds < 3600) {
            return (int) floor($seconds / 60).'m waiting';
        }

        return (int) floor($seconds / 3600).'h waiting';
    }

    private function clock(?Carbon $at): string
    {
        return $at?->timezone((string) config('app.display_timezone'))->format('g:ia') ?? '';
    }

    private function when(?Carbon $at): string
    {
        return $at?->timezone((string) config('app.display_timezone'))->format('j M · g:ia') ?? '';
    }
}
