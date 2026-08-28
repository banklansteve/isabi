<?php

namespace App\Support\StaffChat;

use App\Models\StaffConversation;
use App\Models\StaffConversationParticipant;
use App\Models\StaffMessage;
use App\Models\User;
use App\Support\Chat\MessageReactionService;
use Illuminate\Support\Str;

class StaffChatPresenter
{
    public function __construct(
        private readonly StaffChatService $chat,
        private readonly MessageReactionService $reactions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inboxItem(StaffConversation $conversation, User $viewer): array
    {
        $conversation->loadMissing([
            'participants:id,uid,name,first_name,last_name,avatar_url,role',
            'latestMessage.user:id,uid,name,first_name,last_name,avatar_url',
            'participantRows' => fn ($q) => $q->where('user_id', $viewer->id),
        ]);

        $latest = $conversation->latestMessage;
        $pivot = $conversation->participantRows->first();
        $unread = $this->isUnread($latest, $pivot, $viewer);
        $title = $this->title($conversation, $viewer);

        return [
            'id' => $conversation->id,
            'uid' => $conversation->uid,
            'type' => $conversation->type,
            'title' => $title,
            'subtitle' => $this->preview($latest),
            'unread' => $unread,
            'href' => route('admin.asap.show', $conversation),
            'icon' => $conversation->isAsap() ? 'ti ti-bolt' : 'ti ti-user',
            'when' => $latest?->created_at
                ?->timezone((string) config('app.display_timezone'))
                ->diffForHumans(short: true),
            'when_iso' => $latest?->created_at?->toIso8601String()
                ?? $conversation->updated_at?->toIso8601String(),
            'peer' => $conversation->isDirect() ? $this->peer($conversation, $viewer) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function thread(StaffConversation $conversation, User $viewer): array
    {
        $conversation->load([
            'participants:id,uid,name,first_name,last_name,avatar_url,role,email',
            'messages' => fn ($q) => $q->with([
                'user:id,uid,name,first_name,last_name,avatar_url',
                'reactions',
            ])->orderBy('id'),
        ]);

        return [
            ...$this->inboxItem($conversation, $viewer),
            'messages' => $conversation->messages
                ->map(fn (StaffMessage $message) => $this->message($message, $viewer))
                ->values()
                ->all(),
            'typing' => $this->chat->typingUsers($conversation, $viewer),
            'participants' => $conversation->participants
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'initials' => $this->initials($user),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function message(StaffMessage $message, User $viewer): array
    {
        $message->loadMissing(['user:id,uid,name,first_name,last_name,avatar_url', 'reactions']);

        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => (int) $message->user_id === (int) $viewer->id,
            'user' => $message->user ? [
                'id' => $message->user->id,
                'uid' => $message->user->uid,
                'name' => $message->user->name,
                'avatar_url' => $message->user->avatar_url,
                'initials' => $this->initials($message->user),
            ] : null,
            'when' => $message->created_at
                ?->timezone((string) config('app.display_timezone'))
                ->format('g:ia'),
            'iso' => $message->created_at?->toIso8601String(),
            'attachment' => $this->attachment($message),
            'reactions' => $this->reactions->present($message, $viewer),
        ];
    }

    private function isUnread(?StaffMessage $latest, ?StaffConversationParticipant $pivot, User $viewer): bool
    {
        if (! $latest || (int) $latest->user_id === (int) $viewer->id) {
            return false;
        }

        return ! $pivot?->last_read_at || $latest->created_at?->gt($pivot->last_read_at);
    }

    private function title(StaffConversation $conversation, User $viewer): string
    {
        if ($conversation->isAsap()) {
            return 'ASAP';
        }

        $peer = $this->peer($conversation, $viewer);

        return $peer['name'] ?? 'Direct message';
    }

    /**
     * @return array{id: int, uid: string|null, name: string, avatar_url: string|null, initials: string}|null
     */
    private function peer(StaffConversation $conversation, User $viewer): ?array
    {
        $peer = $conversation->participants->first(fn (User $user) => ! $user->is($viewer));

        if (! $peer) {
            return null;
        }

        return [
            'id' => $peer->id,
            'uid' => $peer->uid,
            'name' => $peer->name,
            'avatar_url' => $peer->avatar_url,
            'initials' => $this->initials($peer),
        ];
    }

    private function preview(?StaffMessage $message): string
    {
        if (! $message) {
            return 'No messages yet';
        }

        if (filled($message->body)) {
            return Str::limit(trim((string) $message->body), 90);
        }

        return $message->attachment_name ? 'Attachment' : 'New message';
    }

    /**
     * @return array{url: string|null, name: string|null, mime: string|null, image: bool}|null
     */
    private function attachment(StaffMessage $message): ?array
    {
        if (! $message->attachment_url && ! $message->attachment_path) {
            return null;
        }

        return [
            'url' => $message->attachment_url,
            'name' => $message->attachment_name,
            'mime' => $message->attachment_mime,
            'image' => str_starts_with((string) $message->attachment_mime, 'image/'),
            'gif' => ($message->attachment_mime === 'image/gif') || str_ends_with(strtolower((string) $message->attachment_name), '.gif'),
        ];
    }

    private function initials(User $user): string
    {
        $source = trim(($user->first_name ?: '').' '.($user->last_name ?: '')) ?: (string) $user->name;

        $initials = Str::of($source)
            ->explode(' ')
            ->filter()
            ->map(fn ($part) => Str::substr((string) $part, 0, 1))
            ->take(2)
            ->implode('');

        return Str::upper((string) $initials) ?: 'I';
    }
}
