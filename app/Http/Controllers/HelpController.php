<?php

namespace App\Http\Controllers;

use App\Http\Requests\Help\SendChatMessageRequest;
use App\Http\Requests\Help\SubmitSupportCsatRequest;
use App\Support\ActivityLogger;
use App\Support\Realtime\Realtime;
use App\Support\SupportChat\SupportConversationService;
use App\Support\SupportChat\SupportPresence;
use App\Support\SupportChat\SupportPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function __construct(
        private readonly SupportConversationService $conversations,
        private readonly SupportPresenter $presenter,
        private readonly SupportPresence $presence,
    ) {}

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

        $this->presence->heartbeat($user);
        $ticket = $this->conversations->latestForCustomer($user);

        if ($ticket) {
            if ($ticket->isOpen()) {
                $this->conversations->refreshRouting($ticket);
            }
            $this->conversations->markCustomerRead($ticket);
        }

        return Inertia::render('Help/Chat', [
            'conversation' => $this->presenter->customer($ticket?->fresh(), $user),
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->presence->heartbeat($user);

        $ticket = $this->conversations->latestForCustomer($user);

        if ($ticket) {
            if ($ticket->isOpen()) {
                $this->conversations->refreshRouting($ticket);
            }
            $this->conversations->markCustomerRead($ticket);
        }

        return response()->json($this->presenter->customer($ticket?->fresh(), $user));
    }

    public function send(SendChatMessageRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->presence->heartbeat($user);

        $ticket = $this->conversations->customerMessage($user, [
            'body' => $request->validated('body'),
            'topic_key' => $request->validated('topic_key'),
            'attachment' => $request->file('attachment'),
        ]);

        $this->conversations->markCustomerRead($ticket);

        $payload = $this->presenter->customer($ticket->fresh(), $user);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return back();
    }

    public function typing(Request $request): JsonResponse
    {
        $ticket = $this->conversations->latestForCustomer($request->user());

        if ($ticket) {
            $this->presence->markTyping($ticket->id, 'customer');
            app(Realtime::class)->typing($ticket, 'customer');
        }

        return response()->json(['ok' => true]);
    }

    public function react(
        \App\Http\Requests\Chat\ToggleMessageReactionRequest $request,
        \App\Models\SupportTicketMessage $message,
        \App\Support\Chat\MessageReactionService $reactions,
    ): JsonResponse {
        $user = $request->user();
        $ticket = $this->conversations->latestForCustomer($user);

        abort_unless($ticket && (int) $message->support_ticket_id === (int) $ticket->id, 404);
        abort_unless($message->kind === \App\Models\SupportTicketMessage::KIND_MESSAGE, 422);

        return response()->json([
            'reactions' => $reactions->toggle($user, $message, (string) $request->validated('emoji')),
        ]);
    }

    public function csat(SubmitSupportCsatRequest $request): JsonResponse|RedirectResponse
    {
        $ticket = $this->conversations->latestForCustomer($request->user());

        abort_unless($ticket, 404);

        $this->conversations->submitCsat(
            $ticket,
            $request->validated('score'),
            $request->validated('comment'),
            (bool) $request->boolean('dismiss'),
        );

        $payload = $this->presenter->customer($ticket->fresh(), $request->user());

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return back();
    }
}
