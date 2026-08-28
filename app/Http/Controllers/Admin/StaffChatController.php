<?php

namespace App\Http\Controllers\Admin;

use App\Events\StaffChatTypingUpdated;
use App\Events\StaffChatUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendStaffChatMessageRequest;
use App\Http\Requests\Admin\StartStaffDirectChatRequest;
use App\Models\StaffConversation;
use App\Models\User;
use App\Support\StaffChat\StaffChatPresenter;
use App\Support\StaffChat\StaffChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffChatController extends Controller
{
    public function __construct(
        private readonly StaffChatService $chat,
        private readonly StaffChatPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $this->assertStaff($request->user());

        return Inertia::render('Admin/Asap/Index', $this->workspace($request));
    }

    public function show(Request $request, StaffConversation $conversation): Response
    {
        $user = $request->user();
        $this->assertStaff($user);
        $this->chat->assertParticipant($user, $conversation);
        $this->chat->markRead($user, $conversation);

        return Inertia::render('Admin/Asap/Index', $this->workspace($request, $conversation));
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertStaff($user);

        $selected = $this->selectedFromQuery($request);
        if ($selected) {
            $this->chat->assertParticipant($user, $selected);
            $this->chat->markRead($user, $selected);
        }

        return response()->json($this->workspace($request, $selected));
    }

    public function store(SendStaffChatMessageRequest $request, StaffConversation $conversation): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->chat->assertParticipant($user, $conversation);

        $this->chat->send($user, $conversation, [
            'body' => $request->validated('body'),
            'attachment' => $request->file('attachment'),
            'gif_url' => $request->validated('gif_url'),
            'gif_name' => $request->validated('gif_name'),
        ]);

        $fresh = $conversation->fresh() ?? $conversation;
        broadcast(new StaffChatUpdated($fresh))->toOthers();

        return $this->respond($request, $fresh);
    }

    public function markRead(Request $request, StaffConversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->assertStaff($user);
        $this->chat->markRead($user, $conversation);

        return response()->json(['ok' => true, 'unread_count' => $this->chat->unreadCount($user)]);
    }

    public function startDirect(StartStaffDirectChatRequest $request): RedirectResponse
    {
        $peer = User::query()->findOrFail((int) $request->validated('user_id'));
        $conversation = $this->chat->directBetween($request->user(), $peer);

        return redirect()->route('admin.asap.show', $conversation);
    }

    public function typing(Request $request, StaffConversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->assertStaff($user);
        $this->chat->markTyping($user, $conversation);

        broadcast(new StaffChatTypingUpdated($conversation, $user))->toOthers();

        return response()->json(['ok' => true]);
    }

    public function react(
        \App\Http\Requests\Chat\ToggleMessageReactionRequest $request,
        StaffConversation $conversation,
        \App\Models\StaffMessage $message,
        \App\Support\Chat\MessageReactionService $reactions,
    ): JsonResponse {
        $user = $request->user();
        $this->assertStaff($user);
        $this->chat->assertParticipant($user, $conversation);
        abort_unless((int) $message->staff_conversation_id === (int) $conversation->id, 404);

        return response()->json([
            'reactions' => $reactions->toggle($user, $message, (string) $request->validated('emoji')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function workspace(Request $request, ?StaffConversation $selected = null): array
    {
        $user = $request->user();
        $conversations = $this->chat->inboxFor($user)
            ->map(fn (StaffConversation $conversation) => $this->presenter->inboxItem($conversation, $user))
            ->values()
            ->all();

        return [
            'conversations' => $conversations,
            'conversation' => $selected ? $this->presenter->thread($selected, $user) : null,
            'directory' => $this->chat->directory($user)->map(fn (User $peer) => [
                'id' => $peer->id,
                'uid' => $peer->uid,
                'name' => $peer->name,
                'email' => $peer->email,
                'avatar_url' => $peer->avatar_url,
                'role_label' => $peer->isSuperAdmin() ? 'Super Admin' : 'Operations',
            ])->values()->all(),
            'unread_count' => $this->chat->unreadCount($user),
            'poll_ms' => 8000,
            'max_attachment_kb' => (int) config('support.max_attachment_kb', 8192),
        ];
    }

    private function selectedFromQuery(Request $request): ?StaffConversation
    {
        $uid = (string) $request->query('conversation', '');

        if ($uid === '') {
            return null;
        }

        return StaffConversation::query()->where('uid', $uid)->first();
    }

    private function respond(Request $request, StaffConversation $conversation): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($this->presenter->thread($conversation->fresh() ?? $conversation, $request->user()));
        }

        return redirect()->route('admin.asap.show', $conversation);
    }

    private function assertStaff(?User $user): void
    {
        abort_unless($user?->isStaff(), 403);
    }
}
