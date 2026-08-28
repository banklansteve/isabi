<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $item
     */
    public function __construct(
        public User $user,
        public array $item,
        public int $unreadCount,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->user->uid)];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    public function broadcastWhen(): bool
    {
        return filled($this->user->uid);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'item' => $this->item,
            'unread_count' => $this->unreadCount,
        ];
    }
}
