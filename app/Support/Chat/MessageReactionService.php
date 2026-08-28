<?php

namespace App\Support\Chat;

use App\Models\ChatMessageReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MessageReactionService
{
    /** @var list<string> */
    public const QUICK = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    /**
     * @return list<array{emoji: string, count: int, mine: bool}>
     */
    public function present(Model $message, ?User $viewer = null): array
    {
        if (! Schema::hasTable('chat_message_reactions')) {
            return [];
        }

        $reactions = $message->relationLoaded('reactions')
            ? $message->reactions
            : $message->reactions()->get();

        return $reactions
            ->groupBy('emoji')
            ->map(function (Collection $group, string $emoji) use ($viewer) {
                return [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'mine' => $viewer
                        ? $group->contains(fn (ChatMessageReaction $reaction) => (int) $reaction->user_id === (int) $viewer->id)
                        : false,
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @return list<array{emoji: string, count: int, mine: bool}>
     */
    public function toggle(User $user, Model $message, string $emoji): array
    {
        $emoji = trim($emoji);
        abort_unless($emoji !== '' && mb_strlen($emoji) <= 32, 422, 'Pick a valid emoji.');

        $existing = ChatMessageReaction::query()
            ->where('message_type', $message->getMorphClass())
            ->where('message_id', $message->getKey())
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ChatMessageReaction::query()->create([
                'message_type' => $message->getMorphClass(),
                'message_id' => $message->getKey(),
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
        }

        $message->unsetRelation('reactions');
        $message->load('reactions');

        return $this->present($message, $user);
    }
}
