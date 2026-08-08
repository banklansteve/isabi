<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Review;
use App\Models\WorkLog;
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
        $reviewsCount = Review::query()->where('user_id', $user->id)->count();

        $pendingReviewJob = WorkLog::query()
            ->where('user_id', $user->id)
            ->whereDoesntHave('review')
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->first();

        $activity = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (ActivityLog $log) => $log->toFeedItem())
            ->values()
            ->all();

        if ($activity === []) {
            $activity = [
                [
                    'id' => 0,
                    'icon' => 'ti ti-sparkles',
                    'title' => 'Welcome to Isabi',
                    'body' => 'Your account is ready. Log a job or share your page when you’re set.',
                    'time' => 'Just now',
                ],
            ];
        }

        return Inertia::render('Dashboard', [
            'greeting' => $this->greeting(),
            'firstName' => $firstName,
            'glance' => [
                'jobs' => $jobsCount,
                'reviews' => $reviewsCount,
                'credits' => 5,
                'plan' => 'Free',
                'plan_detail' => '5 review links / month',
            ],
            'page' => [
                'url' => $user->publicUrl() ?: url('/p/'.$slug),
                'slug' => $slug,
                'completion' => (int) ($user->profile_completion ?? 0),
            ],
            'nudge' => $this->resolveNudge($user),
            'jobsChart' => $jobsChart,
            'activity' => $activity,
            'pendingReview' => $pendingReviewJob ? [
                'uid' => $pendingReviewJob->uid,
                'description' => $pendingReviewJob->description,
            ] : null,
        ]);
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
        $hour = (int) now()->timezone(config('app.timezone'))->format('G');

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

        if ((int) ($user->profile_completion ?? 0) < 45) {
            return [
                'key' => 'profile',
                'tone' => 'base',
                'icon' => 'ti ti-user-circle',
                'title' => 'Finish setting up your profile',
                'body' => 'A complete page builds more trust. You’re at '.((int) $user->profile_completion).'% so far.',
                'cta_label' => 'Continue setup',
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

        return null;
    }
}


