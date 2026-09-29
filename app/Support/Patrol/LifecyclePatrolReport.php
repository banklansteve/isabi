<?php

namespace App\Support\Patrol;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Paginated dormant / single-session queues for Patrol lifecycle outreach.
 *
 * "Never returned" is based on productive product use (jobs logged / reviews),
 * not mere login silence — quiet for a few weeks after real work is normal.
 */
class LifecyclePatrolReport
{
    public const WINDOWS = [
        '30' => 30,
        '60' => 60,
        '90' => 90,
    ];

    public const SINGLE_SESSION_MIN_AGE_DAYS = 30;

    public const NEVER_RETURNED_DAYS = 30;

    /**
     * @return array{people: LengthAwarePaginator, counts: array<string, int>, window: string, windows: list<string>}
     */
    public function dormant(string $window = '30', int $perPage = 25, ?string $search = null): array
    {
        $window = array_key_exists($window, self::WINDOWS) ? $window : '30';
        $days = self::WINDOWS[$window];

        $counts = [];
        foreach (self::WINDOWS as $key => $quietDays) {
            $counts[$key] = $this->dormantQuery($quietDays)->count();
        }

        $paginator = $this->applySearch($this->dormantQuery($days), $search)
            ->withCount(['workLogs', 'reviews'])
            ->withMax('workLogs as last_job_at', 'created_at')
            ->withMax('workLogs as last_review_request_at', 'review_requested_at')
            ->withMax('reviews as last_review_at', 'submitted_at')
            ->orderByRaw('COALESCE(
                GREATEST(
                    COALESCE((SELECT MAX(wl.created_at) FROM work_logs wl WHERE wl.user_id = users.id), "1970-01-01"),
                    COALESCE((SELECT MAX(wl.review_requested_at) FROM work_logs wl WHERE wl.user_id = users.id AND wl.review_requested_at IS NOT NULL), "1970-01-01"),
                    COALESCE((SELECT MAX(r.submitted_at) FROM reviews r WHERE r.user_id = users.id), "1970-01-01")
                ),
                users.created_at
            ) asc')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (User $user) => $this->dormantRow($user))
        );

