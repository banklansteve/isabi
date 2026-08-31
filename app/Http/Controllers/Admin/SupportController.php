<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignSupportTicketRequest;
use App\Http\Requests\Admin\ReplySupportTicketRequest;
use App\Http\Requests\Admin\ResolveSupportTicketRequest;
use App\Http\Requests\Admin\StoreCannedReplyRequest;
use App\Http\Requests\Admin\StoreSupportNoteRequest;
use App\Http\Requests\Admin\TagSupportTicketRequest;
use App\Http\Requests\Admin\UpdateCannedReplyRequest;
use App\Models\SupportCannedReply;
use App\Models\SupportTicket;
use App\Models\StaffCaseReferral;
use App\Models\User;
use App\Support\Admin\StaffCaseReferralService;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\OpsAttentionFeed;
use App\Support\Realtime\Realtime;
use App\Support\Staff\StaffPresence;
use App\Support\SupportChat\SupportChatTemplates;
use App\Support\SupportChat\SupportConversationService;
use App\Support\SupportChat\SupportPresence;
use App\Support\SupportChat\SupportPresenter;
use App\Support\SupportChat\SupportReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportConversationService $conversations,
        private readonly SupportPresenter $presenter,
        private readonly SupportPresence $presence,
        private readonly SupportReportService $reports,
        private readonly StaffPresence $staffPresence,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);

        $this->presence->heartbeat($request->user());
        $this->staffPresence->touch($request->user());

        $selected = $this->selectedTicket($request);

        if ($selected && ! $this->ticketVisibleTo($request->user(), $selected)) {
            $selected = null;
        }

        if ($selected) {
            $this->conversations->refreshRouting($selected);
            $this->conversations->markStaffRead($selected);

            if ($request->user()?->isOperationsAdmin()) {
                app(OpsAttentionFeed::class)->markOpened($request->user(), 'support:'.$selected->id);
            }
        }

        return Inertia::render('Admin/Support/Index', $this->workspace($request, $selected));
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);
        $this->assertTicketVisible($request->user(), $ticket);

        $this->presence->heartbeat($request->user());
        $this->conversations->refreshRouting($ticket);
        $this->conversations->markStaffRead($ticket);

        if ($request->user()?->isOperationsAdmin()) {
            app(OpsAttentionFeed::class)->markOpened($request->user(), 'support:'.$ticket->id);
        }

        return Inertia::render('Admin/Support/Index', $this->workspace($request, $ticket));
    }

    public function sync(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);
        $this->presence->heartbeat($request->user());

        $selected = $this->selectedTicket($request);

        if ($selected && ! $this->ticketVisibleTo($request->user(), $selected)) {
            $selected = null;
        }

        if ($selected) {
            $this->conversations->refreshRouting($selected);
            $this->conversations->markStaffRead($selected);
        }

        return response()->json($this->workspace($request, $selected, json: true));
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        $this->assertTicketVisible($request->user(), $ticket);
        $this->presence->heartbeat($request->user());
        $agent = $request->user();
        $previous = $ticket->assignedTo;
        $this->conversations->staffReply($agent, $ticket, [
            'body' => $request->validated('body'),
            'attachment' => $request->file('attachment'),
        ]);

        $this->recordChatStart($agent, $ticket, $previous);

        return $this->respond($request, $ticket->fresh());
    }

    public function note(StoreSupportNoteRequest $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        $this->assertTicketVisible($request->user(), $ticket);
        $this->conversations->addNote($request->user(), $ticket, (string) $request->validated('body'));

        return $this->respond($request, $ticket->fresh());
    }

    public function claim(Request $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);

        $user = $request->user();
        abort_unless(
            $user->isSuperAdmin()
                || $ticket->assigned_to_user_id === null
                || (int) $ticket->assigned_to_user_id === (int) $user->id,
            403,
        );

        $agent = $user;
        $previous = $ticket->assignedTo;
        $this->conversations->assign($ticket, $agent);

        app(Realtime::class)->conversation($ticket->fresh() ?? $ticket);

        $this->recordChatStart($agent, $ticket, $previous);

        return $this->respond($request, $ticket->fresh());
    }

    public function assign(AssignSupportTicketRequest $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        $this->assertTicketVisible($request->user(), $ticket);
        $agent = $request->agent();
        abort_if($agent && ! $agent->canDo('admin.support.manage') && ! $agent->isSuperAdmin(), 422);

        $actor = $request->user();
        if ($actor && ! $actor->isSuperAdmin()) {
            $assignee = $ticket->assigned_to_user_id;
            abort_unless(
                $assignee === null || (int) $assignee === (int) $actor->id,
                403,
                'This chat belongs to another agent.',
            );
            abort_unless(
                $agent === null || (int) $agent->id === (int) $actor->id,
                403,
                'You can only assign chats to yourself.',
            );
        }

        $this->conversations->assign($ticket, $agent);

        app(Realtime::class)->conversation($ticket->fresh() ?? $ticket);

        AdminAudit::record(
            'support.assigned',
            $agent
                ? "{$request->user()->name} handed ticket #{$ticket->id} to {$agent->name}."
                : "{$request->user()->name} unassigned ticket #{$ticket->id}.",
            $ticket,
        );

        return $this->respond($request, $ticket->fresh());
    }

    public function tag(TagSupportTicketRequest $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        $this->assertTicketVisible($request->user(), $ticket);
        $this->conversations->tag($ticket, $request->validated('tags'));

        return $this->respond($request, $ticket->fresh());
    }

    public function resolve(ResolveSupportTicketRequest $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        $this->assertTicketVisible($request->user(), $ticket);
        $old = ['status' => $ticket->status];
        $this->conversations->resolve($ticket);

        AdminAudit::record(
            'support.resolved',
            "{$request->user()->name} closed support chat #{$ticket->id}.",
            $ticket,
            $old,
            ['status' => 'resolved'],
        );

        return $this->respond($request, $ticket->fresh());
    }

    public function reopen(ResolveSupportTicketRequest $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        $this->assertTicketVisible($request->user(), $ticket);
        $this->conversations->reopen($ticket);

        AdminAudit::record(
            'support.reopened',
            "{$request->user()->name} reopened ticket #{$ticket->id}.",
            $ticket,
        );

        return $this->respond($request, $ticket->fresh());
    }

    public function typing(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);
        $this->assertTicketVisible($request->user(), $ticket);
        $this->presence->heartbeat($request->user());
        $this->presence->markTyping($ticket->id, 'staff');
        app(Realtime::class)->typing($ticket, 'staff');

        return response()->json(['ok' => true]);
    }

    public function react(
        \App\Http\Requests\Chat\ToggleMessageReactionRequest $request,
        SupportTicket $ticket,
        \App\Models\SupportTicketMessage $message,
        \App\Support\Chat\MessageReactionService $reactions,
    ): JsonResponse {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);
        $this->assertTicketVisible($request->user(), $ticket);
        abort_unless((int) $message->support_ticket_id === (int) $ticket->id, 404);
        abort_unless($message->kind === \App\Models\SupportTicketMessage::KIND_MESSAGE, 422);

        return response()->json([
            'reactions' => $reactions->toggle($request->user(), $message, (string) $request->validated('emoji')),
        ]);
    }

    public function reports(Request $request): Response
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);

        $from = now()->subDays(30)->startOfDay();
        $to = now()->endOfDay();

        return Inertia::render('Admin/Support/Reports', [
            'report' => $this->reports->summary($from, $to),
        ]);
    }

    public function templates(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        SupportChatTemplates::ensure();

        return Inertia::render('Admin/Support/Templates', [
            'templates' => $this->cannedPayload($request->user(), teamOnly: true),
            'moments' => SupportChatTemplates::momentOptions(),
            'topics' => $this->presenter->topicOptions(),
        ]);
    }

    public function storeCanned(StoreCannedReplyRequest $request): RedirectResponse
    {
        $scope = $request->validated('scope') ?: SupportChatTemplates::SCOPE_PERSONAL;

        SupportCannedReply::query()->create([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'topic_key' => $request->validated('topic_key'),
            'moment' => $request->validated('moment') ?: SupportChatTemplates::MOMENT_GENERAL,
            'scope' => $scope,
            'is_system' => false,
            'user_id' => $scope === SupportChatTemplates::SCOPE_TEAM ? null : $request->user()->id,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => $scope === SupportChatTemplates::SCOPE_TEAM ? 'Team template saved' : 'Saved reply',
            'message' => $scope === SupportChatTemplates::SCOPE_TEAM
                ? 'Operations staff can insert this from the composer.'
                : 'You can insert this from the composer with / or the replies picker.',
        ]);
    }

    public function updateCanned(UpdateCannedReplyRequest $request, SupportCannedReply $reply): RedirectResponse
    {
        $scope = $reply->is_system
            ? SupportChatTemplates::SCOPE_TEAM
            : $request->validated('scope');

        $reply->forceFill([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'topic_key' => $request->validated('topic_key'),
            'moment' => $request->validated('moment'),
            'scope' => $scope,
            'user_id' => $scope === SupportChatTemplates::SCOPE_TEAM
                ? null
                : ($reply->user_id ?: $request->user()->id),
        ])->save();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template updated',
            'message' => 'The new copy is ready to insert in chats.',
        ]);
    }

    public function destroyCanned(Request $request, SupportCannedReply $reply): RedirectResponse
    {
        abort_unless($request->user()?->canDo('admin.support.manage'), 403);
        abort_if($reply->is_system, 403);
        abort_unless($reply->user_id === $request->user()->id || $request->user()->isSuperAdmin(), 403);

        $reply->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Reply removed',
            'message' => 'That saved reply is gone.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function workspace(Request $request, ?SupportTicket $selected, bool $json = false): array
    {
        SupportChatTemplates::ensure();

        $filters = [
            'status' => (string) $request->query('status', 'open'),
            'assigned' => (string) $request->query(
                'assigned',
                $request->user()?->isSuperAdmin() ? 'all' : 'me',
            ),
            'sort' => (string) $request->query('sort', 'waiting'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $tickets = $this->filteredTickets($request, $filters)
            ->map(fn (SupportTicket $ticket) => $this->presenter->inboxItem($ticket))
            ->values();

        $openQuery = SupportTicket::query()->whereIn('status', [
            SupportTicket::STATUS_NEW,
            SupportTicket::STATUS_OPEN,
            SupportTicket::STATUS_PENDING,
        ]);

        $visibleOpen = $this->scopeVisibleTickets(clone $openQuery, $request->user());
        $resolvedQuery = SupportTicket::query()->where('status', SupportTicket::STATUS_RESOLVED);
        $visibleResolved = $this->scopeVisibleTickets(clone $resolvedQuery, $request->user());

        return [
            'tickets' => $tickets,
            'ticket' => $selected ? $this->presenter->staffThread($selected) : null,
            'filters' => $filters,
            'agents' => $request->user()?->isSuperAdmin()
                ? $this->conversations->assignableAgents()
                : [],
            'staff' => \App\Support\Admin\JobAdminPresenter::staffOptions(),
            'can_refer' => (bool) $request->user()?->canDo('admin.support.manage'),
            'can_escalate' => app(StaffCaseReferralService::class)
                ->canEscalate($request->user(), StaffCaseReferral::SUBJECT_SUPPORT),
            'topics' => $this->presenter->topicOptions(),
            'canned' => $this->cannedPayload($request->user()),
            'moments' => SupportChatTemplates::momentOptions(),
            'counts' => [
                'active' => $visibleOpen->count(),
                'resolved' => $visibleResolved->count(),
                'unassigned' => (clone $openQuery)->whereNull('assigned_to_user_id')->count(),
                'mine' => (clone $openQuery)->where('assigned_to_user_id', $request->user()->id)->count(),
                'open' => $visibleOpen->count(),
            ],
            'is_super' => (bool) $request->user()?->isSuperAdmin(),
            'poll_ms' => (int) config('support.poll_interval_ms', 8000),
            'staff_available' => $this->presence->anyStaffOnline(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cannedPayload(?\App\Models\User $user, bool $teamOnly = false): array
    {
        return SupportCannedReply::query()
            ->when(
                $teamOnly,
                fn ($query) => $query->where('scope', SupportChatTemplates::SCOPE_TEAM),
                fn ($query) => $query->where(function ($inner) use ($user) {
                    $inner->where('scope', SupportChatTemplates::SCOPE_TEAM)
                        ->orWhere('user_id', $user?->id);
                }),
            )
            ->orderByRaw("CASE moment WHEN 'open' THEN 1 WHEN 'close' THEN 2 WHEN 'review' THEN 3 ELSE 4 END")
            ->orderBy('title')
            ->get()
            ->map(fn (SupportCannedReply $reply) => [
                'id' => $reply->id,
                'title' => $reply->title,
                'body' => $reply->body,
                'topic_key' => $reply->topic_key,
                'moment' => $reply->moment ?: SupportChatTemplates::MOMENT_GENERAL,
                'scope' => $reply->scope ?: SupportChatTemplates::SCOPE_PERSONAL,
                'is_system' => (bool) $reply->is_system,
                'mine' => $reply->user_id === $user?->id,
            ])
            ->all();
    }

    /**
     * @param  array{status: string, assigned: string, sort: string, q: string}  $filters
     * @return \Illuminate\Support\Collection<int, SupportTicket>
     */
    private function filteredTickets(Request $request, array $filters)
    {
        $query = SupportTicket::query()
            ->with(['user:id,uid,name,email,business_name,avatar_url,first_name,last_name', 'assignedTo:id,uid,name'])
            ->with(['messages' => fn ($messages) => $messages->where('kind', 'message')->latest('id')->limit(1)]);

        if ($filters['status'] === 'resolved') {
            $query->where('status', SupportTicket::STATUS_RESOLVED);
        } elseif ($filters['status'] === 'pending') {
            $query->where('status', SupportTicket::STATUS_PENDING);
        } elseif ($filters['status'] === 'new') {
            $query->where('status', SupportTicket::STATUS_NEW);
        } elseif ($filters['status'] !== 'all') {
            $query->whereIn('status', [
                SupportTicket::STATUS_NEW,
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_PENDING,
            ]);
        }

        if ($filters['assigned'] === 'me') {
            $query->where('assigned_to_user_id', $request->user()->id);
        } elseif ($filters['assigned'] === 'unassigned') {
            // Ops may browse the unclaimed desk, but never another agent's chats.
            $query->whereNull('assigned_to_user_id');
        } elseif ($request->user()?->isSuperAdmin() && is_numeric($filters['assigned'])) {
            $query->where('assigned_to_user_id', (int) $filters['assigned']);
        } elseif ($request->user()?->isSuperAdmin()) {
            // Super Admin "all" — every conversation.
        } else {
            // Ops "all" means their own chats only.
            $query->where('assigned_to_user_id', $request->user()->id);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($inner) use ($q) {
                $inner->where('subject', 'like', '%'.$q.'%')
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('business_name', 'like', '%'.$q.'%'));
            });
        }

        if ($filters['sort'] === 'recent') {
            $query->latest('last_reply_at');
        } else {
            $query->orderByRaw("CASE WHEN status IN ('new', 'open') THEN 0 ELSE 1 END")
                ->orderByRaw('COALESCE(last_customer_message_at, created_at) asc');
        }

        return $query->limit(200)->get();
    }

    private function selectedTicket(Request $request): ?SupportTicket
    {
        $routeTicket = $request->route('ticket');

        if ($routeTicket instanceof SupportTicket) {
            return $routeTicket;
        }

        $uid = trim((string) $request->query('ticket', ''));

        if ($uid === '') {
            return null;
        }

        return SupportTicket::query()->where('uid', $uid)->first();
    }

    private function respond(Request $request, SupportTicket $ticket): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($this->presenter->staffThread($ticket));
        }

        return back();
    }

    private function recordChatStart(User $agent, SupportTicket $ticket, ?User $previous): void
    {
        if ($previous?->is($agent)) {
            return;
        }

        AdminAudit::record(
            $previous ? 'support.took_over' : 'support.claimed',
            $previous
                ? "{$agent->name} took over chat #{$ticket->id} from {$previous->name}."
                : "{$agent->name} started chat #{$ticket->id}.",
            $ticket,
        );
    }

    private function ticketVisibleTo(?User $user, SupportTicket $ticket): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $assignee = $ticket->assigned_to_user_id;

        // Own chats, or unclaimed chats they can pick up — never another agent's.
        return $assignee === null || (int) $assignee === (int) $user->id;
    }

    private function assertTicketVisible(?User $user, SupportTicket $ticket): void
    {
        abort_unless($this->ticketVisibleTo($user, $ticket), 403);
    }

    private function scopeVisibleTickets($query, ?User $user)
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        // Default list visibility: only conversations assigned to this agent.
        return $query->where('assigned_to_user_id', $user->id);
    }
}
