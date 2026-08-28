<?php

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTypingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public string $side,
    ) {}

    public function broadcastOn(): array
    {
        if ($this->side === 'staff') {
            $uid = $this->ticket->user?->uid;

            return filled($uid) ? [new PrivateChannel('user.'.$uid)] : [];
        }

        return [new PrivateChannel('support.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'support.typing';
    }

    public function broadcastWhen(): bool
    {
        return $this->broadcastOn() !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'side' => $this->side,
            'typing' => true,
        ];
    }
}
