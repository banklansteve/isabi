<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplySupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Support\Admin\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function index(): Response
    {
        $tickets = SupportTicket::query()
            ->with(['user:id,name,email,business_name,avatar_url', 'assignedTo:id,name'])
            ->withCount('messages')
            ->latest('last_reply_at')
            ->limit(500)
            ->get()
            ->map(fn (SupportTicket $ticket) => [
                ...$this->payload($ticket),
                'created_iso' => ($ticket->last_reply_at ?? $ticket->created_at)?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $ticket->load(['user:id,name,email,business_name,avatar_url', 'messages.user:id,name']);

        return Inertia::render('Admin/Support/Show', [
            'ticket' => $this->payload($ticket, true),
        ]);
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validated();

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'is_staff' => true,
            'body' => $data['body'],
        ]);

        $ticket->forceFill([
            'status' => SupportTicket::STATUS_PENDING,
            'assigned_to_user_id' => $ticket->assigned_to_user_id ?? $request->user()->id,
            'last_reply_at' => now(),
        ])->save();

        AdminAudit::record(
            'support.replied',
            "{$request->user()->name} replied to support ticket #{$ticket->id}.",
            $ticket,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Reply sent',
            'message' => 'The artisan will see this in Help → Chat.',
        ]);
    }

    public function resolve(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $old = ['status' => $ticket->status];

        $ticket->forceFill([
            'status' => SupportTicket::STATUS_RESOLVED,
            'resolved_at' => now(),
        ])->save();

        AdminAudit::record('support.resolved', "{$request->user()->name} resolved ticket #{$ticket->id}.", $ticket, $old, ['status' => 'resolved']);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Ticket resolved',
            'message' => 'This conversation is marked resolved.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(SupportTicket $ticket, bool $detailed = false): array
    {
        $payload = [
            'id' => $ticket->id,
            'subject' => $ticket->subject ?: 'Support chat',
            'status' => $ticket->status,
            'messages_count' => $ticket->messages_count ?? $ticket->messages()->count(),
            'last_reply_at' => $ticket->last_reply_at?->timezone(config('app.display_timezone'))->diffForHumans(),
            'user' => $ticket->user ? [
                'id' => $ticket->user->id,
                'name' => $ticket->user->displayBusinessName(),
                'email' => $ticket->user->email,
                'avatar_url' => $ticket->user->avatar_url ?? null,
            ] : null,
        ];

        if ($detailed) {
            $payload['messages'] = $ticket->messages->map(fn (SupportTicketMessage $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'is_staff' => $message->is_staff,
                'author' => $message->user?->name,
                'when' => $message->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            ])->all();
        }

        return $payload;
    }
}