        return [
            'people' => $paginator,
            'counts' => $counts,
            'window' => $window,
            'windows' => array_keys(self::WINDOWS),
        ];
    }

    /**
     * @return array{people: LengthAwarePaginator, total: int}
     */
    public function singleSession(int $perPage = 25, ?string $search = null): array
    {
        $paginator = $this->applySearch($this->singleSessionQuery(), $search)
            ->withCount('workLogs')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (User $user) => $this->singleSessionRow($user))
        );

        return [
            'people' => $paginator,
            'total' => $paginator->total(),
        ];
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        $term = trim((string) $search);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('business_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('whatsapp', 'like', $like)
                ->orWhere('trade', 'like', $like)
                ->orWhere('uid', 'like', $like);
        });
    }

    /**
     * Artisans whose last productive activity (job logged or review requested/received)
     * is older than the quiet window — or who never did any of that and signed up long enough ago.
     */
    public function dormantQueryPublic(int $quietDays): Builder
    {
        return $this->dormantQuery($quietDays);
    }

    private function dormantQuery(int $quietDays): Builder
    {
        $cutoff = now()->subDays($quietDays);
        $cutoffStr = $cutoff->toDateTimeString();

        return User::query()
            ->artisans()
            ->whereNull('suspended_at')
            ->where('created_at', '<=', $cutoff)
            ->where(function (Builder $q) use ($cutoffStr) {
                // Never did productive work, and account is old enough for this window.
                $q->where(function (Builder $never) {
                    $never->whereDoesntHave('workLogs')
                        ->whereDoesntHave('reviews');
                })->orWhereRaw(
                    'GREATEST(
                        COALESCE((SELECT MAX(wl.created_at) FROM work_logs wl WHERE wl.user_id = users.id), "1970-01-01"),
                        COALESCE((SELECT MAX(wl.review_requested_at) FROM work_logs wl WHERE wl.user_id = users.id AND wl.review_requested_at IS NOT NULL), "1970-01-01"),
                        COALESCE((SELECT MAX(r.submitted_at) FROM reviews r WHERE r.user_id = users.id), "1970-01-01")
                    ) < ?',
                    [$cutoffStr]
                );
            });
    }

    private function singleSessionQuery(): Builder
    {
        return User::query()
            ->artisans()
            ->whereNull('suspended_at')
            ->where('created_at', '<=', now()->subDays(self::SINGLE_SESSION_MIN_AGE_DAYS))
            ->where(function (Builder $q) {
                $q->whereNull('last_login_at')
                    ->orWhereColumn('last_login_at', '<=', DB::raw('DATE_ADD(created_at, INTERVAL 1 DAY)'));
            })
            ->withCount('activityDays')
            ->having('activity_days_count', '<=', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function dormantRow(User $user): array
    {
        $lastJob = $user->last_job_at ? Carbon::parse($user->last_job_at) : null;
        $lastReviewRequest = $user->last_review_request_at
            ? Carbon::parse($user->last_review_request_at)
            : null;
        $lastReview = $user->last_review_at ? Carbon::parse($user->last_review_at) : null;

        $lastProductive = collect([$lastJob, $lastReviewRequest, $lastReview])
            ->filter()
            ->sortByDesc(fn (Carbon $d) => $d->timestamp)
            ->first();

        $anchor = $lastProductive ?? $user->created_at;
        $daysQuiet = $anchor ? (int) $anchor->diffInDays(now()) : null;
        $jobs = (int) ($user->work_logs_count ?? 0);
        $reviews = (int) ($user->reviews_count ?? 0);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'business_name' => $user->displayBusinessName(),
            'trade' => $user->trade,
            'state' => $user->state,
            'whatsapp' => $user->whatsapp,
            'email' => $user->email,
            'jobs' => $jobs,
            'reviews' => $reviews,
            'days_inactive' => $daysQuiet,
            'last_login_label' => $user->last_login_at
                ? $user->last_login_at->timezone(config('app.display_timezone'))->format('j M Y')
                : 'Never logged in',
            'last_job_label' => $lastJob
                ? $lastJob->timezone(config('app.display_timezone'))->format('j M Y')
                : 'No jobs yet',
            'last_review_label' => $lastReview
                ? $lastReview->timezone(config('app.display_timezone'))->format('j M Y')
                : ($lastReviewRequest
                    ? 'Requested '.$lastReviewRequest->timezone(config('app.display_timezone'))->format('j M Y')
                    : 'No reviews yet'),
            'risk' => $this->dormancyRisk($daysQuiet, $jobs, $reviews, $lastProductive === null),
            'profile_url' => route('admin.users.show', $user),
            'activity_url' => route('admin.users.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function singleSessionRow(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'business_name' => $user->displayBusinessName(),
            'trade' => $user->trade,
            'state' => $user->state,
            'whatsapp' => $user->whatsapp,
            'email' => $user->email,
            'jobs' => (int) ($user->work_logs_count ?? 0),
            'signed_up_label' => $user->created_at
                ->timezone(config('app.display_timezone'))
                ->format('j M Y'),
            'detail' => ($user->work_logs_count ?? 0) > 0
                ? 'Logged a job once, then disappeared — onboarding friction after first win.'
                : 'Signed up and never returned — classic onboarding drop-off.',
            'profile_url' => route('admin.users.show', $user),
            'activity_url' => route('admin.users.show', $user),
        ];
    }

    /**
     * @return array{level: string, label: string, detail: string}
     */
    private function dormancyRisk(?int $daysQuiet, int $jobs, int $reviews, bool $neverProductive): array
    {
        // Never returned: 30+ days with no job logged and no review activity
        // (includes signup-only accounts that never did productive work).
        if ($daysQuiet !== null && $daysQuiet >= self::NEVER_RETURNED_DAYS && ($neverProductive || ($jobs === 0 && $reviews === 0))) {
            return [
                'level' => 'critical',
                'label' => 'Never returned',
                'detail' => $neverProductive || ($jobs === 0 && $reviews === 0)
                    ? 'Signed up 30+ days ago without logging a job or getting a review.'
                    : "No job or review activity for {$daysQuiet} days.",
            ];
        }

        if ($daysQuiet !== null && $daysQuiet >= self::NEVER_RETURNED_DAYS && $jobs + $reviews > 0) {
            // Had productive work once, then silent on jobs/reviews for 30+ days.
            if ($daysQuiet >= 90) {
                return [
                    'level' => 'critical',
                    'label' => '90+ days quiet',
                    'detail' => "Had {$jobs} job(s) / {$reviews} review(s), then no productive activity for {$daysQuiet} days.",
                ];
            }
            if ($daysQuiet >= 60) {
                return [
                    'level' => 'high',
                    'label' => '60+ days quiet',
                    'detail' => "No job logged or review for {$daysQuiet} days — at risk of churn.",
                ];
            }

            return [
                'level' => 'critical',
                'label' => 'Never returned',
                'detail' => "No job logged or review for {$daysQuiet} days — treat as never returned.",
            ];
        }

        return [
            'level' => 'watch',
            'label' => 'Watch',
            'detail' => 'Quiet recently — keep an eye on productive activity.',
        ];
    }
}
