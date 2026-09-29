<?php

namespace App\Support\Admin;

use App\Models\AdminApproval;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
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
        if ($key === '') {
            return;
        }

        if (str_starts_with($key, 'announcement:')) {
            $deliveryId = (int) substr($key, strlen('announcement:'));

            if ($deliveryId > 0) {
                $delivery = AnnouncementDelivery::query()
                    ->whereKey($deliveryId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($delivery && $delivery->status !== AnnouncementDelivery::STATUS_READ) {
                    $delivery->forceFill([
                        'status' => AnnouncementDelivery::STATUS_READ,
                        'read_at' => now(),
                    ])->save();

                    if ($delivery->announcement) {
                        app(AnnouncementService::class)->refreshCounts($delivery->announcement);
                    }
                }
            }

            $this->forgetCache($user);

            return;
        }

        if (! Schema::hasTable('staff_attention_reads')) {
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
        if (! $user->isStaff() || $user->isRestrictedStaff() || $key === '') {
            return;
        }

        foreach ($this->attentionItemsFor($user) as $item) {
            if ((string) ($item['key'] ?? '') !== $key) {
                continue;
            }

            $this->markRead($user, (string) $item['key'], (string) $item['signature']);

            return;
        }
    }

    public function markGroupOpened(User $user, string $group): void
    {
        if (! $user->isStaff() || $user->isRestrictedStaff() || $group === '') {
            return;
        }

        foreach ($this->attentionItemsFor($user) as $item) {
            if ((string) ($item['group'] ?? '') !== $group) {
                continue;
            }

            $this->markRead($user, (string) $item['key'], (string) $item['signature']);
        }
    }

    public function markAllRead(User $user): void
    {
        foreach ($this->attentionItemsFor($user) as $item) {
            $this->markRead($user, (string) $item['key'], (string) $item['signature']);
        }

        if ($user->isSuperAdmin()) {
            $this->markRelatedAnnouncementsRead($user, null, null, null, ['staff_escalation']);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attentionItemsFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $this->superAdminInbox($user)['items'] ?? [];
        }

        return $this->payload($user)['items'] ?? [];
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
        $lifecycle = [
            ...$this->onboardingItems($user),
            ...$this->dormantItems($user),
        ];
        $patrol = [
            ...$this->patrolJobItems($user),
            ...$this->patrolReviewItems($user),
        ];
        $items = array_merge($approvals, $escalations, $moderation, $lifecycle, $patrol);
        $items = array_map(function (array $item) use ($user) {
            $item = $this->withReadState($user, $item);

            // Escalations stay unread until acknowledged or explicitly marked read after ack.
            if (($item['group'] ?? '') === 'escalations' && empty($item['acknowledged'])) {
                $item['unread'] = true;
            }

            return $item;
        }, $items);
        $unread = array_values(array_filter($items, fn (array $item) => ! empty($item['unread'])));

        return [
            'priority_groups' => $this->priorityGroups($items),
            'items' => $items,
            'unread_items' => $unread,
            'open_count' => count($items),
            'unread_count' => count($unread),
        ];
    }

    /**
     * Shape Super Admin attention items for the shared NotificationBell.
     *
     * @return list<array<string, mixed>>
     */
    public function superAdminNotificationItems(User $user): array
    {
        if (! $user->isSuperAdmin()) {
            return [];
        }

        return array_map(function (array $item) {
            return [
                'id' => 'attention:'.($item['key'] ?? uniqid('attn_', true)),
                'attention_key' => (string) ($item['key'] ?? ''),
                'signature' => (string) ($item['signature'] ?? ''),
                'referral_id' => $item['referral_id'] ?? null,
                'acknowledge_escalation' => ($item['group'] ?? '') === 'escalations' && empty($item['acknowledged']),
                'title' => (string) ($item['title'] ?? 'Needs attention'),
                'body' => (string) ($item['subtitle'] ?? ''),
                'time' => 'Needs attention',
                'icon' => (string) ($item['icon'] ?? 'ti ti-bell'),
                'unread' => true,
                'href' => $item['href'] ?? null,
            ];
        }, $this->superAdminInbox($user)['unread_items'] ?? []);
    }

    /**
     * @param  array{unread_count: int, items: list<array<string, mixed>>}  $payload
     * @return array{unread_count: int, items: list<array<string, mixed>>}
     */
    public function mergeSuperAdminNotifications(User $user, array $payload): array
    {
        if (! $user->isSuperAdmin()) {
            return $payload;
        }

        $attentionByReferral = collect($this->superAdminNotificationItems($user))
            ->filter(fn (array $item) => ! empty($item['referral_id']))
            ->keyBy(fn (array $item) => (int) $item['referral_id']);

        $existing = array_map(function (array $item) use ($attentionByReferral) {
            $referralId = isset($item['referral_id']) ? (int) $item['referral_id'] : 0;
            $kind = (string) ($item['kind'] ?? '');

            if ($referralId > 0 && $kind === 'staff_escalation' && $attentionByReferral->has($referralId)) {
                $match = $attentionByReferral->get($referralId);
                $item['attention_key'] = $match['attention_key'] ?? null;
                $item['signature'] = $match['signature'] ?? null;
                $item['acknowledge_escalation'] = ! empty($match['acknowledge_escalation']);
                $item['referral_id'] = $referralId;
            }

            return $item;
        }, array_values($payload['items'] ?? []));

        $seenReferralIds = collect($existing)
            ->map(fn (array $item) => isset($item['referral_id']) ? (int) $item['referral_id'] : null)
            ->filter()
            ->all();
        $seenHrefs = collect($existing)->pluck('href')->filter()->all();
        $seenAttentionKeys = collect($existing)
            ->pluck('attention_key')
            ->filter()
            ->all();

        $extra = array_values(array_filter(
            $this->superAdminNotificationItems($user),
            function (array $item) use ($seenHrefs, $seenReferralIds, $seenAttentionKeys) {
                $attentionKey = (string) ($item['attention_key'] ?? '');

                if ($attentionKey !== '' && in_array($attentionKey, $seenAttentionKeys, true)) {
                    return false;
                }

                if (! empty($item['referral_id']) && in_array((int) $item['referral_id'], $seenReferralIds, true)) {
                    return false;
                }

                $href = $item['href'] ?? null;

                return ! $href || ! in_array($href, $seenHrefs, true);
            },
        ));

        $items = array_slice([...$extra, ...$existing], 0, 24);
        $unread = count(array_filter($items, fn (array $item) => ! empty($item['unread'])));

        return [
            'unread_count' => $unread,
            'items' => $items,
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
        $items = $user->isRestrictedStaff() ? [] : $this->items($user);
        $shortcuts = $user->isRestrictedStaff() ? [] : $this->shortcuts($user, $items);
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
            ...$this->referrerUpdateItems($user),
            ...$this->referrerAnnouncementItems($user),
            ...$this->patrolJobItems($user),
            ...$this->patrolReviewItems($user),
            ...$this->supportItems($user),
            ...$this->staffChatItems($user),
            ...$this->onboardingItems($user),
            ...$this->reengagementItems($user),
            ...$this->dormantItems($user),
            ...$this->flaggedJobItems($user),
            ...$this->flaggedReviewItems($user),
        ];

        usort($items, function (array $left, array $right) {
            $urgency = ((int) $right['urgency']) <=> ((int) $left['urgency']);

            if ($urgency !== 0) {
                return $urgency;
            }

            return ((int) ($right['sort_at'] ?? 0)) <=> ((int) ($left['sort_at'] ?? 0));
        });

        return $this->dedupeReferralUpdates(array_values($items));
    }

    /**
     * Prefer structured referral-update rows over duplicate announcement copies.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function dedupeReferralUpdates(array $items): array
    {
        $seenReferralIds = [];

        foreach ($items as $item) {
            if (($item['group'] ?? '') !== 'referral_updates') {
                continue;
            }

            if (! str_starts_with((string) ($item['key'] ?? ''), 'referral-update:')) {
                continue;
            }

            $referralId = (int) ($item['referral_id'] ?? 0);

            if ($referralId > 0) {
                $seenReferralIds[$referralId] = true;
            }
        }

        return array_values(array_filter(
            $items,
            function (array $item) use ($seenReferralIds) {
                if (($item['group'] ?? '') !== 'referral_updates') {
                    return true;
                }

                if (! str_starts_with((string) ($item['key'] ?? ''), 'announcement:')) {
                    return true;
                }

                $referralId = (int) ($item['referral_id'] ?? 0);

                return $referralId <= 0 || empty($seenReferralIds[$referralId]);
            },
        ));
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

    /**
     * @return list<array<string, mixed>>
     */
    private function referrerUpdateItems(User $user): array
    {
        if ($user->isRestrictedStaff() || ! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $rows = app(StaffCaseReferralService::class)->referrerUpdateItems($user);

        return array_map(
            fn (array $item) => $this->withReadState($user, $item),
            $rows,
        );
    }

    /**
     * In-app announcement copies of referral updates (falls back when structured rows are missing).
     *
     * @return list<array<string, mixed>>
     */
    private function referrerAnnouncementItems(User $user): array
    {
        if ($user->isRestrictedStaff() || ! Schema::hasTable('announcement_deliveries')) {
            return [];
        }

        return AnnouncementDelivery::query()
            ->with('announcement:id,title,subject,body,segment')
            ->where('user_id', $user->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->latest('id')
            ->limit(40)
            ->get()
            ->filter(function (AnnouncementDelivery $delivery) {
                $kind = (string) data_get($delivery->announcement?->segment, 'kind', '');

                return $kind === 'staff_referral_update';
            })
            ->map(function (AnnouncementDelivery $delivery) {
                $announcement = $delivery->announcement;
                $segment = is_array($announcement?->segment) ? $announcement->segment : [];
                $href = filled($segment['href'] ?? null)
                    ? (string) $segment['href']
                    : route('admin.dashboard');

                return [
                    'key' => 'announcement:'.$delivery->id,
                    'signature' => 'announcement:'.$delivery->id.':sent',
                    'referral_id' => isset($segment['referral_id']) ? (int) $segment['referral_id'] : null,
                    'urgency' => 66,
                    'sort_at' => optional($delivery->sent_at ?? $delivery->created_at)->timestamp ?? 0,
                    'title' => (string) ($announcement?->subject ?: $announcement?->title ?: 'Case update'),
                    'subtitle' => Str::limit((string) ($announcement?->body ?? ''), 90),
                    'href' => $href,
                    'icon' => 'ti ti-bell-check',
                    'tone' => 'medium',
                    'queue' => 'Your referrals',
                    'group' => 'referral_updates',
                    'priority' => 'medium',
                    'count' => 1,
                    'unread' => true,
                    'meta' => [
                        'delivery_id' => $delivery->id,
                        'subject_type' => isset($segment['subject_type']) ? (string) $segment['subject_type'] : null,
                        'subject_id' => isset($segment['subject_id']) ? (int) $segment['subject_id'] : null,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Clear referrer “case acted on” notifications once the linked case is opened.
     */
    public function markReferrerUpdatesOpened(
        User $user,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?int $referralId = null,
    ): void {
        if (! $user->isStaff() || $user->isRestrictedStaff()) {
            return;
        }

        if ($subjectType === null && $subjectId === null && $referralId === null) {
            return;
        }

        $items = $this->attentionItemsFor($user);

        foreach ($items as $item) {
            $group = (string) ($item['group'] ?? '');

            if ($user->isSuperAdmin() && $group !== 'escalations') {
                continue;
            }

            if ($user->isOperationsAdmin() && $group !== 'referral_updates') {
                continue;
            }

            if ($referralId !== null && (int) ($item['referral_id'] ?? 0) !== $referralId) {
                continue;
            }

            $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];

            if ($group === 'referral_updates' || $group === 'escalations') {
                if ($subjectType !== null && (string) ($meta['subject_type'] ?? '') !== $subjectType) {
                    continue;
                }

                if ($subjectId !== null && (int) ($meta['subject_id'] ?? 0) !== $subjectId) {
                    continue;
                }
            }

            $this->markRead($user, (string) $item['key'], (string) $item['signature']);
        }

        $kinds = $user->isSuperAdmin()
            ? ['staff_escalation']
            : ['staff_referral_update'];

        $this->markRelatedAnnouncementsRead($user, $subjectType, $subjectId, $referralId, $kinds);
    }

    /**
     * Clear in-app announcement copies that mirror attention items for the same case.
     *
     * @param  list<string>  $kinds
     */
    private function markRelatedAnnouncementsRead(
        User $user,
        ?string $subjectType,
        ?int $subjectId,
        ?int $referralId,
        array $kinds,
    ): void {
        if ($kinds === [] || ! Schema::hasTable('announcement_deliveries')) {
            return;
        }

        $deliveries = AnnouncementDelivery::query()
            ->with('announcement:id,segment')
            ->where('user_id', $user->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->latest('id')
            ->limit(80)
            ->get();

        foreach ($deliveries as $delivery) {
            $segment = is_array($delivery->announcement?->segment)
                ? $delivery->announcement->segment
                : [];
            $kind = (string) ($segment['kind'] ?? '');

            if (! in_array($kind, $kinds, true)) {
                continue;
            }

            if ($referralId !== null && (int) ($segment['referral_id'] ?? 0) !== $referralId) {
                continue;
            }

            if ($subjectType !== null && (string) ($segment['subject_type'] ?? '') !== ''
                && (string) $segment['subject_type'] !== $subjectType) {
                continue;
            }

            if ($subjectId !== null && isset($segment['subject_id'])
                && (int) $segment['subject_id'] !== $subjectId) {
                continue;
            }

            if ($referralId === null && ($subjectType !== null || $subjectId !== null)
                && ! isset($segment['subject_type']) && ! isset($segment['subject_id'])) {
                $announcementReferralId = (int) ($segment['referral_id'] ?? 0);

                if ($announcementReferralId <= 0) {
                    continue;
                }

                $matchesSubject = false;
                foreach ($this->attentionItemsFor($user) as $item) {
                    if ((int) ($item['referral_id'] ?? 0) !== $announcementReferralId) {
                        continue;
                    }

                    $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];

                    if ($subjectType !== null && (string) ($meta['subject_type'] ?? '') !== $subjectType) {
                        continue;
                    }

                    if ($subjectId !== null && (int) ($meta['subject_id'] ?? 0) !== $subjectId) {
                        continue;
                    }

                    $matchesSubject = true;
                    break;
                }

                if (! $matchesSubject) {
                    continue;
                }
            }

            $this->markRead($user, 'announcement:'.$delivery->id, 'announcement:'.$delivery->id.':sent');
        }
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
                'workLog:id,uid,description,subject',
            ])
            ->orderByRaw("CASE severity WHEN 'high' THEN 3 WHEN 'medium' THEN 2 ELSE 1 END DESC")
            ->orderByDesc('flagged_at')
            ->limit(60)
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        $featured = $cases->take(12)->values();
        $overflow = $cases->slice(12)->values();

        $items = $featured->map(function (PatrolCase $case) use ($user) {
            $severity = (string) $case->severity;
            $priority = PatrolSeverity::isHigh($severity)
                ? 'high'
                : ($severity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low');

            return $this->withReadState($user, [
                'key' => 'patrol:job:'.$case->id,
                'signature' => $this->signature([
                    $case->id,
                    $case->severity,
                    $case->status,
                    optional($case->updated_at)->timestamp,
                    $case->rules->pluck('rule_key')->sort()->implode(','),
                ]),
                'urgency' => PatrolSeverity::isHigh($severity)
                    ? self::URGENCY_HIGH_PATROL
                    : self::URGENCY_PATROL_QUEUE,
                'sort_at' => optional($case->flagged_at)->timestamp ?? 0,
                'title' => $this->patrolCaseTitle($case, 'job'),
                'subtitle' => $this->patrolSubtitle($case),
                'href' => route('admin.patrol.show', $case),
                'icon' => 'ti ti-binoculars',
                'tone' => $priority,
                'queue' => 'Job logs patrol',
                'group' => 'patrol_jobs',
                'priority' => $priority,
                'count' => 1,
                'meta' => [
                    'rules' => $case->rules->map(fn ($rule) => $rule->label())->filter()->values()->all(),
                    'severity' => $severity,
                ],
            ]);
        })->all();

        if ($overflow->isNotEmpty()) {
            $latest = $overflow->sortByDesc(fn (PatrolCase $case) => optional($case->flagged_at)->timestamp ?? 0)->first();
            $maxSeverity = (string) PatrolSeverity::max($overflow->pluck('severity')->all());
            $count = $overflow->count();

            $items[] = $this->withReadState($user, [
                'key' => 'patrol:jobs:more',
                'signature' => $this->signature([
                    $count,
                    $overflow->max('id'),
                    $maxSeverity,
                ]),
                'urgency' => self::URGENCY_PATROL_QUEUE,
                'sort_at' => optional($latest?->flagged_at)->timestamp ?? 0,
                'title' => $count === 1
                    ? '1 more flagged job log'
                    : $count.' more flagged job logs',
                'subtitle' => 'Open the full job logs patrol queue',
                'href' => route('admin.patrol.jobs'),
                'icon' => 'ti ti-binoculars',
                'tone' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'queue' => 'Job logs patrol',
                'group' => 'patrol_jobs',
                'priority' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'count' => $count,
            ]);
        }

        return array_values(array_filter($items, fn (array $item) => ! empty($item['unread'])));
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
            ->orderByRaw("CASE severity WHEN 'high' THEN 3 WHEN 'medium' THEN 2 ELSE 1 END DESC")
            ->orderByDesc('flagged_at')
            ->limit(60)
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        $featured = $cases->take(12)->values();
        $overflow = $cases->slice(12)->values();

        $items = $featured->map(function (PatrolCase $case) use ($user) {
            $severity = (string) $case->severity;
            $priority = PatrolSeverity::isHigh($severity)
                ? 'high'
                : ($severity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low');

            return $this->withReadState($user, [
                'key' => 'patrol:review:'.$case->id,
                'signature' => $this->signature([
                    $case->id,
                    $case->severity,
                    $case->status,
                    optional($case->updated_at)->timestamp,
                    $case->rules->pluck('rule_key')->sort()->implode(','),
                ]),
                'urgency' => PatrolSeverity::isHigh($severity)
                    ? self::URGENCY_HIGH_PATROL - 5
                    : self::URGENCY_PATROL_QUEUE - 5,
                'sort_at' => optional($case->flagged_at)->timestamp ?? 0,
                'title' => $this->patrolCaseTitle($case, 'review'),
                'subtitle' => $this->patrolSubtitle($case),
                'href' => route('admin.patrol.show', $case),
                'icon' => 'ti ti-star-half',
                'tone' => $priority,
                'queue' => 'Reviews patrol',
                'group' => 'patrol_reviews',
                'priority' => $priority,
                'count' => 1,
                'meta' => [
                    'rules' => $case->rules->map(fn ($rule) => $rule->label())->filter()->values()->all(),
                    'severity' => $severity,
                ],
            ]);
        })->all();

        if ($overflow->isNotEmpty()) {
            $latest = $overflow->sortByDesc(fn (PatrolCase $case) => optional($case->flagged_at)->timestamp ?? 0)->first();
            $maxSeverity = (string) PatrolSeverity::max($overflow->pluck('severity')->all());
            $count = $overflow->count();

            $items[] = $this->withReadState($user, [
                'key' => 'patrol:reviews:more',
                'signature' => $this->signature([
                    $count,
                    $overflow->max('id'),
                    $maxSeverity,
                ]),
                'urgency' => self::URGENCY_PATROL_QUEUE - 5,
                'sort_at' => optional($latest?->flagged_at)->timestamp ?? 0,
                'title' => $count === 1
                    ? '1 more flagged review'
                    : $count.' more flagged reviews',
                'subtitle' => 'Open the full reviews patrol queue',
                'href' => route('admin.patrol.reviews'),
                'icon' => 'ti ti-star-half',
                'tone' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'queue' => 'Reviews patrol',
                'group' => 'patrol_reviews',
                'priority' => $maxSeverity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low',
                'count' => $count,
            ]);
        }

        return array_values(array_filter($items, fn (array $item) => ! empty($item['unread'])));
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
            ->orderByRaw('COALESCE(last_customer_message_at, created_at) asc')
            ->limit(40)
            ->get()
            // Only surface chats that still need action: unclaimed, or unread customer activity.
            ->filter(fn (SupportTicket $ticket) => $this->supportNeedsAttention($ticket))
            ->values();

        if ($tickets->isEmpty()) {
            return [];
        }

        return $tickets
            ->map(fn (SupportTicket $ticket) => $this->supportTicketItem($user, $ticket))
            ->filter(fn (array $item) => ! empty($item['unread']))
            ->values()
            ->all();
    }

    private function supportNeedsAttention(SupportTicket $ticket): bool
    {
        if ($ticket->assigned_to_user_id === null) {
            return true;
        }

        if (! $ticket->last_customer_message_at) {
            return $ticket->status === SupportTicket::STATUS_NEW;
        }

        if (! $ticket->staff_last_read_at) {
            return true;
        }

        return $ticket->last_customer_message_at->gt($ticket->staff_last_read_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function supportTicketItem(User $user, SupportTicket $ticket): array
    {
        $waitFrom = $ticket->last_customer_message_at ?? $ticket->created_at;
        $hours = max(0, (int) ($waitFrom?->diffInHours(now()) ?? 0));
        $priority = $hours >= 3 ? 'high' : 'medium';
        $subject = trim((string) $ticket->subject);

        return $this->withReadState($user, [
            'key' => 'support:'.$ticket->id,
            'signature' => $this->signature([
                $ticket->id,
                $ticket->status,
                optional($ticket->last_customer_message_at)->timestamp ?? 0,
                $ticket->assigned_to_user_id === null ? 'unassigned' : 'assigned',
            ]),
            'urgency' => $priority === 'high' ? self::URGENCY_SUPPORT + 10 : self::URGENCY_SUPPORT,
            'sort_at' => optional($waitFrom)->timestamp ?? 0,
            'title' => $subject !== '' ? $subject : 'Open support ticket',
            'subtitle' => ($ticket->assigned_to_user_id === null ? 'Unassigned · ' : 'Customer support · ')
                .$this->waitingLabel($waitFrom),
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
            'referral_updates' => ['label' => 'Your referrals', 'icon' => 'ti ti-bell-check'],
            'my_approvals' => ['label' => 'My approvals', 'icon' => 'ti ti-clock-hour-4'],
            'patrol_jobs' => ['label' => 'Job logs patrol', 'icon' => 'ti ti-binoculars'],
            'patrol_reviews' => ['label' => 'Reviews patrol', 'icon' => 'ti ti-star-half'],
            'support' => ['label' => 'Customer support', 'icon' => 'ti ti-headset'],
            'onboarding' => ['label' => 'Onboarding follow-up', 'icon' => 'ti ti-route'],
            'reengagement' => ['label' => 'Re-engagement', 'icon' => 'ti ti-flame'],
            'dormant' => ['label' => 'Dormant / at-risk', 'icon' => 'ti ti-user-off'],
            'flagged_jobs' => ['label' => 'Flagged job logs', 'icon' => 'ti ti-briefcase'],
            'flagged_reviews' => ['label' => 'Flagged reviews', 'icon' => 'ti ti-star'],
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
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function shortcuts(User $user, array $items = []): array
    {
        $counts = $this->shortcutCounts($user, $items);

        return collect(config('admin.ops_shortcuts', []))
            ->filter(fn (array $item) => $this->canSeeShortcut($user, $item))
            ->map(function (array $item) use ($counts) {
                $key = (string) $item['key'];

                try {
                    $href = route($item['route'], $item['params'] ?? []);
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
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function shortcutCounts(User $user, array $items = []): array
    {
        $counts = [];
        $unreadByGroup = $this->unreadCountsByGroup($items);

        $attentionShortcuts = [
            'support' => 'support',
            'patrol_jobs' => 'patrol_jobs',
            'patrol_reviews' => 'patrol_reviews',
            'onboarding' => 'onboarding',
            'reengagement' => 'reengagement',
            'dormant' => 'dormant',
            'jobs' => 'flagged_jobs',
            'reviews' => 'flagged_reviews',
        ];

        foreach ($attentionShortcuts as $shortcutKey => $group) {
            $counts[$shortcutKey] = $unreadByGroup[$group] ?? 0;
        }

        if ($user->canDo('admin.users.view')) {
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
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function unreadCountsByGroup(array $items): array
    {
        $counts = [];

        foreach ($items as $item) {
            if (empty($item['unread'])) {
                continue;
            }

            $group = (string) ($item['group'] ?? '');

            if ($group === '') {
                continue;
            }

            $counts[$group] = ($counts[$group] ?? 0) + (int) ($item['count'] ?? 1);
        }

        return $counts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function onboardingItems(User $user): array
    {
        if (! $user->canDo('ops.onboarding.manage') || ! Schema::hasTable('users')) {
            return [];
        }

        $query = User::query()
            ->artisans()
            ->whereNull('suspended_at')
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('workLogs');

        $count = (clone $query)->count();

        if ($count === 0) {
            return [];
        }

        $latestId = (clone $query)->max('id');

        return [$this->withReadState($user, [
            'key' => 'onboarding:queue',
            'signature' => $this->signature([$count, $latestId]),
            'urgency' => 35,
            'sort_at' => now()->timestamp,
            'title' => $count === 1
                ? '1 artisan needs onboarding follow-up'
                : $count.' artisans need onboarding follow-up',
            'subtitle' => 'Verified but no first job logged yet',
            'href' => route('admin.onboarding.index'),
            'icon' => 'ti ti-route',
            'tone' => 'medium',
            'queue' => 'Onboarding follow-up',
            'group' => 'onboarding',
            'priority' => 'medium',
            'count' => $count,
        ])];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reengagementItems(User $user): array
    {
        if (! $user->canDo('ops.reengagement.manage') || ! Schema::hasTable('users')) {
            return [];
        }

        $cutoff = now()->subDays(30);
        $query = User::query()
            ->artisans()
            ->whereNull('suspended_at')
            ->where(function ($builder) use ($cutoff) {
                $builder->where('last_login_at', '<', $cutoff)
                    ->orWhere(fn ($inner) => $inner
                        ->whereNull('last_login_at')
                        ->where('created_at', '<', $cutoff));
            });

        $count = (clone $query)->count();

        if ($count === 0) {
            return [];
        }

        $latestId = (clone $query)->max('id');

        return [$this->withReadState($user, [
            'key' => 'reengagement:queue',
            'signature' => $this->signature([$count, $latestId]),
            'urgency' => 30,
            'sort_at' => now()->timestamp,
            'title' => $count === 1
                ? '1 inactive artisan to re-engage'
                : $count.' inactive artisans to re-engage',
            'subtitle' => 'No sign-in in the last 30 days',
            'href' => route('admin.reengagement.index', ['kind' => 'login']),
            'icon' => 'ti ti-flame',
            'tone' => 'medium',
            'queue' => 'Re-engagement',
            'group' => 'reengagement',
            'priority' => 'medium',
            'count' => $count,
        ])];
    }

    /**
     * Productive dormancy / at-risk artisans (jobs & reviews quiet).
     *
     * @return list<array<string, mixed>>
     */
    private function dormantItems(User $user): array
    {
        if (
            (! $user->canDo('ops.reengagement.manage') && ! $user->canDo('patrol.view'))
            || ! Schema::hasTable('users')
        ) {
            return [];
        }

        $count = app(\App\Support\Patrol\LifecyclePatrolReport::class)
            ->dormantQueryPublic(30)
            ->count();

        if ($count === 0) {
            return [];
        }

        $href = $user->canDo('ops.reengagement.manage')
            ? route('admin.reengagement.index', ['kind' => 'dormant', 'window' => '30'])
            : route('admin.patrol.dormant', ['window' => '30']);

        return [$this->withReadState($user, [
            'key' => 'dormant:queue',
            'signature' => $this->signature([$count, 30]),
            'urgency' => 32,
            'sort_at' => now()->timestamp,
            'title' => $count === 1
                ? '1 dormant / at-risk artisan'
                : $count.' dormant / at-risk artisans',
            'subtitle' => 'No jobs or reviews in 30+ days — follow up',
            'href' => $href,
            'icon' => 'ti ti-user-off',
            'tone' => 'medium',
            'queue' => 'Dormant / at-risk',
            'group' => 'dormant',
            'priority' => 'medium',
            'count' => $count,
        ])];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function flaggedJobItems(User $user): array
    {
        if (! $user->canDo('admin.content.manage') || ! Schema::hasTable('work_logs')) {
            return [];
        }

        $query = WorkLog::query()
            ->whereNotNull('flagged_at')
            ->whereNull('removed_at');

        $count = (clone $query)->count();

        if ($count === 0) {
            return [];
        }

        $latest = (clone $query)->latest('flagged_at')->first(['id', 'flagged_at', 'flag_reason']);

        return [$this->withReadState($user, [
            'key' => 'jobs:flagged',
            'signature' => $this->signature([
                $count,
                $latest?->id,
                optional($latest?->flagged_at)->timestamp,
            ]),
            'urgency' => 38,
            'sort_at' => optional($latest?->flagged_at)->timestamp ?? now()->timestamp,
            'title' => $count === 1
                ? '1 flagged job log'
                : $count.' flagged job logs',
            'subtitle' => filled($latest?->flag_reason)
                ? (string) $latest->flag_reason
                : 'Open the flagged jobs queue',
            'href' => route('admin.jobs.index', ['tab' => 'flagged']),
            'icon' => 'ti ti-briefcase',
            'tone' => 'medium',
            'queue' => 'Flagged job logs',
            'group' => 'flagged_jobs',
            'priority' => 'medium',
            'count' => $count,
        ])];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function flaggedReviewItems(User $user): array
    {
        if (! $user->canDo('admin.content.manage') || ! Schema::hasTable('reviews')) {
            return [];
        }

        $query = Review::query()
            ->whereNotNull('flagged_at')
            ->whereNull('removed_at');

        $count = (clone $query)->count();

        if ($count === 0) {
            return [];
        }

        $latest = (clone $query)->latest('flagged_at')->first(['id', 'flagged_at', 'flag_reason']);

        return [$this->withReadState($user, [
            'key' => 'reviews:flagged',
            'signature' => $this->signature([
                $count,
                $latest?->id,
                optional($latest?->flagged_at)->timestamp,
            ]),
            'urgency' => 37,
            'sort_at' => optional($latest?->flagged_at)->timestamp ?? now()->timestamp,
            'title' => $count === 1
                ? '1 flagged review'
                : $count.' flagged reviews',
            'subtitle' => filled($latest?->flag_reason)
                ? (string) $latest->flag_reason
                : 'Open the flagged reviews queue',
            'href' => route('admin.reviews.index', ['tab' => 'flagged']),
            'icon' => 'ti ti-star',
            'tone' => 'medium',
            'queue' => 'Flagged reviews',
            'group' => 'flagged_reviews',
            'priority' => 'medium',
            'count' => $count,
        ])];
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

    private function patrolCaseTitle(PatrolCase $case, string $kind): string
    {
        $rule = $case->rules
            ->sortByDesc(fn (PatrolCaseRule $rule) => PatrolSeverity::rank((string) $rule->severity))
            ->first();

        $label = $rule?->label() ?: ($kind === 'review' ? 'Review flagged' : 'Job log flagged');

        return $label;
    }

    private function patrolSubtitle(PatrolCase $case): string
    {
        $rule = $case->rules
            ->sortByDesc(fn (PatrolCaseRule $rule) => PatrolSeverity::rank((string) $rule->severity))
            ->first();

        $trigger = is_array($rule?->evidence) ? (string) ($rule->evidence['trigger'] ?? '') : '';
        $who = $this->personLabel($case->artisan);
        $severity = config('patrol.severities.'.$case->severity, Str::headline((string) $case->severity));

        if ($trigger !== '') {
            return $severity.' · '.$who.' · '.$trigger;
        }

        return $severity.' · '.$who;
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
