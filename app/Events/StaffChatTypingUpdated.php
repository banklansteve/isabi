<?php

namespace App\Events;

use App\Models\StaffConversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class StaffChatTypingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StaffConversation $conversation,
        public User $user,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('staff.chat.'.$this->conversation->uid)];
    }

    public function broadcastAs(): string
    {
        return 'staff.typing';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_uid' => $this->conversation->uid,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->first_name ?: Str::before($this->user->name, ' '),
            ],
        ];
    }
}
