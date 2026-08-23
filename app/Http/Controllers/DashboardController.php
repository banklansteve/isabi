<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Review;
use App\Models\WorkLog;
use App\Support\NumberFormat;
use App\Support\Tokens\ReviewLinkGate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const ACTIVITY_LIMIT = 5;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $firstName = $user->first_name ?: str($user->name)->before(' ')->toString();
        $slug = $user->slug ?: 'artisan';
        $jobsChart = $this->jobsChart($user->id);
        $jobsCount = WorkLog::query()->where('user_id', $user->id)->count();
        $linkStatus = app(ReviewLinkGate::class)->status($user);

        $reviewStats = Review::query()
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $reviewsCount = (int) ($reviewStats->total ?? 0);

        $pendingReviewJob = WorkLog::query()
            ->where('user_id', $user->id)
            ->whereDoesntHave('review')
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->first();

        $dueReminders = WorkLog::query()
            ->where('user_id', $user->id)
            ->with('user')
            ->whereNotNull('review_requested_at')
            ->whereNull('review_reminder_sent_at')
            ->whereDoesntHave('review')
            ->orderBy('review_requested_at')
            ->limit(8)
            ->get()
            ->filter(fn (WorkLog $log) => $log->setRelation('user', $user)->reminderDue())
            ->values()
            ->map(fn (WorkLog $log) => [
                'uid' => $log->uid,
                'description' => $log->description,
                'client_name' => $log->client_name,
                'requested_label' => $log->review_requested_at
                    ?->timezone(config('app.display_timezone'))
                    ->diffForHumans(),
            ]);

        $activity = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (ActivityLog $log) => $log->toFeedItem())
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'greeting' => $this->greeting(),
            'firstName' => $firstName,
            'glance' => $this->glanceStats($linkStatus, $user, $jobsCount, $reviewsCount),
            'reputation' => $this->reputation($user, $linkStatus, $reviewStats, $reviewsCount),
            'page' => [
                'url' => $user->publicUrl() ?: url('/p/'.$slug),
                'slug' => $slug,
                'completion' => (int) ($user->profile_completion ?? 0),
                'business_name' => $user->displayBusinessName(),
                'trade' => $user->trade,
            ],
            'nudge' => $this->resolveNudge($user),
            'jobsChart' => $jobsChart,
            'activity' => $activity,
            'pendingReview' => $pendingReviewJob ? [
                'uid' => $pendingReviewJob->uid,
                'description' => $pendingReviewJob->description,
            ] : null,
            'dueReminders' => $dueReminders,
        ]);
    }

    /**
     * @return array{
     *     jobs: int,
     *     reviews: int,
     *     views: int,
     *     views_label: string,
     *     credits: int,
     *     plan: string,
     *     plan_detail: string
     * }
     */
    private function glanceStats(array $status, $user, int $jobsCount, int $reviewsCount): array
    {
        $views = (int) ($user->public_page_views ?? 0);

        $detail = $status['has_annual']
            ? 'Unlimited review links'
            : "{$status['free_remaining']} free left · {$status['token_balance']} tokens";

        return [
            'jobs' => $jobsCount,
            'reviews' => $reviewsCount,
            'views' => $views,
            'views_label' => NumberFormat::compact($views),
            'credits' => $status['token_balance'],
            'plan' => $status['plan'],
            'plan_detail' => $detail,
        ];
    }

    /**
     * Reputation snapshot: how the review loop is actually performing.
     */
    private function reputation($user, array $status, ?Review $stats, int $reviewsCount): array
    {
        $requestsSent = WorkLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('review_requested_at')
            ->count();

        $average = (float) ($stats->average ?? 0);

        $latest = Review::query()
            ->where('user_id', $user->id)
            ->with('workLog:id,uid,description')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();

        return [
            'average' => $average > 0 ? round($average, 1) : null,
            'count' => $reviewsCount,
            'requests_sent' => $requestsSent,
            'awaiting' => max(0, $requestsSent - $reviewsCount),
            'response_rate' => $requestsSent > 0
                ? (int) round(min($reviewsCount, $requestsSent) / $requestsSent * 100)
                : 0,
            'latest' => $latest ? [
                'rating' => (float) $latest->rating,
                'comment' => str($latest->comment ?? '')->squish()->limit(180)->toString(),
                'client' => $latest->client_display_name ?: 'A client',
                'job' => $latest->workLog?->description,
                'job_uid' => $latest->workLog?->uid,
                'time' => $latest->submitted_at
                    ?->timezone(config('app.display_timezone'))
                    ->diffForHumans(),
            ] : null,
            'links' => [
                'has_annual' => $status['has_annual'],
                'free_limit' => $status['free_limit'],
                'free_used' => $status['free_used'],
                'free_remaining' => $status['free_remaining'],
                'token_balance' => $status['token_balance'],
                'reset_label' => $this->linkResetLabel(),
            ],
        ];
    }

    private function linkResetLabel(): string
    {
        if ((string) config('pricing.free.review_link_reset', 'calendar_month') === 'rolling_30_days') {
            return 'Rolling 30-day window';
        }

        return 'Resets '.now()
            ->timezone(config('app.display_timezone'))
            ->startOfMonth()
            ->addMonthNoOverflow()
            ->format('j M');
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    private function jobsChart(int $userId): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(5);

        $counts = WorkLog::query()
            ->where('user_id', $userId)
            ->where('worked_on', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(worked_on, '%Y-%m') as month_key, COUNT(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        $months = [];
        $cursor = $start->copy();

        for ($i = 0; $i < 6; $i++) {
            $key = $cursor->format('Y-m');
            $months[] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'count' => (int) ($counts[$key] ?? 0),
            ];
            $cursor->addMonth();
        }

        return $months;
    }

    private function greeting(): string
    {
        $hour = (int) now()->timezone(config('app.display_timezone'))->format('G');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    /**
     * @return array{key: string, tone: string, icon: string, title: string, body: string, cta_label: string, cta_href: string}|null
     */
    private function resolveNudge($user): ?array
    {
        if (blank($user->whatsapp)) {
            return [
                'key' => 'whatsapp',
                'tone' => 'coral',
                'icon' => 'ti ti-brand-whatsapp',
                'title' => 'Add your WhatsApp number',
                'body' => 'Clients get review links on WhatsApp. Add your number so you can send them after jobs.',
                'cta_label' => 'Update account',
                'cta_href' => route('profile.edit'),
            ];
        }

        if (WorkLog::query()->where('user_id', $user->id)->doesntExist()) {
            return [
                'key' => 'first_job',
                'tone' => 'base',
                'icon' => 'ti ti-briefcase',
                'title' => 'You have unused credits',
                'body' => 'Your free plan includes review links. Log a finished job, then send one to a real client.',
                'cta_label' => 'Log a job',
                'cta_href' => route('work-log.create'),
            ];
        }

        $dueCount = WorkLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('review_requested_at')
            ->whereNull('review_reminder_sent_at')
            ->whereDoesntHave('review')
            ->get()
            ->filter(fn (WorkLog $log) => $log->setRelation('user', $user)->reminderDue())
            ->count();

        if ($dueCount > 0) {
            return [
                'key' => 'reminders',
                'tone' => 'coral',
                'icon' => 'ti ti-bell',
                'title' => $dueCount === 1
                    ? '1 client hasn’t reviewed yet'
                    : "{$dueCount} clients haven’t reviewed yet",
                'body' => 'Send a one-tap WhatsApp reminder — same flow as the first invite.',
                'cta_label' => 'Review due nudges',
                'cta_href' => route('work-log.index'),
            ];
        }

        return null;
    }
}
