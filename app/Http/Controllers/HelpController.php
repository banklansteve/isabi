<?php

namespace App\Http\Controllers;

use App\Http\Requests\Help\SendChatMessageRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.help',
            summary: "{$user->name} opened Help & support.",
            user: $user,
        );

        return Inertia::render('Help/Index');
    }

    public function chat(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.help_chat',
            summary: "{$user->name} opened support chat.",
            user: $user,
        );

        $ticket = SupportTicket::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])
            ->latest('id')
            ->with('messages')
            ->first();

        $messages = $ticket?->messages->map(fn (SupportTicketMessage $message) => [
            'id' => $message->id,
            'role' => $message->is_staff ? 'support' : 'user',
            'body' => $message->body,
            'time' => $message->created_at?->timezone(config('app.display_timezone'))->format('g:ia'),
        ])->all() ?? [];

        return Inertia::render('Help/Chat', [
            'messages' => $messages,
        ]);
    }

    public function send(SendChatMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $request->user();

        $ticket = SupportTicket::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])
            ->latest('id')
            ->first();

        if (! $ticket) {
            $ticket = SupportTicket::query()->create([
                'user_id' => $user->id,
                'subject' => str($data['body'])->limit(80)->toString(),
                'status' => SupportTicket::STATUS_OPEN,
                'last_reply_at' => now(),
            ]);
        }

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'is_staff' => false,
            'body' => $data['body'],
        ]);

        $ticket->forceFill([
            'status' => SupportTicket::STATUS_OPEN,
            'last_reply_at' => now(),
            'resolved_at' => null,
        ])->save();

        return back();
    }
}
