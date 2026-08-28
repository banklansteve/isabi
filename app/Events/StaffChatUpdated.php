<?php

namespace App\Events;

use App\Models\StaffConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffChatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StaffConversation $conversation,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('staff.chat'),
            new PrivateChannel('staff.chat.'.$this->conversation->uid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'staff.chat';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_uid' => $this->conversation->uid,
            'type' => $this->conversation->type,
        ];
    }
}
