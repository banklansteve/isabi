<?php

namespace App\Support\Analytics;

use App\Models\AnalyticsDailyMetric;
use App\Models\AnalyticsRetentionCohort;
use Carbon\Carbon;

class ProductAnalyticsReports
{
    /**
     * Full payload for Admin Analytics (product-usage tabs). Reads summary tables only.
     *
     * @return array<string, mixed>
     */
    public function forAnalyticsPage(): array
    {
        $days = 366;
        $metrics = AnalyticsDailyMetric::query()
            ->where('metric_date', '>=', now()->subDays($days - 1)->toDateString())
            ->orderBy('metric_date')
            ->get();

        $latest = $metrics->last();

        return [
            'active_daily' => $this->seriesFrom($metrics, 'dau'),
            'active_users' => $this->seriesFrom($metrics, 'wau', weekly: true),
            'monthly_active' => $this->seriesFrom($metrics, 'mau', monthly: true),
            'active_kpi' => $this->activeKpis($metrics),
            'login_frequency' => $this->loginFrequency($latest),
            'retention' => $this->retentionRows(),
            'page_popularity' => $this->pagePopularity($metrics),
            'feature_usage' => $this->featureUsage($metrics),
            'credits_conversion' => $this->creditsConversion($metrics),
            'referral_signups_trend' => $this->seriesFrom($metrics, 'referral_signups'),
            'has_summary_data' => $metrics->isNotEmpty(),
        ];
    }

    /**
     * Credits conversion block for Financials (summary tables only).
     *
     * @return array<string, mixed>
     */
    public function creditsConversionForFinancials(): array
    {
        $metrics = AnalyticsDailyMetric::query()
            ->where('metric_date', '>=', now()->subDays(365)->toDateString())
            ->orderBy('metric_date')
            ->get();

        return $this->creditsConversion($metrics);
    }

