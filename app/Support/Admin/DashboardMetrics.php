<?php

namespace App\Support\Admin;

use App\Models\Referral;
use App\Models\Review;
use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\NumberFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $artisans = User::query()->artisans();

        $usersNow = (clone $artisans)->count();
        $usersThisWeek = (clone $artisans)->where('created_at', '>=', now()->startOfWeek())->count();

        $revenueThisMonth = $this->completedRevenueBetween(now()->startOfMonth(), now());
        $revenueLastMonth = $this->completedRevenueBetween(
            now()->subMonth()->startOfMonth(),
            now()->startOfMonth(),
        );

        $jobsToday = WorkLog::query()->whereDate('created_at', now()->toDateString())->count();
        $jobsThisWeek = WorkLog::query()->where('created_at', '>=', now()->startOfWeek())->count();
        $jobsLastWeek = WorkLog::query()
            ->where('created_at', '>=', now()->subWeek()->startOfWeek())
            ->where('created_at', '<', now()->startOfWeek())
            ->count();

        $reviewsTotal = Review::query()->count();
        $reviewsThisWeek = Review::query()->where('submitted_at', '>=', now()->startOfWeek())->count();
        $reviewsLastWeek = Review::query()
            ->where('submitted_at', '>=', now()->subWeek()->startOfWeek())
            ->where('submitted_at', '<', now()->startOfWeek())
            ->count();

        $planMix = $this->planMix();

        return [
            'kpis' => [
                [
                    'key' => 'users',
                    'label' => 'Total users',
                    'value' => number_format($usersNow),
                    'delta' => $usersThisWeek > 0
                        ? [
                            'value' => $usersThisWeek,
                            'label' => '+'.number_format($usersThisWeek).' this week',
                            'tone' => 'up',
                        ]
                        : null,
                ],
                [
                    'key' => 'revenue',
                    'label' => 'Revenue (month)',
                    'value' => NumberFormat::naira($revenueThisMonth),
                    'delta' => NumberFormat::percentDelta($revenueThisMonth, $revenueLastMonth),
                    'delta_suffix' => 'vs last month',
                ],
                [
                    'key' => 'jobs',
                    'label' => 'Jobs logged today',
                    'value' => number_format($jobsToday),
                    'delta' => $this->absoluteDelta($jobsThisWeek, $jobsLastWeek, 'this week'),
                ],
                [
                    'key' => 'reviews',
                    'label' => 'Reviews received',
                    'value' => number_format($reviewsTotal),
                    'delta' => $this->absoluteDelta($reviewsThisWeek, $reviewsLastWeek, 'this week'),
                ],
            ],
            'revenue' => $this->monthlySeries(
                TokenPurchase::query()->where('status', TokenPurchase::STATUS_COMPLETED),
                'paid_at',
                'price',
                6,
            ),
            'revenue_daily' => $this->revenueDaily(366),
            'signups_daily' => $this->datedDaily(User::query()->artisans(), 'created_at', 366),
            'plans' => $planMix,
            'funnel' => $this->signupFunnel(),
            'active_users' => $this->weeklyActiveUsers(26),
            'monthly_active' => $this->monthlyActiveUsers(18),
            'active_daily' => $this->activeDaily(366),
            'currency_symbol' => (string) config('pricing.currency_symbol', '₦'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function analytics(): array
    {
        $byState = User::query()
            ->artisans()
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->select('state', DB::raw('COUNT(*) as total'))
            ->groupBy('state')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->state,
                'value' => (int) $row->total,
            ])
            ->all();

        $byTrade = User::query()
            ->artisans()
            ->whereNotNull('trade')
            ->where('trade', '!=', '')
            ->select('trade', DB::raw('COUNT(*) as total'))
            ->groupBy('trade')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->trade,
                'value' => (int) $row->total,
            ])
            ->all();

        $byCity = User::query()
            ->artisans()
            ->whereNotNull('lga')
            ->where('lga', '!=', '')
            ->select('lga', 'state', DB::raw('COUNT(*) as total'))
            ->groupBy('lga', 'state')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->lga.($row->state ? ', '.$row->state : ''),
                'value' => (int) $row->total,
            ])
            ->all();

        return [
            'user_growth' => $this->monthlySeries(User::query()->artisans(), 'created_at', null, 12),
            'jobs_trend' => $this->monthlySeries(WorkLog::query(), 'created_at', null, 12),
            'daily_signups' => $this->datedDaily(User::query()->artisans(), 'created_at', 366),
            'cumulative_users' => $this->cumulativeUsers(12),
            'acquisition' => $this->acquisitionMix(),
            'active_users' => $this->weeklyActiveUsers(26),
            'monthly_active' => $this->monthlyActiveUsers(18),
            'active_daily' => $this->activeDaily(366),
            'jobs_per_active' => $this->jobsPerActiveUser(26),
            'geography' => $byState,
            'cities' => $byCity,
            'trades' => $byTrade,
            'retention' => $this->retentionCohorts(),
            'funnel' => $this->signupFunnel(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function financials(): array
    {
        $completed = TokenPurchase::query()->where('status', TokenPurchase::STATUS_COMPLETED);

        $byProcessor = (clone $completed)
            ->select(DB::raw("COALESCE(processor, 'unspecified') as processor"), DB::raw('SUM(price) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('processor')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $this->processorLabel((string) $row->processor),
                'value' => (int) $row->total,
                'count' => (int) $row->count,
            ])
            ->all();

        $byPack = (clone $completed)
            ->select('pack_name', 'pack_key', DB::raw('SUM(price) as total'), DB::raw('SUM(tokens) as tokens'), DB::raw('COUNT(*) as count'))
            ->groupBy('pack_name', 'pack_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->pack_name,
                'key' => $row->pack_key,
                'value' => (int) $row->total,
                'tokens' => (int) $row->tokens,
                'count' => (int) $row->count,
            ])
            ->all();

        $annualKeys = ['annual', 'annual_unlock'];
        $recurring = (clone $completed)->whereIn('pack_key', $annualKeys)->sum('price');
        $oneOff = (clone $completed)->whereNotIn('pack_key', $annualKeys)->sum('price');

        return [
            'revenue' => $this->monthlySeries($completed, 'paid_at', 'price', 12),
            'revenue_daily' => $this->revenueDaily(366),
            'by_processor' => $byProcessor,
            'by_pack' => $byPack,
            'split' => [
                ['label' => 'One-off packs', 'value' => (int) $oneOff, 'color' => '#2F6FED'],
                ['label' => 'Annual unlock', 'value' => (int) $recurring, 'color' => '#0F9F6E'],
            ],
            'totals' => [
                'all_time' => NumberFormat::naira((int) (clone $completed)->sum('price'), false),
                'this_month' => NumberFormat::naira($this->completedRevenueBetween(now()->startOfMonth(), now()), false),
                'orders' => number_format((clone $completed)->count()),
                'renewal_rate' => $this->annualRenewalRate(),
            ],
            'revenue_by_source' => $this->revenueBySource(18),
            'referral_liability' => $this->referralLiability(18),
            'currency_symbol' => (string) config('pricing.currency_symbol', '₦'),
        ];
    }

    /**
     * @return list<array{month: string, signed_up: int, active_30: int, active_60: int, active_90: int, rate_30: int, rate_60: int, rate_90: int}>
     */
    public function retentionCohorts(): array
    {
        $rows = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $ids = User::query()
                ->artisans()
                ->whereBetween('created_at', [$start, $end])
                ->pluck('id');

            $signedUp = $ids->count();
            $active30 = $this->activeAfter($ids, $start, 30);
            $active60 = $this->activeAfter($ids, $start, 60);
            $active90 = $this->activeAfter($ids, $start, 90);

            $rows[] = [
                'month' => $start->format('M Y'),
                'signed_up' => $signedUp,
                'active_30' => $active30,
                'active_60' => $active60,
                'active_90' => $active90,
                'rate_30' => $signedUp ? (int) round(($active30 / $signedUp) * 100) : 0,
                'rate_60' => $signedUp ? (int) round(($active60 / $signedUp) * 100) : 0,
                'rate_90' => $signedUp ? (int) round(($active90 / $signedUp) * 100) : 0,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, requested: int, completed: int, rate: int}>
     */
    public function reviewCompletionSeries(): array
    {
        $rows = [];

        for ($i = 17; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $requested = WorkLog::query()
                ->whereNotNull('review_requested_at')
                ->whereBetween('review_requested_at', [$start, $end])
                ->count();

            $completed = WorkLog::query()
                ->whereNotNull('review_requested_at')
                ->whereBetween('review_requested_at', [$start, $end])
                ->whereHas('review')
                ->count();

            $rows[] = [
                'label' => $start->format('M Y'),
                'date' => $start->toDateString(),
                'requested' => $requested,
                'completed' => $completed,
                'rate' => $requested ? (int) round(($completed / $requested) * 100) : 0,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function reviewInsights(): array
    {
        $buckets = [];
        for ($star = 1; $star <= 5; $star++) {
            $buckets[] = [
                'label' => (string) $star,
                'value' => Review::query()->where('rating', '>=', $star)->where('rating', '<', $star + 1)->count(),
            ];
        }

        $flagged = [];
        for ($i = 17; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $flagged[] = [
                'label' => $start->format('M Y'),
                'date' => $start->toDateString(),
                'value' => Review::query()->whereNotNull('flagged_at')->whereBetween('flagged_at', [$start, $end])->count(),
            ];
        }

        return [
            'completion' => $this->reviewCompletionSeries(),
            'ratings' => $buckets,
            'flagged' => $flagged,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function referralInsights(): array
    {
        $trend = $this->monthlySeries(Referral::query(), 'created_at', null, 18);
        $referredIds = Referral::query()->pluck('referred_user_id');
        $referred = $referredIds->count();
        $active = $referred
            ? (int) WorkLog::query()->whereIn('user_id', $referredIds)->selectRaw('COUNT(DISTINCT user_id) as aggregate')->value('aggregate')
            : 0;

        return [
            'trend' => $trend,
            'conversion_rate' => $referred ? (int) round(($active / $referred) * 100) : 0,
            'referred' => $referred,
            'active' => $active,
        ];
    }

    /**
     * @return list<array{label: string, value: int, percent: int}>
     */
    public function signupFunnel(): array
    {
        $signedUp = User::query()->artisans()->count();
        $verified = User::query()->artisans()->whereNotNull('email_verified_at')->count();
        $profile = User::query()->artisans()->where('profile_completion', '>=', 80)->count();
        $firstJob = User::query()->artisans()->whereHas('workLogs')->count();
        $firstRequest = User::query()->artisans()->whereHas('workLogs', fn ($q) => $q->whereNotNull('review_requested_at'))->count();
        $firstReview = User::query()->artisans()->whereHas('reviews')->count();

        $steps = [
            ['label' => 'Signed up', 'value' => $signedUp],
            ['label' => 'Verified', 'value' => $verified],
            ['label' => 'Profile completed', 'value' => $profile],
            ['label' => 'First job logged', 'value' => $firstJob],
            ['label' => 'First review request sent', 'value' => $firstRequest],
            ['label' => 'First review received', 'value' => $firstReview],
        ];

        return collect($steps)
            ->map(function (array $step) use ($signedUp) {
                $step['percent'] = $signedUp > 0 ? (int) round(($step['value'] / $signedUp) * 100) : 0;

                return $step;
            })
            ->all();
    }

    /**
     * @return list<array{label: string, value: int, color: string, percent: int}>
     */
    public function acquisitionMix(): array
    {
        $referral = User::query()->artisans()->whereNotNull('referred_by_user_id')->count();
        $total = User::query()->artisans()->count();
        $organic = max(0, $total - $referral);

        $items = [
            ['label' => 'Organic', 'value' => $organic, 'color' => '#2F6FED'],
            ['label' => 'Referral', 'value' => $referral, 'color' => '#0F9F6E'],
        ];

        return collect($items)
            ->map(function (array $item) use ($total) {
                $item['percent'] = $total > 0 ? (int) round(($item['value'] / $total) * 100) : 0;

                return $item;
            })
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function weeklyActiveUsers(int $weeks): array
    {
        $start = now()->subWeeks($weeks - 1)->startOfWeek();
        $expr = $this->weekExpression('created_at');

        $rows = WorkLog::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$expr} as week_key, COUNT(DISTINCT user_id) as total")
            ->groupBy('week_key')
            ->pluck('total', 'week_key');

        $series = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $week = now()->subWeeks($i)->startOfWeek();
            $key = $week->format('o-W');
            $series[] = [
                'label' => $week->format('j M'),
                'date' => $week->toDateString(),
                'value' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return list<array{label: string, date: string, value: int}>
     */
    public function monthlyActiveUsers(int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $expr = $this->monthExpression('created_at');

        $rows = WorkLog::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$expr} as month_key, COUNT(DISTINCT user_id) as total")
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $series[] = [
                'label' => $month->format('M Y'),
                'date' => $month->toDateString(),
                'value' => (int) ($rows[$month->format('Y-m')] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return list<array{date: string, label: string, value: int}>
     */
    public function activeDaily(int $days): array
    {
        return $this->datedDailyDistinct(WorkLog::query(), 'created_at', 'user_id', $days);
    }

    /**
     * @return list<array{date: string, label: string, value: int}>
     */
    public function revenueDaily(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $expr = $this->dayExpression('COALESCE(paid_at, created_at)');

        $rows = TokenPurchase::query()
            ->where('status', TokenPurchase::STATUS_COMPLETED)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$start])
            ->selectRaw("{$expr} as day_key, SUM(price) as total")
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $series[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'value' => (int) ($rows[$day->toDateString()] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @param  Builder<Model>  $query
     * @return list<array{date: string, label: string, value: int}>
     */
    public function datedDaily($query, string $dateColumn, int $days): array
    {
        return $this->dailySeries($query, $dateColumn, $days);
    }

    /**
     * @param  Builder<Model>  $query
     * @return list<array{date: string, label: string, value: int}>
     */
    private function datedDailyDistinct($query, string $dateColumn, string $distinctColumn, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $expr = $this->dayExpression($dateColumn);

        $rows = (clone $query)
            ->where($dateColumn, '>=', $start)
            ->selectRaw("{$expr} as day_key, COUNT(DISTINCT {$distinctColumn}) as total")
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $series[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'value' => (int) ($rows[$day->toDateString()] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return list<array{label: string, value: float}>
     */
    public function jobsPerActiveUser(int $weeks): array
    {
        $series = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = (clone $start)->endOfWeek();
            $jobs = WorkLog::query()->whereBetween('created_at', [$start, $end])->count();
            $active = (int) WorkLog::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('COUNT(DISTINCT user_id) as aggregate')
                ->value('aggregate');
            $series[] = [
                'label' => $start->format('j M'),
                'date' => $start->toDateString(),
                'value' => $active ? round($jobs / $active, 1) : 0,
            ];
        }

        return $series;
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function cumulativeUsers(int $months): array
    {
        $monthly = $this->monthlySeries(User::query()->artisans(), 'created_at', null, $months);
        $before = User::query()
            ->artisans()
            ->where('created_at', '<', now()->subMonths($months - 1)->startOfMonth())
            ->count();

        $running = $before;
        foreach ($monthly as &$point) {
            $running += $point['value'];
            $point['value'] = $running;
        }

        return $monthly;
    }

    /**
     * @return array{labels: list<string>, dates: list<string>, layers: list<array{key: string, label: string, color: string, values: list<int>}>}
     */
    public function revenueBySource(int $months): array
    {
        $annualKeys = ['annual', 'annual_unlock'];
        $labels = [];
        $packs = [];
        $annual = [];

        $dates = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $labels[] = $start->format('M Y');
            $dates[] = $start->toDateString();

            $completed = TokenPurchase::query()
                ->where('status', TokenPurchase::STATUS_COMPLETED)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('paid_at', [$start, $end])
                        ->orWhere(function ($inner) use ($start, $end) {
                            $inner->whereNull('paid_at')->whereBetween('created_at', [$start, $end]);
                        });
                });

            $annual[] = (int) (clone $completed)->whereIn('pack_key', $annualKeys)->sum('price');
            $packs[] = (int) (clone $completed)->whereNotIn('pack_key', $annualKeys)->sum('price');
        }

        return [
            'labels' => $labels,
            'dates' => $dates,
            'layers' => [
                ['key' => 'packs', 'label' => 'Credit packs', 'color' => '#2F6FED', 'values' => $packs],
                ['key' => 'annual', 'label' => 'Annual unlock', 'color' => '#0F9F6E', 'values' => $annual],
            ],
        ];
    }

    /**
     * @return array{rate: int, due: int, renewed: int}
     */
    public function annualRenewalRate(): array
    {
        $due = User::query()
            ->artisans()
            ->whereNotNull('annual_expires_at')
            ->whereBetween('annual_expires_at', [now()->subMonths(6), now()->addDays(14)])
            ->count();

        $renewed = User::query()
            ->artisans()
            ->whereNotNull('annual_expires_at')
            ->where('annual_expires_at', '>', now())
            ->whereBetween('annual_expires_at', [now(), now()->addYear()])
            ->whereHas('tokenPurchases', function ($query) {
                $query->where('status', TokenPurchase::STATUS_COMPLETED)
                    ->whereIn('pack_key', ['annual', 'annual_unlock'])
                    ->where('created_at', '>=', now()->subMonths(6));
            })
            ->count();

        return [
            'due' => $due,
            'renewed' => $renewed,
            'rate' => $due ? (int) round(($renewed / $due) * 100) : 0,
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function referralLiability(int $months): array
    {
        $unit = (int) (config('pricing.credits.packs.0.price', 3000) / max(1, (int) config('pricing.credits.packs.0.credits', 10)));

        $series = $this->monthlySeries(
            TokenTransaction::query()
                ->where('action', TokenTransaction::ACTION_REFERRAL)
                ->where('type', TokenTransaction::TYPE_CREDIT),
            'created_at',
            'amount',
            $months,
        );

        return collect($series)
            ->map(fn (array $point) => [
                'label' => $point['label'],
                'date' => $point['date'] ?? null,
                'value' => $point['value'] * $unit,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int, color: string, percent: int}>
     */
    private function planMix(): array
    {
        $annual = User::query()
            ->artisans()
            ->where(function ($query) {
                $query->where('plan', 'annual')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('annual_expires_at')
                            ->where('annual_expires_at', '>', now());
                    });
            })
            ->count();

        $payg = User::query()
            ->artisans()
            ->where(function ($query) {
                $query->where('plan', '!=', 'annual')
                    ->orWhereNull('plan');
            })
            ->where(function ($query) {
                $query->where('token_balance', '>', 0)
                    ->orWhereHas('tokenPurchases', fn ($p) => $p->where('status', TokenPurchase::STATUS_COMPLETED));
            })
            ->where(function ($query) {
                $query->whereNull('annual_expires_at')
                    ->orWhere('annual_expires_at', '<=', now());
            })
            ->where('plan', '!=', 'annual')
            ->count();

        $total = User::query()->artisans()->count();
        $free = max(0, $total - $annual - $payg);

        $items = [
            ['label' => 'Free', 'value' => $free, 'color' => '#2F6FED'],
            ['label' => 'Pay-as-you-go', 'value' => $payg, 'color' => '#FF6A3D'],
            ['label' => 'Annual', 'value' => $annual, 'color' => '#0F9F6E'],
        ];

        return collect($items)
            ->map(function (array $item) use ($total) {
                $item['percent'] = $total > 0 ? (int) round(($item['value'] / $total) * 100) : 0;

                return $item;
            })
            ->all();
    }

    /**
     * @param  Builder<Model>  $query
     * @return list<array{label: string, value: int}>
     */
    private function dailySeries($query, string $dateColumn, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $expr = $this->dayExpression($dateColumn);

        $rows = (clone $query)
            ->where($dateColumn, '>=', $start)
            ->selectRaw("{$expr} as day_key, COUNT(*) as total")
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $series[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'value' => (int) ($rows[$day->toDateString()] ?? 0),
            ];
        }

        return $series;
    }

    private function dayExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "date({$column})";
        }

        return "DATE({$column})";
    }

    private function weekExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "strftime('%Y-%W', {$column})";
        }

        return "DATE_FORMAT({$column}, '%x-%v')";
    }

    /**
     * @param  Builder<Model>  $query
     * @return list<array{label: string, value: int}>
     */
    private function monthlySeries($query, string $dateColumn, ?string $sumColumn, int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $expr = $this->monthExpression($dateColumn);

        $rows = (clone $query)
            ->where($dateColumn, '>=', $start)
            ->selectRaw("{$expr} as month_key, ".($sumColumn ? "SUM({$sumColumn})" : 'COUNT(*)').' as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $series = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $series[] = [
                'label' => $month->format('M'),
                'date' => $month->toDateString(),
                'value' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    private function monthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "strftime('%Y-%m', {$column})";
        }

        return "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function completedRevenueBetween(Carbon $from, Carbon $to): int
    {
        return (int) TokenPurchase::query()
            ->where('status', TokenPurchase::STATUS_COMPLETED)
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('paid_at')->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->sum('price');
    }

    /**
     * @param  Collection<int, int>  $userIds
     */
    private function activeAfter($userIds, Carbon $cohortStart, int $days): int
    {
        if ($userIds->isEmpty()) {
            return 0;
        }

        $until = (clone $cohortStart)->addDays($days);

        return (int) WorkLog::query()
            ->whereIn('user_id', $userIds)
            ->where('created_at', '<=', $until)
            ->selectRaw('COUNT(DISTINCT user_id) as aggregate')
            ->value('aggregate');
    }

    /**
     * @return array{value: int, label: string, tone: string}|null
     */
    private function absoluteDelta(int $current, int $previous, string $period): ?array
    {
        if ($current === 0 && $previous === 0) {
            return null;
        }

        $diff = $current - $previous;
        $prefix = $diff > 0 ? '+' : '';

        return [
            'value' => $diff,
            'label' => $prefix.number_format($diff).' '.$period,
            'tone' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral'),
        ];
    }

    private function processorLabel(string $processor): string
    {
        return match ($processor) {
            'paystack' => 'Paystack',
            'flutterwave' => 'Flutterwave',
            'unspecified', '' => 'Unspecified',
            default => ucfirst($processor),
        };
    }
}
