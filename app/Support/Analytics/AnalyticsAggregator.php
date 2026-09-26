<?php

namespace App\Support\Analytics;

use App\Enums\UserRole;
use App\Models\AnalyticsDailyMetric;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsRetentionCohort;
use App\Models\AnalyticsUserActivityDay;
use App\Models\TokenPurchase;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AnalyticsAggregator
{
    public static function rawRetentionDays(): int
    {
        return max(30, min(60, (int) config('audit.analytics.raw_retention_days', 45)));
    }

    public const PAGE_ACTIONS = [
        'page.my_page' => 'page_my_page',
        'page.work_log' => 'page_work_log',
        'page.credits' => 'page_credits',
        'page.help' => 'page_help',
        'page.help_chat' => 'page_help_chat',
        'page.referrals' => 'page_referrals',
    ];

    /**
     * Aggregate one calendar day (app timezone) from raw analytics_events (+ purchases).
     */
    public function aggregateDay(CarbonInterface $day): AnalyticsDailyMetric
    {
        $day = Carbon::parse($day->toDateString(), config('app.timezone'))->startOfDay();
        $from = $day->copy();
        $to = $day->copy()->endOfDay();

        $this->ingestLoginDays($from, $to);
        $pageCounts = $this->countActions($from, $to);
        $uniqueByAction = $this->uniqueUsersByAction($from, $to);

        $dau = AnalyticsUserActivityDay::query()
            ->whereDate('activity_date', $day->toDateString())
            ->count();

        $wau = AnalyticsUserActivityDay::query()
            ->whereBetween('activity_date', [
                $day->copy()->subDays(6)->toDateString(),
                $day->toDateString(),
            ])
            ->distinct('user_id')
            ->count('user_id');

        $mau = AnalyticsUserActivityDay::query()
            ->whereBetween('activity_date', [
                $day->copy()->subDays(29)->toDateString(),
                $day->toDateString(),
            ])
            ->distinct('user_id')
            ->count('user_id');

        $freq = $this->loginFrequencyBuckets($day);
        $active30 = array_sum([
            $freq['login_freq_1_3'],
            $freq['login_freq_4_10'],
            $freq['login_freq_11_plus'],
        ]);

        $purchaseUsers = TokenPurchase::query()
            ->where('status', TokenPurchase::STATUS_COMPLETED)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('paid_at')->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->distinct('user_id')
            ->count('user_id');

        return AnalyticsDailyMetric::query()->updateOrCreate(
            ['metric_date' => $day->toDateString()],
            [
                'dau' => $dau,
                'wau' => $wau,
                'mau' => $mau,
                'page_my_page' => $pageCounts['page_my_page'] ?? 0,
                'page_work_log' => $pageCounts['page_work_log'] ?? 0,
                'page_credits' => $pageCounts['page_credits'] ?? 0,
                'page_help' => $pageCounts['page_help'] ?? 0,
                'page_help_chat' => $pageCounts['page_help_chat'] ?? 0,
                'page_referrals' => $pageCounts['page_referrals'] ?? 0,
                'page_credits_users' => $uniqueByAction['page.credits'] ?? 0,
                'purchase_users' => $purchaseUsers,
                'referral_signups' => $pageCounts['referral_signups'] ?? 0,
                'export_users' => $this->uniqueUsersInWindow($day, 'work_log.exported', 30),
                'review_messages_users' => $this->uniqueUsersInWindow($day, 'profile.review_messages_updated', 30),
                'login_freq_0' => $freq['login_freq_0'],
                'login_freq_1_3' => $freq['login_freq_1_3'],
                'login_freq_4_10' => $freq['login_freq_4_10'],
                'login_freq_11_plus' => $freq['login_freq_11_plus'],
                'active_users_30d' => $active30,
            ],
        );
    }

    public function recomputeRetentionCohorts(int $months = 12): void
    {
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $ids = User::query()
                ->where('role', UserRole::User)
                ->whereBetween('created_at', [$start, $end])
                ->pluck('id');

            $signedUp = $ids->count();
            $active30 = $this->cohortRetained($ids, $start, 30);
            $active60 = $this->cohortRetained($ids, $start, 60);
            $active90 = $this->cohortRetained($ids, $start, 90);

            AnalyticsRetentionCohort::query()->updateOrCreate(
                ['cohort_month' => $start->toDateString()],
                [
                    'signed_up' => $signedUp,
                    'active_30' => $active30,
                    'active_60' => $active60,
                    'active_90' => $active90,
                    'rate_30' => $signedUp ? (int) round(($active30 / $signedUp) * 100) : 0,
                    'rate_60' => $signedUp ? (int) round(($active60 / $signedUp) * 100) : 0,
                    'rate_90' => $signedUp ? (int) round(($active90 / $signedUp) * 100) : 0,
                ],
            );
        }
    }

    public function pruneRawEvents(?int $keepDays = null): int
    {
        $keepDays ??= self::rawRetentionDays();
        $cutoff = now()->subDays($keepDays)->startOfDay();

        return AnalyticsEvent::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    public function backfill(int $days = 90): void
    {
        for ($i = $days - 1; $i >= 0; $i--) {
            $this->aggregateDay(now()->subDays($i));
        }

        $this->recomputeRetentionCohorts(12);
    }

    private function ingestLoginDays(Carbon $from, Carbon $to): void
    {
        $rows = AnalyticsEvent::query()
            ->where('action', 'auth.login')
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->select('user_id', DB::raw('DATE(created_at) as activity_date'))
            ->groupBy('user_id', DB::raw('DATE(created_at)'))
            ->get();

        $now = now();

        foreach ($rows as $row) {
            AnalyticsUserActivityDay::query()->firstOrCreate(
                [
                    'activity_date' => $row->activity_date,
                    'user_id' => $row->user_id,
                ],
                ['created_at' => $now],
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function countActions(Carbon $from, Carbon $to): array
    {
        $actions = [
            ...array_keys(self::PAGE_ACTIONS),
            'referral.signed_up',
        ];

        $rows = AnalyticsEvent::query()
            ->whereIn('action', $actions)
            ->whereBetween('created_at', [$from, $to])
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->pluck('total', 'action');

        $out = [];

        foreach (self::PAGE_ACTIONS as $action => $column) {
            $out[$column] = (int) ($rows[$action] ?? 0);
        }

        $out['referral_signups'] = (int) ($rows['referral.signed_up'] ?? 0);

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function uniqueUsersByAction(Carbon $from, Carbon $to): array
    {
        $actions = [
            'page.credits',
            'work_log.exported',
            'profile.review_messages_updated',
            'referral.signed_up',
        ];

        $rows = AnalyticsEvent::query()
            ->whereIn('action', $actions)
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->select('action', DB::raw('COUNT(DISTINCT user_id) as total'))
            ->groupBy('action')
            ->pluck('total', 'action');

        $out = [];
        foreach ($actions as $action) {
            $out[$action] = (int) ($rows[$action] ?? 0);
        }

        return $out;
    }

    private function uniqueUsersInWindow(Carbon $asOf, string $action, int $days): int
    {
        $from = $asOf->copy()->subDays($days - 1)->startOfDay();
        $to = $asOf->copy()->endOfDay();

        return (int) AnalyticsEvent::query()
            ->where('action', $action)
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * @return array{login_freq_0: int, login_freq_1_3: int, login_freq_4_10: int, login_freq_11_plus: int}
     */
    private function loginFrequencyBuckets(Carbon $asOf): array
    {
        $from = $asOf->copy()->subDays(29)->toDateString();
        $to = $asOf->toDateString();

        $artisanIds = User::query()
            ->where('role', UserRole::User)
            ->where('created_at', '<=', $asOf->copy()->endOfDay())
            ->pluck('id');

        if ($artisanIds->isEmpty()) {
            return [
                'login_freq_0' => 0,
                'login_freq_1_3' => 0,
                'login_freq_4_10' => 0,
                'login_freq_11_plus' => 0,
            ];
        }

        $counts = AnalyticsUserActivityDay::query()
            ->whereIn('user_id', $artisanIds)
            ->whereBetween('activity_date', [$from, $to])
            ->select('user_id', DB::raw('COUNT(*) as days'))
            ->groupBy('user_id')
            ->pluck('days', 'user_id');

        $buckets = [
            'login_freq_0' => 0,
            'login_freq_1_3' => 0,
            'login_freq_4_10' => 0,
            'login_freq_11_plus' => 0,
        ];

        foreach ($artisanIds as $id) {
            $n = (int) ($counts[$id] ?? 0);
            if ($n === 0) {
                $buckets['login_freq_0']++;
            } elseif ($n <= 3) {
                $buckets['login_freq_1_3']++;
            } elseif ($n <= 10) {
                $buckets['login_freq_4_10']++;
            } else {
                $buckets['login_freq_11_plus']++;
            }
        }

        return $buckets;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $userIds
     */
    private function cohortRetained($userIds, Carbon $cohortStart, int $days): int
    {
        if ($userIds->isEmpty()) {
            return 0;
        }

        $until = $cohortStart->copy()->addDays($days)->toDateString();
        $from = $cohortStart->toDateString();

        return (int) AnalyticsUserActivityDay::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('activity_date', [$from, $until])
            ->distinct('user_id')
            ->count('user_id');
    }
}
