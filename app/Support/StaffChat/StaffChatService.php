<?php

namespace App\Support\StaffChat;

use App\Models\StaffConversation;
use App\Models\StaffConversationParticipant;
use App\Models\StaffMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffChatService
{
    public function __construct(
        private readonly StaffChatAttachmentService $attachments,
        private readonly StaffChatNotifier $notifier,
    ) {}

    public function ensureAsap(): StaffConversation
    {
        $conversation = StaffConversation::query()
            ->where('type', StaffConversation::TYPE_ASAP)
            ->first();

        if (! $conversation) {
            $conversation = StaffConversation::query()->create([
                'uid' => $this->uid(),
                'type' => StaffConversation::TYPE_ASAP,
                'name' => 'ASAP',
            ]);
        }

        $this->syncAsapParticipants($conversation);

        return $conversation->fresh(['participants', 'latestMessage.user']) ?? $conversation;
    }

    public function syncAsapParticipants(StaffConversation $conversation): void
    {
        $staffIds = User::query()
            ->staff()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existing = StaffConversationParticipant::query()
            ->where('staff_conversation_id', $conversation->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach (array_diff($staffIds, $existing) as $userId) {
            StaffConversationParticipant::query()->firstOrCreate([
                'staff_conversation_id' => $conversation->id,
                'user_id' => $userId,
            ]);
        }
    }

    public function directBetween(User $a, User $b): StaffConversation
    {
        abort_unless($a->isStaff() && $b->isStaff(), 403);
        abort_if($a->is($b), 422, 'You cannot message yourself.');

        $existing = StaffConversation::query()
            ->where('type', StaffConversation::TYPE_DIRECT)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $b->id))
            ->withCount('participants')
            ->get()
            ->first(fn (StaffConversation $conversation) => (int) $conversation->participants_count === 2);

        if ($existing) {
            return $existing->load(['participants', 'latestMessage.user']);
        }

        return DB::transaction(function () use ($a, $b) {
            $conversation = StaffConversation::query()->create([
                'uid' => $this->uid(),
                'type' => StaffConversation::TYPE_DIRECT,
                'name' => null,
            ]);

            foreach ([$a->id, $b->id] as $userId) {
                StaffConversationParticipant::query()->create([
                    'staff_conversation_id' => $conversation->id,
                    'user_id' => $userId,
                ]);
            }

            return $conversation->load(['participants', 'latestMessage.user']);
        });
    }

    /**
     * @return Collection<int, StaffConversation>
     */
    public function inboxFor(User $user): Collection
    {
        $this->ensureAsap();

        return StaffConversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participants:id,uid,name,first_name,last_name,avatar_url,role',
                'latestMessage.user:id,uid,name,first_name,last_name,avatar_url',
                'participantRows' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->orderByRaw("CASE WHEN type = 'asap' THEN 0 ELSE 1 END")
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{body?: string, attachment?: UploadedFile|null, gif_url?: string|null, gif_name?: string|null}  $input
     */
    public function send(User $user, StaffConversation $conversation, array $input): StaffMessage
    {
        $this->assertParticipant($user, $conversation);

        $body = trim((string) ($input['body'] ?? ''));
        $file = $input['attachment'] ?? null;
        $gifUrl = trim((string) ($input['gif_url'] ?? ''));
        abort_if($body === '' && ! ($file instanceof UploadedFile) && $gifUrl === '', 422, 'Write a message or attach a file.');

        $attachment = null;
        if ($file instanceof UploadedFile) {
            $attachment = $this->attachments->store($file, $conversation->id);
        } elseif ($gifUrl !== '') {
            $attachment = [
                'disk' => 'remote',
                'path' => $gifUrl,
                'url' => $gifUrl,
                'name' => (string) ($input['gif_name'] ?? 'GIF'),
                'mime' => 'image/gif',
                'size' => null,
            ];
        }

        $message = StaffMessage::query()->create([
            'staff_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $body !== '' ? $body : null,
            'attachment_disk' => $attachment['disk'] ?? null,
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_url' => $attachment['url'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'attachment_mime' => $attachment['mime'] ?? null,
            'attachment_size' => $attachment['size'] ?? null,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        StaffConversationParticipant::query()
            ->where('staff_conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $message = $message->load('user:id,uid,name,first_name,last_name,avatar_url');
        $this->notifier->notifyNewMessage($conversation->fresh(['participants']) ?? $conversation, $message, $user);

        return $message;
    }

    public function markRead(User $user, StaffConversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        StaffConversationParticipant::query()
            ->where('staff_conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $this->notifier->markConversationNotificationsRead($user, $conversation);
    }

    public function markTyping(User $user, StaffConversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);
        Cache::put($this->typingKey($conversation->id, $user->id), now()->timestamp, 5);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function typingUsers(StaffConversation $conversation, User $viewer): array
    {
        return $conversation->participants
            ->filter(fn (User $user) => ! $user->is($viewer) && Cache::has($this->typingKey($conversation->id, $user->id)))
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->first_name ?: Str::before($user->name, ' '),
            ])
            ->values()
            ->all();
    }

    public function unreadCount(User $user): int
    {
        if (! $user->isStaff()) {
            return 0;
        }

        $this->ensureAsap();

        return StaffConversationParticipant::query()
            ->where('user_id', $user->id)
            ->with('conversation.latestMessage')
            ->get()
            ->filter(function (StaffConversationParticipant $row) {
                $latest = $row->conversation?->latestMessage;
                if (! $latest) {
                    return false;
                }
                if ((int) $latest->user_id === (int) $row->user_id) {
                    return false;
                }

                return ! $row->last_read_at || $latest->created_at?->gt($row->last_read_at);
            })
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function directory(User $viewer): Collection
    {
        return User::query()
            ->staff()
            ->where('id', '!=', $viewer->id)
            ->orderBy('name')
            ->get(['id', 'uid', 'name', 'first_name', 'last_name', 'avatar_url', 'role', 'email']);
    }

    public function assertParticipant(User $user, StaffConversation $conversation): void
    {
        if ($conversation->isAsap()) {
            $this->syncAsapParticipants($conversation);
        }

        $isMember = StaffConversationParticipant::query()
            ->where('staff_conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($user->isStaff() && $isMember, 403);
    }

    private function typingKey(int $conversationId, int $userId): string
    {
        return 'staff-chat.typing.'.$conversationId.'.'.$userId;
    }

    private function uid(): string
    {
        do {
            $uid = 'SC'.strtoupper(Str::random(10));
        } while (StaffConversation::query()->where('uid', $uid)->exists());

        return $uid;
    }
}
