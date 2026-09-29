<?php

namespace App\Support\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsUserActivityDay;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Extra growth / engagement / retention pulse reports for Admin Analytics.
 * Keeps ProductAnalyticsReports focused on nightly summary tables.
 */
class AnalyticsPulseReports
{
    /**
     * @return array<string, mixed>
     */
    public function forAnalyticsPage(): array
    {
        return [
            'signup_daily' => $this->signupDailyWithRolling(90),
            'signup_weekly' => $this->signupWeekly(26),
            'signup_source' => $this->signupSourceBreakdown(),
            'trade_signup_trend' => $this->tradeSignupTrend(90),
            'geo_signups' => $this->geoSignups(),
            'device_split' => $this->deviceSplit(90),
            'time_to_first_job' => $this->timeToFirstJob(12),
            'page_time' => $this->pageTime(30),
            'page_time_ready' => $this->pageTimeReady(),
            'retention_heatmap' => $this->retentionHeatmap(12),
        ];
    }

    /**
     * Ensure recent daily summaries exist so DAU charts aren't empty after logins.
     */
    public function refreshRecentSummaries(int $days = 3): void
    {
        $key = 'analytics.pulse.refresh';
        if (Cache::has($key)) {
            return;
        }

        // Short throttle so admins see fresh DAU soon after logins, without hammering aggregates.
        Cache::put($key, 1, now()->addMinutes(5));

        $aggregator = app(AnalyticsAggregator::class);
        for ($i = $days - 1; $i >= 0; $i--) {
            $aggregator->aggregateDay(now()->subDays($i));
        }
    }

    /**
     * @return list<array{label: string, date: string, value: int, rolling_avg: float|null}>
     */
    private function signupDailyWithRolling(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = User::query()
            ->artisans()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $series = [];
        $window = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $key = $day->toDateString();
            $value = (int) ($rows[$key] ?? 0);
            $window[] = $value;
            if (count($window) > 7) {
                array_shift($window);
            }
            $series[] = [
                'label' => $day->format('j M'),
                'date' => $key,
                'value' => $value,
                'rolling_avg' => count($window) >= 3
                    ? round(array_sum($window) / count($window), 1)
                    : null,
            ];
        }

