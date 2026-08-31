<?php

namespace App\Support\Admin\ModerationDesk;

use App\Models\PatrolCase;
use App\Models\PatrolCaseRule;
use App\Models\Review;
use App\Models\StaffCaseReferral;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\StaffCaseReferralService;
use App\Support\Patrol\PatrolSeverity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModerationDeskService
{
    public const FILTER_ALL = 'all';

    public const FILTER_JOBS = 'jobs';

    public const FILTER_REVIEWS = 'reviews';

    public const FILTER_PATROL = 'patrol';

    public const FILTER_REFERRALS = 'referrals';

    public const FILTER_USERS = 'users';

    public const FILTERS = [
        self::FILTER_ALL,
        self::FILTER_JOBS,
        self::FILTER_REVIEWS,
        self::FILTER_PATROL,
        self::FILTER_REFERRALS,
        self::FILTER_USERS,
    ];

    /**
     * @return array{
     *     filter: string,
     *     query: string,
     *     stats: array<string, int>,
     *     items: list<array<string, mixed>>
     * }
     */
    public function page(User $user, string $filter = self::FILTER_ALL, string $query = ''): array
    {
        $filter = in_array($filter, self::FILTERS, true) ? $filter : self::FILTER_ALL;
        $query = trim($query);

        $items = match ($filter) {
            self::FILTER_JOBS => $this->jobItems($user),
            self::FILTER_REVIEWS => $this->reviewItems($user),
            self::FILTER_PATROL => $this->patrolItems($user),
            self::FILTER_REFERRALS => $this->referralItems($user),
            self::FILTER_USERS => $this->userItems($user),
            default => $this->allItems($user),
        };

        if ($query !== '') {
            $needle = Str::lower($query);
            $items = array_values(array_filter(
                $items,
                fn (array $item) => Str::contains(Str::lower(implode(' ', array_filter([
                    $item['title'] ?? '',
                    $item['subtitle'] ?? '',
                    $item['subject_label'] ?? '',
                    $item['source_label'] ?? '',
                ]))), $needle),
            ));
        }

        usort($items, fn (array $left, array $right) => ((int) ($right['sort_at'] ?? 0)) <=> ((int) ($left['sort_at'] ?? 0)));

        return [
            'filter' => $filter,
            'query' => $query,
            'stats' => $this->stats($user),
            'items' => array_slice($items, 0, 100),
        ];
    }

    public function canAccess(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canDo('admin.content.manage')
            || $user->canDo('admin.moderation.manage')
            || $user->canDo('patrol.view')
            || $user->canDo('admin.users.view');
    }

    /**
     * @return array<string, int>
     */
    public function stats(User $user): array
    {
        return [
            'all' => count($this->allItems($user)),
            'jobs' => count($this->jobItems($user)),
            'reviews' => count($this->reviewItems($user)),
            'patrol' => count($this->patrolItems($user)),
            'referrals' => count($this->referralItems($user)),
            'users' => count($this->userItems($user)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allItems(User $user): array
    {
        $items = [
            ...$this->jobItems($user),
            ...$this->reviewItems($user),
            ...$this->patrolItems($user),
            ...$this->referralItems($user),
            ...$this->userItems($user),
        ];

        usort($items, fn (array $left, array $right) => ((int) ($right['sort_at'] ?? 0)) <=> ((int) ($left['sort_at'] ?? 0)));

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobItems(User $user): array
    {
        if (! $this->canSeeContent($user) || ! Schema::hasTable('work_logs')) {
            return [];
        }

        $items = [];

        if (Schema::hasColumn('work_logs', 'flagged_at')) {
            $items = array_merge($items, WorkLog::query()
                ->with(['user:id,name,email,business_name'])
                ->whereNotNull('flagged_at')
                ->whereNull('removed_at')
                ->latest('flagged_at')
                ->limit(30)
                ->get()
                ->map(fn (WorkLog $log) => $this->row(
                    id: 'desk:job:flagged:'.$log->uid,
                    kind: self::FILTER_JOBS,
                    source: 'manual_flag',
                    sourceLabel: 'Manual flag',
                    title: 'Flagged job log',
                    subtitle: $this->artisanLabel($log->user).' · '.Str::limit((string) ($log->flag_reason ?: $log->description), 72),
                    subjectLabel: $log->uid,
                    status: 'flagged',
                    severity: 'high',
                    sortAt: $log->flagged_at,
                    href: route('admin.jobs.index', ['job' => $log->uid, 'tab' => 'flagged']),
                    icon: 'ti ti-flag',
                ))
                ->all());
        }

        if (Schema::hasColumn('work_logs', 'hidden_at')) {
            $items = array_merge($items, WorkLog::query()
                ->with(['user:id,name,email,business_name'])
                ->whereNotNull('hidden_at')
                ->whereNull('removed_at')
                ->whereNull('flagged_at')
                ->latest('hidden_at')
                ->limit(20)
                ->get()
                ->map(fn (WorkLog $log) => $this->row(
                    id: 'desk:job:hidden:'.$log->uid,
                    kind: self::FILTER_JOBS,
                    source: 'hidden',
                    sourceLabel: 'Hidden',
                    title: 'Hidden job log',
                    subtitle: $this->artisanLabel($log->user).' · '.Str::limit((string) ($log->description ?: 'Hidden from public'), 72),
                    subjectLabel: $log->uid,
                    status: 'hidden',
                    severity: 'medium',
                    sortAt: $log->hidden_at,
                    href: route('admin.jobs.index', ['job' => $log->uid, 'tab' => 'hidden']),
                    icon: 'ti ti-eye-off',
                ))
                ->all());
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reviewItems(User $user): array
    {
        if (! $this->canSeeContent($user) || ! Schema::hasTable('reviews')) {
            return [];
        }

        $items = [];

        if (Schema::hasColumn('reviews', 'flagged_at')) {
            $items = array_merge($items, Review::query()
                ->with(['artisan:id,name,email,business_name'])
                ->whereNotNull('flagged_at')
                ->whereNull('removed_at')
                ->latest('flagged_at')
                ->limit(30)
                ->get()
                ->map(fn (Review $review) => $this->row(
                    id: 'desk:review:flagged:'.$review->uid,
                    kind: self::FILTER_REVIEWS,
                    source: 'manual_flag',
                    sourceLabel: 'Manual flag',
                    title: 'Flagged review',
                    subtitle: $review->rating.'★ · '.$this->artisanLabel($review->artisan).' · '.Str::limit((string) ($review->flag_reason ?: $review->comment), 72),
                    subjectLabel: $review->uid,
                    status: 'flagged',
                    severity: 'high',
                    sortAt: $review->flagged_at,
                    href: route('admin.reviews.index', ['review' => $review->uid, 'tab' => 'flagged']),
                    icon: 'ti ti-star',
                ))
                ->all());
        }

        if (Schema::hasColumn('reviews', 'hidden_at')) {
            $items = array_merge($items, Review::query()
                ->with(['artisan:id,name,email,business_name'])
                ->whereNotNull('hidden_at')
                ->whereNull('removed_at')
                ->whereNull('flagged_at')
                ->latest('hidden_at')
                ->limit(20)
                ->get()
                ->map(fn (Review $review) => $this->row(
                    id: 'desk:review:hidden:'.$review->uid,
                    kind: self::FILTER_REVIEWS,
                    source: 'hidden',
                    sourceLabel: 'Hidden',
                    title: 'Hidden review',
                    subtitle: $review->rating.'★ · '.$this->artisanLabel($review->artisan),
                    subjectLabel: $review->uid,
                    status: 'hidden',
                    severity: 'medium',
                    sortAt: $review->hidden_at,
                    href: route('admin.reviews.index', ['review' => $review->uid, 'tab' => 'hidden']),
                    icon: 'ti ti-eye-off',
                ))
                ->all());
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function patrolItems(User $user): array
    {
        if (! $user->canDo('patrol.view') || ! Schema::hasTable('patrol_cases')) {
            return [];
        }

        return PatrolCase::query()
            ->open()
            ->with([
                'artisan:id,name,email,business_name,first_name,last_name',
                'rules',
            ])
            ->latest('flagged_at')
            ->limit(40)
            ->get()
            ->map(function (PatrolCase $case) {
                $rule = $case->rules
                    ->sortByDesc(fn (PatrolCaseRule $rule) => PatrolSeverity::rank((string) $rule->severity))
                    ->first();
                $severity = (string) ($case->severity ?: $rule?->severity ?: PatrolCase::SEVERITY_MEDIUM);
                $kind = $case->kind === PatrolCase::KIND_REVIEW ? self::FILTER_REVIEWS : self::FILTER_JOBS;

                return $this->row(
                    id: 'desk:patrol:'.$case->id,
                    kind: self::FILTER_PATROL,
                    source: 'patrol',
                    sourceLabel: 'Patrol auto-flag',
                    title: $case->kind === PatrolCase::KIND_REVIEW ? 'Patrol flagged review' : 'Patrol flagged job log',
                    subtitle: ($rule?->label() ?: 'Flagged').' · '.$this->artisanLabel($case->artisan),
                    subjectLabel: $case->kind === PatrolCase::KIND_REVIEW ? 'Review patrol #'.$case->id : 'Job patrol #'.$case->id,
                    status: (string) $case->status,
                    severity: PatrolSeverity::isHigh($severity) ? 'high' : ($severity === PatrolCase::SEVERITY_MEDIUM ? 'medium' : 'low'),
                    sortAt: $case->flagged_at,
                    href: route('admin.patrol.show', $case),
                    icon: $case->kind === PatrolCase::KIND_REVIEW ? 'ti ti-star-half' : 'ti ti-binoculars',
                    meta: ['desk_kind' => $kind],
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function referralItems(User $user): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        $referrals = StaffCaseReferral::query()
            ->active()
            ->where('queue', StaffCaseReferral::QUEUE_MODERATION)
            ->with(['referredBy:id,name,email', 'assignee:id,name,email'])
            ->latest('referred_at')
            ->limit(40)
            ->get();

        $presenter = app(StaffCaseReferralService::class);

        return $referrals
            ->map(function (StaffCaseReferral $referral) use ($presenter, $user) {
                $row = $presenter->present($referral);
                if ($row === null) {
                    return null;
                }

                if (! $user->isSuperAdmin() && (int) $referral->assignee_user_id !== (int) $user->id && ! $user->canDo('admin.moderation.manage')) {
                    // Ops still see moderation queue items on the desk for comparison.
                }

                return $this->row(
                    id: 'desk:referral:'.$referral->id,
                    kind: self::FILTER_REFERRALS,
                    source: 'referral',
                    sourceLabel: 'Colleague referral',
                    title: $row['title'],
                    subtitle: trim(($row['referrer']['name'] ?? 'Staff').' · '.($referral->note ? Str::limit($referral->note, 72) : $row['subtitle'])),
                    subjectLabel: $row['subtitle'],
                    status: 'referred',
                    severity: 'medium',
                    sortAt: $referral->referred_at,
                    href: $row['href'],
                    icon: $row['icon'] ?: 'ti ti-transfer',
                    meta: [
                        'assignee' => $referral->assignee?->name,
                    ],
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function userItems(User $user): array
    {
        if (! $user->canDo('admin.users.view')) {
            return [];
        }

        $items = [];

        $suspended = User::query()
            ->artisans()
            ->whereNotNull('suspended_at')
            ->latest('suspended_at')
            ->limit(20)
            ->get()
            ->map(fn (User $artisan) => $this->row(
                id: 'desk:user:suspended:'.$artisan->id,
                kind: self::FILTER_USERS,
                source: 'suspended',
                sourceLabel: 'Suspended',
                title: 'Suspended artisan',
                subtitle: Str::limit((string) ($artisan->suspension_reason ?: 'Account suspended'), 72),
                subjectLabel: $artisan->displayBusinessName() ?: $artisan->email,
                status: 'suspended',
                severity: 'high',
                sortAt: $artisan->suspended_at,
                href: route('admin.users.show', $artisan),
                icon: 'ti ti-user-off',
            ))
            ->all();

        $unverified = User::query()
            ->artisans()
            ->whereNull('email_verified_at')
            ->whereNull('suspended_at')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (User $artisan) => $this->row(
                id: 'desk:user:unverified:'.$artisan->id,
                kind: self::FILTER_USERS,
                source: 'unverified',
                sourceLabel: 'Unverified',
                title: 'Unverified artisan',
                subtitle: $artisan->email,
                subjectLabel: $artisan->displayBusinessName() ?: $artisan->email,
                status: 'unverified',
                severity: 'low',
                sortAt: $artisan->created_at,
                href: route('admin.users.show', $artisan),
                icon: 'ti ti-user-question',
            ))
            ->all();

        return [...$suspended, ...$unverified];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        string $id,
        string $kind,
        string $source,
        string $sourceLabel,
        string $title,
        string $subtitle,
        string $subjectLabel,
        string $status,
        string $severity,
        ?Carbon $sortAt,
        string $href,
        string $icon,
        array $meta = [],
    ): array {
        $timezone = (string) config('app.display_timezone', config('app.timezone'));

        return [
            'id' => $id,
            'kind' => $kind,
            'source' => $source,
            'source_label' => $sourceLabel,
            'title' => $title,
            'subtitle' => $subtitle,
            'subject_label' => $subjectLabel,
            'status' => $status,
            'severity' => $severity,
            'sort_at' => $sortAt?->timestamp ?? 0,
            'age' => $sortAt?->timezone($timezone)->diffForHumans(),
            'href' => $href,
            'icon' => $icon,
            'meta' => $meta,
        ];
    }

    private function canSeeContent(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->canDo('admin.content.manage')
            || $user->canDo('admin.moderation.manage');
    }

    private function artisanLabel(?User $user): string
    {
        if (! $user) {
            return 'Unknown artisan';
        }

        return $user->displayBusinessName() ?: $user->name ?: $user->email ?: 'Unknown artisan';
    }
}
