<?php

namespace App\Support\Admin;

use App\Enums\StaffStatus;
use App\Enums\UserRole;
use App\Models\AdminAuditLog;
use App\Models\PatrolCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Staff\StaffPresence;
use App\Support\Staff\StaffShift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OpsInsightsService
{
    public function __construct(private readonly StaffPresence $presence) {}

    /**
     * @param  array{
     *     range?: string,
     *     from?: string|null,
     *     to?: string|null,
     *     queue?: string,
     *     q?: string,
     *     sort?: string
     * }  $filters
     * @return array<string, mixed>
     */
    public function payload(User $viewer, array $filters = []): array
    {
        $team = $viewer->isSuperAdmin();
        $rangeId = (string) ($filters['range'] ?? 'this_week');
        $bounds = DateRange::bounds($rangeId, $filters['from'] ?? null, $filters['to'] ?? null);
        $utc = DateRange::utc($bounds);
        $queue = $this->normalizeQueue((string) ($filters['queue'] ?? 'all'));
        $sort = $this->normalizeSort((string) ($filters['sort'] ?? 'score'));
        $search = trim((string) ($filters['q'] ?? ''));

        $staff = $this->staff();
        $ids = $staff->pluck('id')->all();
        $onLeave = array_flip($this->presence->onLeaveIds($ids));
        $lastSeen = $this->presence->lastSeenMap($staff);

        $snapshots = $this->snapshotWindows();
        $lookback = $this->earliestFrom($utc['from'], $snapshots);
        $audit = $this->auditBuckets($ids, $lookback);
        $support = $this->supportEvents($ids, $lookback);
        $patrolResolved = $this->patrolResolvedEvents($ids, $lookback);
        $liveWork = $this->liveWork($ids);

        $todayUtc = DateRange::utc($snapshots['today']);
        $weekUtc = DateRange::utc($snapshots['this_week']);
        $monthUtc = DateRange::utc($snapshots['this_month']);

        $rows = $staff->map(function (User $user) use (
            $audit, $support, $patrolResolved, $liveWork, $onLeave, $lastSeen,
            $utc, $queue, $todayUtc, $weekUtc, $monthUtc
        ) {
            $leave = isset($onLeave[$user->id]);
            $live = $this->presence->snapshot($user, $lastSeen[$user->id] ?? null, $leave);
            $actions = $audit[$user->id] ?? [];
            $rangeStats = $this->windowStats($user->id, $actions, $support, $patrolResolved, $utc['from'], $utc['to'], $queue);
            $todayStats = $this->windowStats($user->id, $actions, $support, $patrolResolved, $todayUtc['from'], $todayUtc['to'], $queue);
            $weekStats = $this->windowStats($user->id, $actions, $support, $patrolResolved, $weekUtc['from'], $weekUtc['to'], $queue);
            $monthStats = $this->windowStats($user->id, $actions, $support, $patrolResolved, $monthUtc['from'], $monthUtc['to'], $queue);

            return [
                'id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'email' => $user->email,
                'initials' => $this->initials($user),
                'roles' => collect($user->staffRoles)
                    ->filter(fn ($role) => $role->is_active)
                    ->map(fn ($role) => [
                        'slug' => $role->slug,
                        'name' => $role->name,
                    ])
                    ->values()
                    ->all(),
                'live' => [
                    ...$live,
                    'tasks' => $liveWork[$user->id] ?? [],
                    'attendance' => StaffShift::attendance($user, $this->presence),
                ],
                'today' => $todayStats,
                'this_week' => $weekStats,
                'this_month' => $monthStats,
                'range' => $rangeStats,
            ];
        });

        $maxCompleted = max(1, (int) $rows->max(fn (array $row) => $row['range']['completed']));
        $maxActions = max(1, (int) $rows->max(fn (array $row) => $row['range']['actions']));

        $rows = $rows->map(function (array $row) use ($maxCompleted, $maxActions) {
            $row['range']['score'] = $this->score($row['range'], $row['live'], $maxCompleted, $maxActions);
            $row['range']['grade'] = $this->grade($row['range']['score']['total']);

            return $row;
        });

        if ($search !== '') {
            $needle = Str::lower($search);
            $rows = $rows->filter(fn (array $row) => str_contains(Str::lower($row['name'].' '.$row['email'].' '.$row['uid']), $needle));
        }

        $rows = $this->sortRows($rows, $sort)->values();

        if (! $team) {
            $rows = $rows->where('id', $viewer->id)->values();
        }

        $kpis = [
            'staff' => $rows->count(),
            'on_duty' => $rows->where('live.on_duty', true)->count(),
            'away' => $rows->where('live.status', 'away')->count(),
            'active_now' => $rows->whereIn('live.status', ['active', 'idle'])->count(),
            'completed' => (int) $rows->sum(fn (array $row) => $row['range']['completed']),
            'actions' => (int) $rows->sum(fn (array $row) => $row['range']['actions']),
            'open_tasks' => (int) $rows->sum(fn (array $row) => count($row['live']['tasks'])),
            'avg_score' => $rows->isEmpty()
                ? 0
                : (int) round($rows->avg(fn (array $row) => $row['range']['score']['total'])),
        ];

        return [
            'team' => $team,
            'filters' => [
                'range' => $rangeId,
                'from' => $bounds['from']?->toDateString(),
                'to' => $bounds['to']?->toDateString(),
                'queue' => $queue,
                'q' => $search,
                'sort' => $sort,
            ],
            'range_label' => $bounds['label'] ?? DateRange::label($rangeId),
            'queues' => $this->queueOptions(),
            'kpis' => $kpis,
            'staff' => $rows->all(),
            'poll_ms' => (int) config('admin.ops_insights.live_poll_ms', 15000),
            'idle_after_minutes' => (int) config('admin.ops_insights.idle_after_minutes', 7),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function live(User $viewer): array
    {
        $staff = $this->staff($viewer);
        $ids = $staff->pluck('id')->all();
        $onLeave = array_flip($this->presence->onLeaveIds($ids));
        $lastSeen = $this->presence->lastSeenMap($staff);
        $liveWork = $this->liveWork($ids);

        $people = $staff->map(function (User $user) use ($onLeave, $lastSeen, $liveWork) {
            $live = $this->presence->snapshot($user, $lastSeen[$user->id] ?? null, isset($onLeave[$user->id]));

            return [
                'id' => $user->id,
                'live' => [
                    ...$live,
                    'tasks' => $liveWork[$user->id] ?? [],
                    'attendance' => StaffShift::attendance($user, $this->presence),
                ],
            ];
        })->values()->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'staff' => $people,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function staff(?User $viewer = null): Collection
    {
        $query = User::query()
            ->where('role', UserRole::OperationsAdmin)
            ->where('staff_status', StaffStatus::Active)
            ->whereNull('suspended_at')
            ->with(['staffRoles' => fn ($roles) => $roles->where('is_active', true)])
            ->orderBy('name');

        if ($viewer && ! $viewer->isSuperAdmin()) {
            $query->whereKey($viewer->id);
        }

        return $query->get();
    }

    /**
     * @return array{today: array{from: Carbon, to: Carbon}, this_week: array{from: Carbon, to: Carbon}, this_month: array{from: Carbon, to: Carbon}}
     */
    private function snapshotWindows(): array
    {
        return [
            'today' => DateRange::bounds('today'),
            'this_week' => DateRange::bounds('this_week'),
            'this_month' => DateRange::bounds('this_month'),
        ];
    }

    private function earliestFrom(?Carbon $rangeFrom, array $snapshots): Carbon
    {
        $candidates = [
            $rangeFrom,
            $snapshots['today']['from']?->copy()->utc(),
            $snapshots['this_week']['from']?->copy()->utc(),
            $snapshots['this_month']['from']?->copy()->utc(),
        ];

        if ($rangeFrom === null) {
            $candidates[] = now()->subDays(90)->startOfDay()->utc();
        }

        $earliest = collect($candidates)->filter()->sortBy(fn (Carbon $date) => $date->timestamp)->first();

        return ($earliest ?? now()->startOfMonth())->copy()->utc();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<array{action: string, created_at: string}>>
     */
    private function auditBuckets(array $ids, Carbon $fromUtc): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = AdminAuditLog::query()
            ->whereIn('actor_id', $ids)
            ->where('created_at', '>=', $fromUtc)
            ->get(['actor_id', 'action', 'created_at']);

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row->actor_id][] = [
                'action' => (string) $row->action,
                'created_at' => $row->created_at?->toIso8601String(),
                'at' => $row->created_at?->getTimestamp() ?? 0,
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<array{type: string, at: int, csat: int|null, seconds: int|null}>>
     */
    private function supportEvents(array $ids, Carbon $fromUtc): array
    {
        $map = [];

        foreach ($ids as $id) {
            $map[$id] = [];
        }

        if ($ids === []) {
            return $map;
        }

        $resolved = SupportTicket::query()
            ->whereIn('assigned_to_user_id', $ids)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $fromUtc)
            ->get(['assigned_to_user_id', 'resolved_at', 'csat_score']);

        foreach ($resolved as $ticket) {
            $map[(int) $ticket->assigned_to_user_id][] = [
                'type' => 'resolved',
                'at' => $ticket->resolved_at?->getTimestamp() ?? 0,
                'csat' => $ticket->csat_score ? (int) $ticket->csat_score : null,
                'seconds' => null,
            ];
        }

        $responded = SupportTicket::query()
            ->whereIn('assigned_to_user_id', $ids)
            ->whereNotNull('first_response_at')
            ->where('first_response_at', '>=', $fromUtc)
            ->get(['assigned_to_user_id', 'created_at', 'first_response_at']);

        foreach ($responded as $ticket) {
            $seconds = ($ticket->created_at && $ticket->first_response_at)
                ? max(0, $ticket->created_at->diffInSeconds($ticket->first_response_at))
                : 0;
            $map[(int) $ticket->assigned_to_user_id][] = [
                'type' => 'first_response',
                'at' => $ticket->first_response_at?->getTimestamp() ?? 0,
                'csat' => null,
                'seconds' => $seconds,
            ];
        }

        return $map;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<int>>
     */
    private function patrolResolvedEvents(array $ids, Carbon $fromUtc): array
    {
        $map = [];

        foreach ($ids as $id) {
            $map[$id] = [];
        }

        if ($ids === []) {
            return $map;
        }

        $cases = PatrolCase::query()
            ->whereIn('resolved_by', $ids)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $fromUtc)
            ->get(['resolved_by', 'resolved_at']);

        foreach ($cases as $case) {
            $map[(int) $case->resolved_by][] = $case->resolved_at?->getTimestamp() ?? 0;
        }

        return $map;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<array{type: string, label: string, href: string, waiting: string|null}>>
     */
    private function liveWork(array $ids): array
    {
        $work = [];

        foreach ($ids as $id) {
            $work[$id] = [];
        }

        if ($ids === []) {
            return $work;
        }

        $tickets = SupportTicket::query()
            ->whereIn('assigned_to_user_id', $ids)
            ->whereIn('status', [
                SupportTicket::STATUS_NEW,
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_PENDING,
            ])
            ->with('user:id,name')
            ->orderByRaw('COALESCE(last_customer_message_at, created_at) asc')
            ->get(['id', 'uid', 'assigned_to_user_id', 'subject', 'status', 'last_customer_message_at', 'user_id']);

        foreach ($tickets as $ticket) {
            $id = (int) $ticket->assigned_to_user_id;
            $work[$id][] = [
                'type' => 'support',
                'label' => $ticket->user?->name ?: ($ticket->subject ?: 'Customer chat'),
                'href' => $ticket->adminShowUrl(),
                'waiting' => $ticket->status === SupportTicket::STATUS_PENDING ? 'Waiting on customer' : 'Open chat',
            ];
        }

        $cases = PatrolCase::query()
            ->open()
            ->whereIn('assigned_to', $ids)
            ->with(['artisan:id,name'])
            ->latest('flagged_at')
            ->get(['id', 'assigned_to', 'kind', 'status', 'user_id', 'severity']);

        foreach ($cases as $case) {
            $id = (int) $case->assigned_to;
            $work[$id][] = [
                'type' => 'patrol',
                'label' => ($case->artisan?->name ?: 'Patrol').' · '.str_replace('_', ' ', $case->status),
                'href' => route('admin.patrol.show', $case),
                'waiting' => $case->severityLabel(),
            ];
        }

        return $work;
    }

    /**
     * @param  list<array{action: string, created_at?: string, at: int}>  $actions
     * @param  array<int, list<array{type: string, at: int, csat: int|null, seconds: int|null}>>  $support
     * @param  array<int, list<int>>  $patrolResolved
     * @return array<string, mixed>
     */
    private function windowStats(
        int $userId,
        array $actions,
        array $support,
        array $patrolResolved,
        ?Carbon $from,
        ?Carbon $to,
        string $queue,
    ): array {
        $fromTs = $from?->timestamp;
        $toTs = $to?->timestamp;
        $completions = config('admin.ops_insights.completion_actions', []);
        $byQueue = [];
        $completed = 0;
        $total = 0;

        foreach ($this->queueOptions() as $option) {
            if ($option['key'] === 'all') {
                continue;
            }
            $byQueue[$option['key']] = 0;
        }

        foreach ($actions as $action) {
            $at = (int) ($action['at'] ?? 0);
            if ($fromTs && $at < $fromTs) {
                continue;
            }
            if ($toTs && $at > $toTs) {
                continue;
            }

            $key = $this->queueFor((string) $action['action']);
            if ($queue !== 'all' && $key !== $queue) {
                continue;
            }

            $total++;
            if ($key) {
                $byQueue[$key] = ($byQueue[$key] ?? 0) + 1;
            }
            if (in_array($action['action'], $completions, true) || str_starts_with((string) $action['action'], 'patrol.recommended_')) {
                $completed++;
            }
        }

        $chatsResolved = 0;
        $csatSum = 0;
        $csatCount = 0;
        $firstSum = 0;
        $firstCount = 0;

        if ($queue === 'all' || $queue === 'support') {
            foreach ($support[$userId] ?? [] as $event) {
                $at = (int) ($event['at'] ?? 0);
                if ($fromTs && $at < $fromTs) {
                    continue;
                }
                if ($toTs && $at > $toTs) {
                    continue;
                }
                if ($event['type'] === 'resolved') {
                    $chatsResolved++;
                    if ($event['csat']) {
                        $csatSum += (int) $event['csat'];
                        $csatCount++;
                    }
                }
                if ($event['type'] === 'first_response' && $event['seconds'] !== null) {
                    $firstSum += (int) $event['seconds'];
                    $firstCount++;
                }
            }
        }

        $patrolCount = 0;
        if ($queue === 'all' || $queue === 'patrol') {
            foreach ($patrolResolved[$userId] ?? [] as $at) {
                if ($fromTs && $at < $fromTs) {
                    continue;
                }
                if ($toTs && $at > $toTs) {
                    continue;
                }
                $patrolCount++;
            }
        }

        return [
            'completed' => $completed,
            'actions' => $total,
            'by_queue' => $byQueue,
            'chats_resolved' => $chatsResolved,
            'patrol_resolved' => $patrolCount,
            'csat' => $csatCount > 0 ? round($csatSum / $csatCount, 1) : null,
            'avg_first_response_minutes' => $firstCount > 0 ? (int) round($firstSum / $firstCount / 60) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $stats
     * @param  array<string, mixed>  $live
     * @return array{total: int, completions: int, responsiveness: int, volume: int, presence: int}
     */
    private function score(array $stats, array $live, int $maxCompleted, int $maxActions): array
    {
        $weights = config('admin.ops_insights.score_weights', [
            'completions' => 40,
            'responsiveness' => 25,
            'volume' => 20,
            'presence' => 15,
        ]);

        $completionScore = (int) round(min(100, ($stats['completed'] / max(1, $maxCompleted)) * 100));
        $volumeScore = (int) round(min(100, ($stats['actions'] / max(1, $maxActions)) * 100));

        $minutes = $stats['avg_first_response_minutes'];
        $responseScore = $minutes === null
            ? ($stats['completed'] > 0 ? 70 : 50)
            : (int) round(max(0, 100 - min(100, ($minutes / 60) * 100)));
        $csatScore = $stats['csat'] !== null ? (int) round(((float) $stats['csat'] / 5) * 100) : 55;
        $responsiveness = (int) round(($responseScore * 0.7) + ($csatScore * 0.3));

        $presence = match ($live['status'] ?? 'unknown') {
            'active' => 100,
            'idle' => 80,
            'away' => max(15, 55 - (int) floor(((int) ($live['away_seconds'] ?? 0)) / 300) * 5),
            'leave' => 90,
            'off_duty' => 70,
            default => 45,
        };

        if ($stats['completed'] === 0 && $stats['actions'] === 0 && ($live['status'] ?? '') !== 'active') {
            $completionScore = min($completionScore, 35);
            $volumeScore = min($volumeScore, 35);
        }

        $total = (int) round(
            ($completionScore * $weights['completions']
                + $responsiveness * $weights['responsiveness']
                + $volumeScore * $weights['volume']
                + $presence * $weights['presence']) / 100,
        );

        return [
            'total' => max(0, min(100, $total)),
            'completions' => $completionScore,
            'responsiveness' => $responsiveness,
            'volume' => $volumeScore,
            'presence' => $presence,
        ];
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 55 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRows(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'name' => $rows->sortBy(fn (array $row) => Str::lower($row['name']), SORT_NATURAL),
            'completed' => $rows->sortByDesc(fn (array $row) => $row['range']['completed']),
            'actions' => $rows->sortByDesc(fn (array $row) => $row['range']['actions']),
            'away' => $rows->sortByDesc(fn (array $row) => (int) ($row['live']['away_seconds'] ?? -1)),
            'open' => $rows->sortByDesc(fn (array $row) => count($row['live']['tasks'])),
            default => $rows->sortByDesc(fn (array $row) => $row['range']['score']['total']),
        };
    }

    /**
     * @return list<array{key: string, label: string, icon: string}>
     */
    private function queueOptions(): array
    {
        $items = [['key' => 'all', 'label' => 'All queues', 'icon' => 'ti ti-layout-grid']];

        foreach (config('admin.ops_insights.queues', []) as $key => $queue) {
            $items[] = [
                'key' => (string) $key,
                'label' => (string) ($queue['label'] ?? Str::headline((string) $key)),
                'icon' => (string) ($queue['icon'] ?? 'ti ti-circle'),
            ];
        }

        return $items;
    }

    private function queueFor(string $action): ?string
    {
        foreach (config('admin.ops_insights.queues', []) as $key => $queue) {
            foreach ($queue['prefixes'] ?? [] as $prefix) {
                if ($action === $prefix || str_starts_with($action, (string) $prefix)) {
                    return (string) $key;
                }
            }
        }

        return null;
    }

    private function normalizeQueue(string $queue): string
    {
        if ($queue === 'all' || isset(config('admin.ops_insights.queues')[$queue])) {
            return $queue;
        }

        return 'all';
    }

    private function normalizeSort(string $sort): string
    {
        return in_array($sort, ['score', 'name', 'completed', 'actions', 'away', 'open'], true)
            ? $sort
            : 'score';
    }

    private function initials(User $user): string
    {
        $parts = array_filter([$user->first_name, $user->last_name]);

        if ($parts === []) {
            $parts = preg_split('/\s+/', (string) $user->name) ?: [];
        }

        return strtoupper(substr(implode('', array_map(fn ($part) => mb_substr((string) $part, 0, 1), $parts)), 0, 2));
    }
}