        return $series;
    }

    /**
     * @return list<array{label: string, date: string, value: int}>
     */
    private function signupWeekly(int $weeks): array
    {
        $start = now()->subWeeks($weeks - 1)->startOfWeek();
        $users = User::query()
            ->artisans()
            ->where('created_at', '>=', $start)
            ->get(['created_at']);

        $grouped = $users->groupBy(fn (User $u) => $u->created_at->copy()->startOfWeek()->toDateString());

        $series = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $week = now()->subWeeks($i)->startOfWeek();
            $key = $week->toDateString();
            $series[] = [
                'label' => $week->format('j M'),
                'date' => $key,
                'value' => $grouped->get($key)?->count() ?? 0,
            ];
        }

        return $series;
    }

    /**
     * @return list<array{label: string, value: int, color: string, percent: int}>
     */
    private function signupSourceBreakdown(): array
    {
        $referral = User::query()->artisans()->whereNotNull('referred_by_user_id')->count();
        $total = User::query()->artisans()->count();
        // UTM / landing-channel capture is not wired yet — treat non-referral as organic.
        // Keep a Direct bucket at 0 so the UI can light up when a third channel appears.
        $direct = 0;
        $organic = max(0, $total - $referral - $direct);

        $items = [
            ['label' => 'Organic', 'value' => $organic, 'color' => '#2F6FED'],
            ['label' => 'Referral', 'value' => $referral, 'color' => '#0F9F6E'],
            ['label' => 'Direct', 'value' => $direct, 'color' => '#0B1F3A'],
        ];

        return collect($items)
            ->map(function (array $item) use ($total) {
                $item['percent'] = $total > 0 ? (int) round(($item['value'] / $total) * 100) : 0;

                return $item;
            })
            ->values()
            ->all();
    }

    /**
     * Top trades by new signups in the window, with prior-window comparison.
     *
     * @return list<array{label: string, value: int, prior: int, delta: int, tone: string}>
     */
    private function tradeSignupTrend(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $priorFrom = now()->subDays(($days * 2) - 1)->startOfDay();
        $priorTo = $from->copy()->subSecond();

        $current = User::query()
            ->artisans()
            ->where('created_at', '>=', $from)
            ->whereNotNull('trade')
            ->where('trade', '!=', '')
            ->select('trade', DB::raw('COUNT(*) as total'))
            ->groupBy('trade')
            ->pluck('total', 'trade');

        $prior = User::query()
            ->artisans()
            ->whereBetween('created_at', [$priorFrom, $priorTo])
            ->whereNotNull('trade')
            ->where('trade', '!=', '')
            ->select('trade', DB::raw('COUNT(*) as total'))
            ->groupBy('trade')
            ->pluck('total', 'trade');

        return $current
            ->map(function ($total, $trade) use ($prior) {
                $p = (int) ($prior[$trade] ?? 0);
                $c = (int) $total;
                $delta = $c - $p;

                return [
                    'label' => (string) $trade,
                    'value' => $c,
                    'prior' => $p,
                    'delta' => $delta,
                    'tone' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
                ];
            })
            ->sortByDesc('value')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return array{states: list, cities: list}
     */
    private function geoSignups(): array
    {
        $from = now()->subDays(89)->startOfDay();

        $states = User::query()
            ->artisans()
            ->where('created_at', '>=', $from)
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->select('state', DB::raw('COUNT(*) as total'))
            ->groupBy('state')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['label' => $row->state, 'value' => (int) $row->total])
            ->all();

        $cities = User::query()
            ->artisans()
            ->where('created_at', '>=', $from)
            ->whereNotNull('lga')
            ->where('lga', '!=', '')
            ->select('lga', 'state', DB::raw('COUNT(*) as total'))
            ->groupBy('lga', 'state')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->lga.($row->state ? ', '.$row->state : ''),
                'value' => (int) $row->total,
            ])
            ->all();

        return ['states' => $states, 'cities' => $cities];
    }

    /**
     * @return list<array{label: string, value: int, color: string, percent: int}>
     */
    private function deviceSplit(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $events = AnalyticsEvent::query()
            ->where('action', 'auth.login')
            ->where('created_at', '>=', $from)
            ->whereNotNull('user_agent')
            ->get(['user_agent', 'properties']);

        $mobile = 0;
        $desktop = 0;

        foreach ($events as $event) {
            $props = is_array($event->properties) ? $event->properties : [];
            $class = $props['device'] ?? null;
            if (! in_array($class, ['mobile', 'desktop'], true)) {
                $class = $this->classifyUa((string) $event->user_agent);
            }
            if ($class === 'mobile') {
                $mobile++;
            } else {
                $desktop++;
            }
        }

        // Fallback stretch: last_seen user agents aren't stored; use event count only.
        $total = $mobile + $desktop;
        $items = [
            ['label' => 'Mobile', 'value' => $mobile, 'color' => '#2F6FED'],
            ['label' => 'Desktop', 'value' => $desktop, 'color' => '#0B1F3A'],
        ];

        return collect($items)
            ->map(function (array $item) use ($total) {
                $item['percent'] = $total > 0 ? (int) round(($item['value'] / $total) * 100) : 0;

                return $item;
            })
            ->all();
    }

    private function classifyUa(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Median hours to first job by signup month.
     *
     * @return list<array{label: string, date: string, value: float, sample: int}>
     */
    private function timeToFirstJob(int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $users = User::query()
            ->artisans()
            ->where('created_at', '>=', $start)
            ->with(['workLogs' => fn ($q) => $q->orderBy('created_at')->limit(1)])
            ->get(['id', 'created_at']);

        $byMonth = $users->groupBy(fn (User $u) => $u->created_at->format('Y-m'));

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $hours = [];
            foreach ($byMonth->get($key, collect()) as $user) {
                $first = $user->workLogs->first();
                if (! $first) {
                    continue;
                }
                $hours[] = max(0, $user->created_at->diffInHours($first->created_at));
            }
            sort($hours);
            $n = count($hours);
            $median = 0.0;
            if ($n > 0) {
                $mid = intdiv($n, 2);
                $median = $n % 2 === 0
                    ? ($hours[$mid - 1] + $hours[$mid]) / 2
                    : (float) $hours[$mid];
            }

            $series[] = [
                'label' => $month->format('M Y'),
                'date' => $month->toDateString(),
                'value' => round($median / 24, 1), // days
                'sample' => $n,
            ];
        }

        return $series;
    }

    private function pageTimeReady(): bool
    {
        return AnalyticsEvent::query()
            ->where('action', 'page.duration')
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();
    }

    /**
     * @return list<array{label: string, value: float, visits: int}>
     */
    private function pageTime(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $events = AnalyticsEvent::query()
            ->where('action', 'page.duration')
            ->where('created_at', '>=', $from)
            ->get(['properties']);

        $buckets = [];
        foreach ($events as $event) {
            $props = is_array($event->properties) ? $event->properties : [];
            $page = (string) ($props['page'] ?? 'unknown');
            $seconds = (float) ($props['seconds'] ?? 0);
            if ($seconds <= 0 || $seconds > 3600) {
                continue;
            }
            $buckets[$page] ??= ['total' => 0.0, 'n' => 0];
            $buckets[$page]['total'] += $seconds;
            $buckets[$page]['n']++;
        }

        $labels = [
            'dashboard' => 'Dashboard',
            'work-log' => 'Work log',
            'profile' => 'Profile',
            'credits' => 'Credits',
            'help' => 'Help',
            'public-profile' => 'Public profile',
            'unknown' => 'Other',
        ];

        return collect($buckets)
            ->map(function ($row, $page) use ($labels) {
                $n = (int) $row['n'];

                return [
                    'label' => $labels[$page] ?? $page,
                    'value' => $n > 0 ? round($row['total'] / $n, 1) : 0,
                    'visits' => $n,
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * Weekly signup cohorts × weeks-later retention % (login activity).
     *
     * @return array{columns: list<string>, rows: list<array{label: string, cells: list<array{value: int|null, label: string}>}>}
     */
    private function retentionHeatmap(int $cohorts): array
    {
        $columns = ['W0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7'];
        $rows = [];

        for ($c = $cohorts - 1; $c >= 0; $c--) {
            $cohortStart = now()->subWeeks($c)->startOfWeek();
            $cohortEnd = $cohortStart->copy()->endOfWeek();
            $ids = User::query()
                ->artisans()
                ->whereBetween('created_at', [$cohortStart, $cohortEnd])
                ->pluck('id');

            $signed = $ids->count();
            $cells = [];
            foreach ($columns as $wi => $col) {
                if ($signed === 0) {
                    $cells[] = ['value' => null, 'label' => '—'];
                    continue;
                }
                $weekStart = $cohortStart->copy()->addWeeks($wi);
                if ($weekStart->isFuture()) {
                    $cells[] = ['value' => null, 'label' => ''];
                    continue;
                }
                $weekEnd = $weekStart->copy()->endOfWeek();
                $active = AnalyticsUserActivityDay::query()
                    ->whereIn('user_id', $ids)
                    ->whereBetween('activity_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->distinct('user_id')
                    ->count('user_id');
                // Also count job activity as active for denser signal when logins were missing.
                if ($active === 0) {
                    $active = WorkLog::query()
                        ->whereIn('user_id', $ids)
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->distinct('user_id')
                        ->count('user_id');
                }
                $pct = (int) round(($active / $signed) * 100);
                $cells[] = ['value' => $pct, 'label' => $pct.'%'];
            }

            $rows[] = [
                'label' => $cohortStart->format('j M'),
                'signed_up' => $signed,
                'cells' => $cells,
            ];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }
}
