<?php

namespace App\Events;

use App\Models\SupportTicket;
use App\Support\SupportChat\SupportPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportStaffInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public bool $includeThread = true,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('support.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'support.inbox';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $presenter = app(SupportPresenter::class);

        return [
            'item' => $presenter->inboxItem($this->ticket),
            'thread' => $this->includeThread ? $presenter->staffThread($this->ticket) : null,
        ];
    }
}
