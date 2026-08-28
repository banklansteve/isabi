<?php

namespace App\Support\Realtime;

use App\Events\SupportCustomerUpdated;
use App\Events\SupportStaffInboxUpdated;
use App\Events\SupportTypingUpdated;
use App\Events\UserNotificationUpdated;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Throwable;

class Realtime
{
    public function conversation(SupportTicket $ticket, bool $notifyCustomer = true, bool $includeThread = true): void
    {
        $ticket = $ticket->fresh(['user', 'assignedTo']) ?? $ticket;

        if ($notifyCustomer && $ticket->user) {
            $this->send(new SupportCustomerUpdated($ticket, $ticket->user));
        }

        $this->send(new SupportStaffInboxUpdated($ticket, $includeThread));
    }

    public function typing(SupportTicket $ticket, string $side): void
    {
        $ticket->loadMissing('user');
        $this->send(new SupportTypingUpdated($ticket, $side));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function notification(User $user, array $item, int $unreadCount): void
    {
        $this->send(new UserNotificationUpdated($user, $item, $unreadCount));
    }

    private function send(ShouldBroadcast $event): void
    {
        try {
            broadcast($event);
        } catch (Throwable $e) {
            logger()->warning('realtime.failed', [
                'event' => $event::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