    /**
     * Referral signup series for the referrals admin page.
     *
     * @return list<array{label: string, date: string, value: int}>
     */
    public function referralSignupsTrend(int $days = 366): array
    {
        $metrics = AnalyticsDailyMetric::query()
            ->where('metric_date', '>=', now()->subDays($days - 1)->toDateString())
            ->orderBy('metric_date')
            ->get();

        return $this->seriesFrom($metrics, 'referral_signups');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AnalyticsDailyMetric>  $metrics
     * @return list<array{label: string, date: string, value: int}>
     */
    private function seriesFrom($metrics, string $column, bool $weekly = false, bool $monthly = false): array
    {
        if ($weekly) {
            return $metrics
                ->groupBy(fn (AnalyticsDailyMetric $row) => $row->metric_date->copy()->startOfWeek()->toDateString())
                ->map(function ($group, $weekStart) {
                    $start = Carbon::parse($weekStart);
                    $last = $group->last();

                    return [
                        'label' => $start->format('j M'),
                        'date' => $start->toDateString(),
                        'value' => (int) ($last?->wau ?? 0),
                    ];
                })
                ->values()
                ->all();
        }

        if ($monthly) {
            return $metrics
                ->groupBy(fn (AnalyticsDailyMetric $row) => $row->metric_date->format('Y-m'))
                ->map(function ($group, $key) {
                    $start = Carbon::parse($key.'-01');
                    $last = $group->last();

                    return [
                        'label' => $start->format('M Y'),
                        'date' => $start->toDateString(),
                        'value' => (int) ($last?->mau ?? 0),
                    ];
                })
                ->values()
                ->all();
        }

        return $metrics->map(fn (AnalyticsDailyMetric $row) => [
            'label' => $row->metric_date->format('j M'),
            'date' => $row->metric_date->toDateString(),
            'value' => (int) $row->{$column},
        ])->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AnalyticsDailyMetric>  $metrics
     * @return array{dau: array, wau: array, mau: array}
     */
    private function activeKpis($metrics): array
    {
        $latest = $metrics->last();
        $prev = $metrics->count() >= 2
            ? $metrics->slice(-31, 1)->first() ?? $metrics->get($metrics->count() - 2)
            : null;

        // Compare latest day vs same offset ~30 days earlier when possible.
        $compare = $metrics->first(fn (AnalyticsDailyMetric $row) => $latest
            && $row->metric_date->equalTo($latest->metric_date->copy()->subDays(30)));

        return [
            'dau' => $this->kpiCard('Daily active', $latest?->dau ?? 0, $compare?->dau ?? $prev?->dau),
            'wau' => $this->kpiCard('Weekly active', $latest?->wau ?? 0, $compare?->wau ?? $prev?->wau),
            'mau' => $this->kpiCard('Monthly active', $latest?->mau ?? 0, $compare?->mau ?? $prev?->mau),
        ];
    }

    /**
     * @return array{label: string, value: string, delta: array{label: string, tone: string}|null}
     */
    private function kpiCard(string $label, int $current, ?int $previous): array
    {
        $delta = null;
        if ($previous !== null && $previous > 0) {
            $pct = (int) round((($current - $previous) / $previous) * 100);
            $prefix = $pct > 0 ? '+' : '';
            $delta = [
                'label' => $prefix.$pct.'% vs prior period',
                'tone' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'neutral'),
            ];
        } elseif ($previous !== null && $previous === 0 && $current > 0) {
            $delta = ['label' => 'New activity', 'tone' => 'up'];
        }

        return [
            'label' => $label,
            'value' => number_format($current),
            'delta' => $delta,
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function loginFrequency(?AnalyticsDailyMetric $latest): array
    {
        if (! $latest) {
            return [
                ['label' => '0 logins', 'value' => 0],
                ['label' => '1–3', 'value' => 0],
                ['label' => '4–10', 'value' => 0],
                ['label' => '11+', 'value' => 0],
            ];
        }

        return [
            ['label' => '0 logins', 'value' => (int) $latest->login_freq_0],
            ['label' => '1–3', 'value' => (int) $latest->login_freq_1_3],
            ['label' => '4–10', 'value' => (int) $latest->login_freq_4_10],
            ['label' => '11+', 'value' => (int) $latest->login_freq_11_plus],
        ];
    }

    /**
     * @return list<array{month: string, signed_up: int, active_30: int, active_60: int, active_90: int, rate_30: int, rate_60: int, rate_90: int}>
     */
    private function retentionRows(): array
    {
        return AnalyticsRetentionCohort::query()
            ->orderByDesc('cohort_month')
            ->limit(12)
            ->get()
            ->sortBy('cohort_month')
            ->values()
            ->map(fn (AnalyticsRetentionCohort $row) => [
                'month' => $row->cohort_month->format('M Y'),
                'signed_up' => (int) $row->signed_up,
                'active_30' => (int) $row->active_30,
                'active_60' => (int) $row->active_60,
                'active_90' => (int) $row->active_90,
                'rate_30' => (int) $row->rate_30,
                'rate_60' => (int) $row->rate_60,
                'rate_90' => (int) $row->rate_90,
            ])
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AnalyticsDailyMetric>  $metrics
     * @return array{items: list<array{label: string, value: int, key: string}>, lowest_key: string|null, series: array<string, list>}
     */
    private function pagePopularity($metrics): array
    {
        $map = [
            'page_my_page' => ['label' => 'My page', 'key' => 'my_page'],
            'page_work_log' => ['label' => 'Work log', 'key' => 'work_log'],
            'page_credits' => ['label' => 'Credits', 'key' => 'credits'],
            'page_help' => ['label' => 'Help', 'key' => 'help'],
            'page_help_chat' => ['label' => 'Help chat', 'key' => 'help_chat'],
            'page_referrals' => ['label' => 'Referrals', 'key' => 'referrals'],
        ];

        $items = [];
        foreach ($map as $column => $meta) {
            $items[] = [
                'label' => $meta['label'],
                'key' => $meta['key'],
                'value' => (int) $metrics->sum($column),
            ];
        }

        usort($items, fn ($a, $b) => $b['value'] <=> $a['value']);

        $lowest = collect($items)->sortBy('value')->first();

        return [
            'items' => $items,
            'lowest_key' => $lowest['key'] ?? null,
            'series' => collect($map)->mapWithKeys(fn ($meta, $column) => [
                $meta['key'] => $this->seriesFrom($metrics, $column),
            ])->all(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AnalyticsDailyMetric>  $metrics
     * @return array{export: array, review_messages: array}
     */
    private function featureUsage($metrics): array
    {
        $window = $metrics->filter(fn (AnalyticsDailyMetric $row) => $row->metric_date->gte(now()->subDays(29)->startOfDay()));
        $prev = $metrics->filter(fn (AnalyticsDailyMetric $row) => $row->metric_date->between(
            now()->subDays(59)->startOfDay(),
            now()->subDays(30)->endOfDay(),
        ));

        $active = (int) ($window->last()?->active_users_30d ?? 0);
        $prevActive = (int) ($prev->last()?->active_users_30d ?? 0);

        // Trailing-window uniques are stored on each daily row at aggregation time.
        $exportUsers = (int) ($window->last()?->export_users ?? 0);
        $prevExport = (int) ($prev->last()?->export_users ?? 0);
        $reviewUsers = (int) ($window->last()?->review_messages_users ?? 0);
        $prevReview = (int) ($prev->last()?->review_messages_users ?? 0);

        return [
            'export' => $this->usageCard(
                'Work log export',
                'Share of recently active artisans who exported their work log — useful signal for whether PDF export is worth more investment.',
                $exportUsers,
                $active,
                $prevExport,
                $prevActive,
            ),
            'review_messages' => $this->usageCard(
                'Review message customization',
                'Share of active artisans who edited WhatsApp review copy — quietly unused features may not need more polish yet.',
                $reviewUsers,
                $active,
                $prevReview,
                $prevActive,
            ),
        ];
    }

    /**
     * @return array{label: string, value: string, count: int, active: int, hint: string, delta: array|null}
     */
    private function usageCard(
        string $label,
        string $hint,
        int $users,
        int $active,
        int $prevUsers,
        int $prevActive,
    ): array {
        $rate = $active > 0 ? (int) round(($users / $active) * 100) : 0;
        $prevRate = $prevActive > 0 ? (int) round(($prevUsers / $prevActive) * 100) : null;
        $delta = null;
        if ($prevRate !== null) {
            $diff = $rate - $prevRate;
            $prefix = $diff > 0 ? '+' : '';
            $delta = [
                'label' => $prefix.$diff.' pts vs prior 30d',
                'tone' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral'),
            ];
        }

        return [
            'label' => $label,
            'hint' => $hint,
            'value' => $rate.'%',
            'count' => $users,
            'active' => $active,
            'delta' => $delta,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AnalyticsDailyMetric>  $metrics
     * @return array{rate: int, viewers: int, purchasers: int, funnel: list, weekly_rate: list}
     */
    private function creditsConversion($metrics): array
    {
        $window = $metrics->filter(fn (AnalyticsDailyMetric $row) => $row->metric_date->gte(now()->subDays(29)->startOfDay()));
        $viewers = (int) $window->sum('page_credits_users');
        $purchasers = (int) $window->sum('purchase_users');
        $rate = $viewers > 0 ? (int) round(($purchasers / $viewers) * 100) : 0;

        $weekly = $metrics
            ->groupBy(fn (AnalyticsDailyMetric $row) => $row->metric_date->copy()->startOfWeek()->toDateString())
            ->map(function ($group, $weekStart) {
                $v = (int) $group->sum('page_credits_users');
                $p = (int) $group->sum('purchase_users');

                return [
                    'label' => Carbon::parse($weekStart)->format('j M'),
                    'date' => $weekStart,
                    'value' => $v > 0 ? (int) round(($p / $v) * 100) : 0,
                    'viewers' => $v,
                    'purchasers' => $p,
                ];
            })
            ->values()
            ->all();

        return [
            'rate' => $rate,
            'viewers' => $viewers,
            'purchasers' => $purchasers,
            'funnel' => [
                [
                    'label' => 'Viewed credits',
                    'value' => $viewers,
                    'percent' => $viewers > 0 ? 100 : 0,
                ],
                [
                    'label' => 'Purchased tokens',
                    'value' => $purchasers,
                    'percent' => $viewers > 0 ? (int) round(($purchasers / $viewers) * 100) : 0,
                ],
            ],
            'viewers_series' => $this->seriesFrom($metrics, 'page_credits_users'),
            'purchasers_series' => $this->seriesFrom($metrics, 'purchase_users'),
            'weekly_rate' => $weekly,
            'hint' => 'Credits page viewers who completed a token purchase in the same window (from daily summaries).',
        ];
    }
}
