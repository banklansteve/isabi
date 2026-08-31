<?php

namespace App\Support\Admin;

use App\Models\AdminApproval;
use App\Models\Announcement;
use App\Models\BillingIssue;
use App\Models\LeaveRequest;
use App\Models\PatrolCase;
use App\Models\PatrolCaseRule;
use App\Models\Referral;
use App\Models\Review;
use App\Models\StaffAttentionRead;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\StaffCaseReferralService;
use App\Support\Patrol\PatrolSeverity;
use App\Support\StaffChat\StaffChatPresenter;
use App\Support\StaffChat\StaffChatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OpsAttentionFeed
{
    public const URGENCY_HIGH_PATROL = 100;

    public const URGENCY_SUPPORT = 60;

    public const URGENCY_PATROL_QUEUE = 40;

    /**
     * @return array{
     *     greeting: string,
     *     roles: list<array<string, mixed>>,
     *     role_summary: string,
     *     items: list<array<string, mixed>>,
     *     shortcuts: list<array<string, mixed>>,
     *     open_count: int,
     *     unread_count: int,
     *     unread_items: list<array<string, mixed>>,
     *     restricted: bool
     * }
     */
    public function home(User $user): array
    {
        $payload = $this->payload($user);

        return [
            'greeting' => $this->greeting(),
            'given_name' => $this->givenName($user),
            'timezone' => (string) config('app.display_timezone', config('app.timezone')),
            'roles' => $payload['roles'],
            'role_summary' => $payload['role_summary'],
            'items' => $payload['items'],
            'priority_groups' => $payload['priority_groups'],
            'shortcuts' => $payload['shortcuts'],
            'duty' => $payload['duty'],
            'escalations' => $payload['escalations'],
            'escalate' => $payload['escalate'],
            'open_count' => $payload['open_count'],
            'unread_count' => $payload['unread_count'],
            'unread_items' => $payload['unread_items'],
            'restricted' => $user->isRestrictedStaff(),
        ];
    }

    /**
     * Lightweight inbox for the shared notification bell.
     *
     * @return array{
     *     greeting: string,
     *     open_count: int,
     *     unread_count: int,
     *     items: list<array<string, mixed>>,
     *     tasks: list<array<string, mixed>>,
     *     attention_items: list<array<string, mixed>>,
     *     priority_groups: list<array<string, mixed>>,
     *     shortcuts: list<array<string, mixed>>,
     *     roles: list<array<string, mixed>>,
     *     role_summary: string
     * }
     */
    public function inbox(User $user): array
    {
        $payload = $this->payload($user);
        $tasks = $this->withoutMessages($payload['items']);

        return [
            'greeting' => $payload['greeting'],
            'open_count' => $payload['open_count'],
            'unread_count' => $payload['unread_count'],
            'items' => $payload['unread_items'],
            'tasks' => array_slice($tasks, 0, 6),
            'attention_items' => $payload['items'],
            'priority_groups' => $payload['priority_groups'],
            'shortcuts' => $payload['shortcuts'],
            'roles' => $payload['roles'],
            'role_summary' => $payload['role_summary'],
        ];
    }

    /**
     * Tasks page feed — work queues only (no ASAP / ops chat messages).
     *
     * @return array<string, mixed>
     */
    public function tasks(User $user): array
    {
        $home = $this->home($user);
        $items = $this->withoutMessages($home['items']);

        return [
            ...$home,
            'items' => $items,
            'open_count' => array_sum(array_map(fn (array $item) => (int) ($item['count'] ?? 1), $items)),
            'unread_count' => count(array_filter($items, fn (array $item) => $item['unread'])),
            'unread_items' => array_values(array_filter($items, fn (array $item) => $item['unread'])),
            'priority_groups' => $this->priorityGroups($items),
        ];
    }

    public function markRead(User $user, string $key, string $signature): void
    {
        if (! Schema::hasTable('staff_attention_reads') || $key === '') {
            return;
        }

        StaffAttentionRead::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'item_key' => $key,
            ],
            [
                'signature' => $signature,
                'read_at' => now(),
            ],
        );

        $this->forgetCache($user);
    }

    public function markOpened(User $user, string $key): void
    {
        if (! $user->isOperationsAdmin() || $user->isRestrictedStaff() || $key === '') {
            return;
        }

        foreach ($this->payload($user)['items'] as $item) {
            if ((string) ($item['key'] ?? '') !== $key) {
                continue;
            }

            $this->markRead($user, (string) $item['key'], (string) $item['signature']);

            return;
        }
    }

    public function markAllRead(User $user): void
    {
        foreach ($this->payload($user)['items'] as $item) {
            $this->markRead($user, (string) $item['key'], (string) $item['signature']);
        }
    }

    /**
     * @return array{
     *     roles: list<array<string, mixed>>,
     *     role_summary: string,
     *     items: list<array<string, mixed>>,
     *     shortcuts: list<array<string, mixed>>,
     *     open_count: int,
     *     unread_count: int,
     *     unread_items: list<array<string, mixed>>
     * }
     */
    /**
     * Super Admin priority inbox for Overview + notification context.
     *
     * @return array{priority_groups: list<array<string, mixed>>, open_count: int, unread_count: int}
     */
    public function superAdminInbox(User $user): array
    {
        abort_unless($user->isSuperAdmin(), 403);

        $approvals = app(ApprovalService::class)->superAdminAttentionItems();
        $escalations = app(StaffCaseReferralService::class)->superAdminAttentionItems();
        $moderation = app(ModerationAttentionService::class)->superAdminItems($user);
        $patrol = [
            ...$this->patrolJobItems($user),
            ...$this->patrolReviewItems($user),
        ];
        $items = array_merge($approvals, $escalations, $moderation, $patrol);
        $items = array_map(fn (array $item) => $this->withReadState($user, $item), $items);
        $unread = count(array_filter($items, fn (array $item) => ! empty($item['unread'])));

        return [
            'priority_groups' => $this->priorityGroups($items),
            'open_count' => count($items),
            'unread_count' => $unread,
        ];
    }

    public function payload(User $user): array
    {
        $cacheKey = 'opsAttention.'.$user->id;
        $request = request();

        if ($request?->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $roles = $user->isRestrictedStaff() ? [] : OpsDutyPresenter::badges($user);
        $shortcuts = $user->isRestrictedStaff() ? [] : $this->shortcuts($user);
        $items = $user->isRestrictedStaff() ? [] : $this->items($user);
        $unread = array_values(array_filter($items, fn (array $item) => $item['unread']));

        $payload = [
            'roles' => $roles,
            'role_summary' => OpsDutyPresenter::summary($roles),
            'greeting' => $this->greeting(),
            'items' => $items,
            'priority_groups' => $this->priorityGroups($items),
            'shortcuts' => $shortcuts,
            'duty' => $user->isRestrictedStaff() ? null : $this->duty($user, $roles, $items),
            'escalations' => $user->isRestrictedStaff() ? [] : $this->escalations($user),
            'escalate' => $user->isRestrictedStaff() ? null : $this->escalate($user),
            'open_count' => array_sum(array_map(fn (array $item) => (int) ($item['count'] ?? 1), $items)),
            'unread_count' => count($unread),
            'unread_items' => $unread,
        ];

        $request?->attributes->set($cacheKey, $payload);

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(User $user): array
    {
        $items = [
            ...$this->myApprovalItems($user),
            ...$this->assignedReferralItems($user),
            ...$this->patrolJobItems($user),
            ...$this->patrolReviewItems($user),
            ...$this->supportItems($user),
            ...$this->staffChatItems($user),
        ];

        usort($items, function (array $left, array $right) {
            $urgency = ((int) $right['urgency']) <=> ((int) $left['urgency']);

            if ($urgency !== 0) {
                return $urgency;
            }

            return ((int) ($right['sort_at'] ?? 0)) <=> ((int) ($left['sort_at'] ?? 0));
        });

        return array_values($items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function forgetCache(User $user): void
    {
        $request = request();
        $request?->attributes->remove('opsAttention.'.$user->id);
        $request?->attributes->remove('opsAttentionReads.'.$user->id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function myApprovalItems(User $user): array
    {
        if ($user->isRestrictedStaff() || $user->isSuperAdmin() || ! Schema::hasTable('admin_approvals')) {
            return [];
        }

        $count = app(ApprovalService::class)->pendingCountFor($user);
        if ($count === 0) {
            return [];
        }

        return [
            $this->withReadState($user, [
                'key' => 'my-approvals',
                'signature' => 'my-approvals:'.$count,
                'urgency' => 55,
                'sort_at' => now()->timestamp,
                'title' => $count === 1
                    ? '1 request awaiting Super Admin approval'
                    : $count.' requests awaiting Super Admin approval',
                'subtitle' => 'Track what you submitted for review',
                'href' => route('admin.my-approvals.index'),
                'icon' => 'ti ti-clock-hour-4',
                'tone' => 'medium',
                'queue' => 'My approvals',
                'group' => 'my_approvals',
                'priority' => 'medium',
                'count' => $count,
            ]),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assignedReferralItems(User $user): array
    {
        if ($user->isRestrictedStaff() || ! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $rows = app(StaffCaseReferralService::class)->attentionItems($user);

        return array_map(
            fn (array $item) => $this->withReadState($user, $item),
            $rows,
        );
    }

    private function patrolJobItems(User $user): array
    {
        if (! $user->canDo('patrol.view') || ! Schema::hasTable('patrol_cases')) {
            return [];
        }

        $cases = PatrolCase::query()
            ->open()
            ->jobs()
            ->with([
                'artisan:id,name,first_name,last_name,business_name',
                'rules',
            ])
            ->orderByDesc('flagged_at')
            ->limit(40)
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        $high = $cases
            ->filter(fn (PatrolCase $case) => PatrolSeverity::isHigh((string) $case->severity))
            ->take(8)
            ->values();

        $remaining = $cases->reject(fn (PatrolCase $case) => $high->contains('id', $case->id))->values();

        $items = $high->map(fn (PatrolCase $case) => $this->withReadState($user, [
            'key' => 'patrol:job:'.$case->id,
            'signature' => $this->signature([
                $case->id,
                $case->severity,
                $case->status,
                optional($case->updated_at)->timestamp,
            ]),
            'urgency' => self::URGENCY_HIGH_PATROL,
            'sort_at' => optional($case->flagged_at)->timestamp ?? 0,
            'title' => 'High-severity job log flagged',
            'subtitle' => $this->patrolSubtitle($case),
            'href' => route('admin.patrol.show', $case),
            'icon' => 'ti ti-flag',
            'tone' => 'high',
            'queue' => 'Job logs patrol',
            'group' => 'patrol_jobs',
            'priority' => 'high',
            'count' => 1,
        ]))->all();

        if ($remaining->isNotEmpty()) {
            $latest = $remaining->sortByDesc(fn (PatrolCase $case) => optional($case->flagged_at)->timestamp ?? 0)->first();
            $maxSeverity = (string) PatrolSeverity::max($remaining->pluck('severity')->all());
            $count = $remaining->count();

            $items[] = $this->withReadState($user, [
                'key' => 'patrol:jobs',
                'signature' => $this->signature([
                    $count,
                    $remaining->max('id'),
                    $maxSeverity,
                ]),
                'urgency' => self::URGENCY_PATROL_QUEUE,
                'sort_at' => optional($latest?->flagged_at)->timestamp ?? 0,
                'title' => $count === 1
                    ? '1 flagged job log to review'
                    : $count.' flagged job logs to review',
                'subtitle' => config('patrol.severities.'.$maxSeverity, Str::headline($maxSeverity)).' severity',
                'href' => route('admin.patrol.jobs'),
                'icon' => 'ti ti-binoculars',
                'tone' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'queue' => 'Job logs patrol',
                'group' => 'patrol_jobs',
                'priority' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'count' => $count,
            ]);
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function patrolReviewItems(User $user): array
    {
        if (! $user->canDo('patrol.view') || ! Schema::hasTable('patrol_cases')) {
            return [];
        }

        $cases = PatrolCase::query()
            ->open()
            ->reviews()
            ->with([
                'artisan:id,name,first_name,last_name,business_name',
                'rules',
                'review:id,rating,comment',
            ])
            ->orderByDesc('flagged_at')
            ->limit(40)
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        $high = $cases
            ->filter(fn (PatrolCase $case) => PatrolSeverity::isHigh((string) $case->severity))
            ->take(8)
            ->values();

        $remaining = $cases->reject(fn (PatrolCase $case) => $high->contains('id', $case->id))->values();

        $items = $high->map(fn (PatrolCase $case) => $this->withReadState($user, [
            'key' => 'patrol:review:'.$case->id,
            'signature' => $this->signature([
                $case->id,
                $case->severity,
                $case->status,
                optional($case->updated_at)->timestamp,
            ]),
            'urgency' => self::URGENCY_HIGH_PATROL - 5,
            'sort_at' => optional($case->flagged_at)->timestamp ?? 0,
            'title' => 'High-severity review flagged',
            'subtitle' => $this->patrolSubtitle($case),
            'href' => route('admin.patrol.show', $case),
            'icon' => 'ti ti-star',
            'tone' => 'high',
            'queue' => 'Reviews patrol',
            'group' => 'patrol_reviews',
            'priority' => 'high',
            'count' => 1,
        ]))->all();

        if ($remaining->isNotEmpty()) {
            $latest = $remaining->sortByDesc(fn (PatrolCase $case) => optional($case->flagged_at)->timestamp ?? 0)->first();
            $maxSeverity = (string) PatrolSeverity::max($remaining->pluck('severity')->all());
            $count = $remaining->count();

            $items[] = $this->withReadState($user, [
                'key' => 'patrol:reviews',
                'signature' => $this->signature([
                    $count,
                    $remaining->max('id'),
                    $maxSeverity,
                ]),
                'urgency' => self::URGENCY_PATROL_QUEUE - 5,
                'sort_at' => optional($latest?->flagged_at)->timestamp ?? 0,
                'title' => $count === 1
                    ? '1 flagged review to check'
                    : $count.' flagged reviews to check',
                'subtitle' => config('patrol.severities.'.$maxSeverity, Str::headline($maxSeverity)).' severity',
                'href' => route('admin.patrol.reviews'),
                'icon' => 'ti ti-star-half',
                'tone' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'queue' => 'Reviews patrol',
                'group' => 'patrol_reviews',
                'priority' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'count' => $count,
            ]);
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function supportItems(User $user): array
    {
        if (! $user->canDo('admin.support.manage') || ! Schema::hasTable('support_tickets')) {
            return [];
        }

        $tickets = SupportTicket::query()
            ->with('user:id,name,first_name,last_name')
            ->whereIn('status', [SupportTicket::STATUS_NEW, SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])
            ->where(function ($query) use ($user) {
                $query->whereNull('assigned_to_user_id')
                    ->orWhere('assigned_to_user_id', $user->id);
            })
            ->orderBy('created_at')
            ->limit(6)
            ->get();

        if ($tickets->isEmpty()) {
            return [];
        }

        if ($tickets->count() === 1) {
            $ticket = $tickets->first();

            return [$this->supportTicketItem($user, $ticket)];
        }

        $oldest = $tickets->first();
        $count = $tickets->count();
        $hours = max(0, (int) ($oldest->created_at?->diffInHours(now()) ?? 0));
        $priority = $hours >= 3 ? 'high' : 'medium';
        $subject = trim((string) $oldest->subject);

        return [$this->withReadState($user, [
            'key' => 'support:queue',
            'signature' => $this->signature([
                $count,
                $tickets->max('id'),
                $tickets->pluck('status')->join(','),
            ]),
            'urgency' => $priority === 'high' ? self::URGENCY_SUPPORT + 10 : self::URGENCY_SUPPORT,
            'sort_at' => now()->timestamp - (int) ($oldest->created_at?->timestamp ?? now()->timestamp),
            'title' => $count.' customer chats waiting',
            'subtitle' => ($subject !== '' ? $subject : 'Open ticket').' · '.$this->waitingLabel($oldest->created_at),
            'href' => route('admin.support.index'),
            'icon' => 'ti ti-headset',
            'tone' => $priority === 'high' ? 'high' : 'support',
            'queue' => 'Customer support',
            'group' => 'support',
            'priority' => $priority,
            'count' => $count,
        ])];
    }

    /**
     * @return array<string, mixed>
     */
    private function supportTicketItem(User $user, SupportTicket $ticket): array
    {
        $hours = max(0, (int) ($ticket->created_at?->diffInHours(now()) ?? 0));
        $priority = $hours >= 3 ? 'high' : 'medium';
        $subject = trim((string) $ticket->subject);

        return $this->withReadState($user, [
            'key' => 'support:'.$ticket->id,
            'signature' => $this->signature([
                $ticket->id,
                $ticket->status,
                optional($ticket->last_reply_at ?? $ticket->updated_at)->timestamp,
            ]),
            'urgency' => $priority === 'high' ? self::URGENCY_SUPPORT + 10 : self::URGENCY_SUPPORT,
            'sort_at' => now()->timestamp - (int) ($ticket->created_at?->timestamp ?? now()->timestamp),
            'title' => $subject !== '' ? $subject : 'Open support ticket',
            'subtitle' => 'Customer support · '.$this->waitingLabel($ticket->created_at),
            'href' => $ticket->adminShowUrl(),
            'icon' => 'ti ti-headset',
            'tone' => $priority === 'high' ? 'high' : 'support',
            'queue' => 'Customer support',
            'group' => 'support',
            'priority' => $priority,
            'count' => 1,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function staffChatItems(User $user): array
    {
        if (! $user->isStaff() || ! Schema::hasTable('staff_conversations')) {
            return [];
        }

        $presenter = app(StaffChatPresenter::class);
        $inbox = app(StaffChatService::class)->inboxFor($user);

        $unread = $inbox
            ->map(fn ($conversation) => $presenter->inboxItem($conversation, $user))
            ->filter(fn (array $item) => $item['unread'])
            ->values();

        if ($unread->isEmpty()) {
            return [];
        }

        $items = [];
        $asap = $unread->firstWhere('type', 'asap');

        if ($asap) {
            $items[] = $this->withReadState($user, [
                'key' => 'asap:chat',
                'signature' => $this->signature([
                    $asap['uid'],
                    $asap['when_iso'] ?? '',
                ]),
                'urgency' => 55,
                'sort_at' => strtotime((string) ($asap['when_iso'] ?? '')) ?: now()->timestamp,
                'title' => 'ASAP messages',
                'subtitle' => $asap['subtitle'] ?? 'New messages in the group chat',
                'href' => $asap['href'],
                'icon' => 'ti ti-bolt',
                'tone' => 'medium',
                'queue' => 'ASAP',
                'group' => 'asap',
                'priority' => 'medium',
                'count' => 1,
            ]);
        }

        $direct = $unread->where('type', 'direct')->values();

        if ($direct->count() === 1) {
            $message = $direct->first();
            $peer = $message['peer']['name'] ?? 'Ops teammate';

            $items[] = $this->withReadState($user, [
                'key' => 'ops:dm:'.$message['uid'],
                'signature' => $this->signature([
                    $message['uid'],
                    $message['when_iso'] ?? '',
                ]),
                'urgency' => 50,
                'sort_at' => strtotime((string) ($message['when_iso'] ?? '')) ?: now()->timestamp,
                'title' => 'Message from '.$peer,
                'subtitle' => $message['subtitle'] ?? 'Direct message',
                'href' => $message['href'],
                'icon' => 'ti ti-message',
                'tone' => 'medium',
                'queue' => 'Ops chat',
                'group' => 'ops_chat',
                'priority' => 'medium',
                'count' => 1,
            ]);
        } elseif ($direct->count() > 1) {
            $latest = $direct->sortByDesc(fn (array $item) => strtotime((string) ($item['when_iso'] ?? '')) ?: 0)->first();
            $count = $direct->count();

            $items[] = $this->withReadState($user, [
                'key' => 'ops:messages',
                'signature' => $this->signature([
                    $count,
                    $direct->pluck('uid')->join(','),
                ]),
                'urgency' => 50,
                'sort_at' => strtotime((string) ($latest['when_iso'] ?? '')) ?: now()->timestamp,
                'title' => 'Ops messages ('.$count.')',
                'subtitle' => ($latest['peer']['name'] ?? 'Teammates').' · '.$this->waitingLabel(
                    filled($latest['when_iso'] ?? null) ? Carbon::parse($latest['when_iso']) : null,
                ),
                'href' => route('admin.asap.index'),
                'icon' => 'ti ti-messages',
                'tone' => 'medium',
                'queue' => 'Ops chat',
                'group' => 'ops_chat',
                'priority' => 'medium',
                'count' => $count,
            ]);
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function withoutMessages(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item) => ! in_array((string) ($item['group'] ?? ''), ['asap', 'ops_chat'], true),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function priorityGroups(array $items): array
    {
        $catalog = [
            'escalations' => ['label' => 'Staff escalations', 'icon' => 'ti ti-arrow-up-right-circle'],
            'approvals' => ['label' => 'Pending approvals', 'icon' => 'ti ti-shield-check'],
            'moderation' => ['label' => 'Moderation', 'icon' => 'ti ti-shield-check'],
            'assigned' => ['label' => 'Assigned to me', 'icon' => 'ti ti-user-check'],
            'my_approvals' => ['label' => 'My approvals', 'icon' => 'ti ti-clock-hour-4'],
            'patrol_jobs' => ['label' => 'Job logs patrol', 'icon' => 'ti ti-binoculars'],
            'patrol_reviews' => ['label' => 'Reviews patrol', 'icon' => 'ti ti-star-half'],
            'support' => ['label' => 'Customer support', 'icon' => 'ti ti-headset'],
            'asap' => ['label' => 'ASAP', 'icon' => 'ti ti-bolt'],
            'ops_chat' => ['label' => 'Ops chat', 'icon' => 'ti ti-messages'],
        ];

        $grouped = collect($items)
            ->groupBy(fn (array $item) => (string) ($item['group'] ?? 'other'))
            ->map(function (Collection $rows, string $key) use ($catalog) {
                $meta = $catalog[$key] ?? ['label' => Str::headline($key), 'icon' => 'ti ti-circle'];

                return [
                    'key' => $key,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'count' => (int) $rows->sum(fn (array $item) => (int) ($item['count'] ?? 1)),
                    'items' => $rows->values()->all(),
                ];
            });

        return collect(array_keys($catalog))
            ->filter(fn (string $key) => $grouped->has($key))
            ->map(fn (string $key) => $grouped->get($key))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shortcuts(User $user): array
    {
        $counts = $this->shortcutCounts($user);

        return collect(config('admin.ops_shortcuts', []))
            ->filter(fn (array $item) => $this->canSeeShortcut($user, $item))
            ->map(function (array $item) use ($counts) {
                $key = (string) $item['key'];

                try {
                    $href = route($item['route']);
                } catch (\Throwable) {
                    $href = '#';
                }

                return [
                    'key' => $key,
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'href' => $href,
                    'count' => $counts[$key] ?? 0,
                    'hint' => (string) ($item['hint'] ?? 'open'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{ability?: string, abilitiesAny?: list<string>}  $item
     */
    private function canSeeShortcut(User $user, array $item): bool
    {
        if (! empty($item['opsOnly']) && $user->isSuperAdmin()) {
            return false;
        }

        if (! empty($item['abilitiesAny'])) {
            return collect($item['abilitiesAny'])->contains(fn (string $ability) => $user->canDo($ability));
        }

        $ability = $item['ability'] ?? null;

        return $ability ? $user->canDo($ability) : true;
    }

    /**
     * @return array<string, int>
     */
    private function shortcutCounts(User $user): array
    {
        $counts = [];

        if ($user->canDo('admin.support.manage') && Schema::hasTable('support_tickets')) {
            $counts['support'] = SupportTicket::query()
                ->whereIn('status', [SupportTicket::STATUS_NEW, SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])
                ->where(function ($query) use ($user) {
                    $query->whereNull('assigned_to_user_id')
                        ->orWhere('assigned_to_user_id', $user->id);
                })
                ->count();
        }

        if ($user->canDo('patrol.view') && Schema::hasTable('patrol_cases')) {
            $counts['patrol_jobs'] = PatrolCase::query()
                ->open()
                ->jobs()
                ->count();
            $counts['patrol_reviews'] = PatrolCase::query()
                ->open()
                ->reviews()
                ->count();
        }

        if ($user->canDo('admin.content.manage') && Schema::hasTable('work_logs')) {
            $counts['jobs'] = WorkLog::query()
                ->whereNotNull('flagged_at')
                ->whereNull('removed_at')
                ->count();
        }

        if ($user->canDo('admin.content.manage') && Schema::hasTable('reviews')) {
            $counts['reviews'] = Review::query()
                ->whereNotNull('flagged_at')
                ->whereNull('removed_at')
                ->count();
        }

        if ($user->canDo('admin.users.view')) {
            $counts['users'] = User::query()
                ->artisans()
                ->whereNull('email_verified_at')
                ->count();

            if (Schema::hasTable('referrals')) {
                $counts['referrals'] = Referral::query()
                    ->where('status', Referral::STATUS_SIGNED_UP)
                    ->whereNull('rewarded_at')
                    ->count();
            }
        }

        if ($user->canDo('hr.leave.manage') && Schema::hasTable('leave_requests')) {
            $counts['hr'] = LeaveRequest::query()
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->count();
        }

        if ($user->canDo('admin.messaging.manage') && Schema::hasTable('announcements')) {
            $counts['messaging'] = Announcement::query()
                ->whereIn('status', [Announcement::STATUS_DRAFT, Announcement::STATUS_SCHEDULED])
                ->count();
        }

        if ($user->isStaff() && Schema::hasTable('staff_conversations')) {
            $counts['asap'] = app(StaffChatService::class)->unreadCount($user);
        }

        if ($user->canDo('admin.billing_issues.manage') && Schema::hasTable('billing_issues')) {
            $counts['billing'] = BillingIssue::query()
                ->whereIn('status', [BillingIssue::STATUS_OPEN, BillingIssue::STATUS_ASSIGNED])
                ->when(
                    ! $user->isSuperAdmin(),
                    fn ($query) => $query->where('assigned_to_user_id', $user->id),
                )
                ->count();
        }

        if ($user->canDo('admin.approvals.manage') && Schema::hasTable('admin_approvals')) {
            $counts['approvals'] = AdminApproval::query()
                ->where('status', AdminApproval::STATUS_PENDING)
                ->count();
        }

        if (! $user->isSuperAdmin() && Schema::hasTable('admin_approvals')) {
            $counts['my_approvals'] = app(ApprovalService::class)->pendingCountFor($user);
        }

        if (app(\App\Support\Admin\ModerationDesk\ModerationDeskService::class)->canAccess($user)) {
            $counts['moderation_desk'] = app(\App\Support\Admin\ModerationDesk\ModerationDeskService::class)->stats($user)['all'] ?? 0;
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function withReadState(User $user, array $item): array
    {
        $reads = $this->readsFor($user);
        $saved = $reads->get($item['key']);
        $item['unread'] = ! $saved || $saved->signature !== $item['signature'];

        return $item;
    }

    /**
     * @return Collection<string, StaffAttentionRead>
     */
    private function readsFor(User $user): Collection
    {
        $cacheKey = 'opsAttentionReads.'.$user->id;
        $request = request();

        if ($request?->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $reads = ! Schema::hasTable('staff_attention_reads')
            ? collect()
            : StaffAttentionRead::query()
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('item_key');

        $request?->attributes->set($cacheKey, $reads);

        return $reads;
    }

    private function patrolSubtitle(PatrolCase $case): string
    {
        $rule = $case->rules
            ->sortByDesc(fn (PatrolCaseRule $rule) => PatrolSeverity::rank((string) $rule->severity))
            ->first();

        $ruleLabel = $rule?->label() ?: 'Flagged';
        $who = $this->personLabel($case->artisan);

        return $ruleLabel.' · '.$who;
    }

    private function personLabel(?User $user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        $first = trim((string) $user->first_name) ?: Str::of((string) $user->name)->before(' ')->toString();
        $last = trim((string) $user->last_name) ?: Str::of((string) $user->name)->after(' ')->toString();

        if ($first !== '' && $last !== '' && $last !== $first) {
            return $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.';
        }

        if ($first !== '') {
            return $first;
        }

        return $user->displayBusinessName();
    }

    private function waitingLabel(?Carbon $since): string
    {
        if (! $since) {
            return 'Waiting for a reply';
        }

        $minutes = max(1, (int) $since->diffInMinutes(now()));

        if ($minutes < 60) {
            return 'Oldest waiting '.$minutes.' '.Str::plural('minute', $minutes);
        }

        $hours = max(1, (int) $since->diffInHours(now()));

        if ($hours < 48) {
            return 'Oldest waiting '.$hours.' '.Str::plural('hour', $hours);
        }

        $days = max(1, (int) $since->diffInDays(now()));

        return 'Oldest waiting '.$days.' '.Str::plural('day', $days);
    }

    private function givenName(User $user): string
    {
        $first = trim((string) $user->first_name);

        if ($first !== '') {
            return $first;
        }

        return Str::of((string) $user->name)->before(' ')->toString() ?: 'there';
    }

    /**
     * @param  list<array<string, mixed>>  $roles
     * @param  list<array<string, mixed>>  $items
     * @return array{badge: string, rows: list<array{label: string, value: string}>}
     */
    private function duty(User $user, array $roles, array $items): array
    {
        $tz = (string) config('app.display_timezone', config('app.timezone'));
        $role = collect($roles)->pluck('name')->filter()->first()
            ?: collect($roles)->pluck('short')->filter()->first()
            ?: 'Operations';
        $signedIn = $user->last_login_at
            ? $user->last_login_at->timezone($tz)->format('H:i')
            : '—';
        $open = array_sum(array_map(fn (array $item) => (int) ($item['count'] ?? 1), $items));
        $notices = Schema::hasTable('disciplinary_actions')
            ? $user->pendingDisciplinaryNoticeCount()
            : 0;

        return [
            'badge' => 'On duty',
            'rows' => [
                ['label' => 'Role', 'value' => $role],
                ['label' => 'Signed in', 'value' => $signedIn],
                ['label' => 'Open work', 'value' => str_pad((string) min(99, $open), 2, '0', STR_PAD_LEFT)],
                ['label' => 'Notices', 'value' => str_pad((string) min(99, $notices), 2, '0', STR_PAD_LEFT)],
            ],
        ];
    }

    /**
     * @return array{href: string, label: string}|null
     */
    private function escalate(User $user): ?array
    {
        $targets = [
            ['ability' => 'patrol.view', 'route' => 'admin.patrol.jobs'],
            ['ability' => 'admin.content.manage', 'route' => 'admin.jobs.index'],
        ];

        foreach ($targets as $target) {
            if ($user->canDo($target['ability']) && Route::has($target['route'])) {
                return [
                    'href' => route($target['route']),
                    'label' => 'Escalate a case',
                ];
            }
        }

        return null;
    }

    /**
     * @return list<array{label: string, status: string, tone: string, href: string}>
     */
    private function escalations(User $user): array
    {
        $items = app(StaffCaseReferralService::class)->outboundEscalations($user);

        if ($user->canDo('patrol.view') && Schema::hasTable('patrol_cases')) {
            $pending = PatrolCase::query()
                ->with('artisan:id,name,first_name,last_name')
                ->where('status', PatrolCase::STATUS_PENDING_APPROVAL)
                ->latest('updated_at')
                ->limit(4)
                ->get();

            foreach ($pending as $case) {
                $items[] = [
                    'label' => 'Flag #'.$case->id,
                    'status' => 'In review',
                    'tone' => 'review',
                    'href' => route('admin.patrol.show', $case),
                ];
            }

            $resolved = PatrolCase::query()
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', now()->subDays(14))
                ->latest('resolved_at')
                ->limit(2)
                ->get();

            foreach ($resolved as $case) {
                $items[] = [
                    'label' => 'Flag #'.$case->id,
                    'status' => 'Resolved',
                    'tone' => 'resolved',
                    'href' => route('admin.patrol.show', $case),
                ];
            }
        }

        if ($user->canDo('admin.content.manage') && Schema::hasTable('work_logs') && Schema::hasColumn('work_logs', 'referred_at')) {
            WorkLog::query()
                ->where('referred_by_user_id', $user->id)
                ->whereNotNull('referred_at')
                ->latest('referred_at')
                ->limit(3)
                ->get()
                ->each(function (WorkLog $log) use (&$items) {
                    $items[] = [
                        'label' => 'Job '.($log->uid ?: '#'.$log->id),
                        'status' => 'In review',
                        'tone' => 'review',
                        'href' => route('admin.jobs.index', ['job' => $log->uid, 'tab' => 'flagged']),
                    ];
                });
        }

        return array_slice($items, 0, 6);
    }

    private function greeting(): string
    {
        $hour = now()->timezone((string) config('app.display_timezone', config('app.timezone')))->hour;

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    /**
     * @param  list<mixed>  $parts
     */
    private function signature(array $parts): string
    {
        return substr(sha1(implode('|', array_map(fn ($part) => (string) $part, $parts))), 0, 32);
    }
}
