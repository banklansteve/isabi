<?php

namespace App\Events;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\SupportChat\SupportPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportCustomerUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public User $customer,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->customer->uid)];
    }

    public function broadcastAs(): string
    {
        return 'support.updated';
    }

    public function broadcastWhen(): bool
    {
        return filled($this->customer->uid);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => app(SupportPresenter::class)->customer($this->ticket, $this->customer),
        ];
    }
}
