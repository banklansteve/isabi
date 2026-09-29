<?php

namespace App\Support\Admin;

use App\Models\AnalyticsDailyMetric;
use App\Models\SupportCannedReply;
use App\Models\SupportTicket;
use App\Support\SupportChat\SupportReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Content & support effectiveness reports for Admin Insights.
 */
class ContentEffectivenessReports
{
    public function __construct(private readonly SupportReportService $support) {}

    /**
     * @return array<string, mixed>
     */
    public function forInsightsPage(): array
    {
        $from = now()->subDays(89)->startOfDay();
        $to = now()->endOfDay();

        return [
            'help_vs_tickets' => $this->helpVsTickets($from, $to),
            'faq_effectiveness' => $this->faqEffectiveness(90),
            'support_health' => $this->supportHealth(90),
            'zero_result_searches' => $this->zeroResultSearches(),
        ];
    }

    /**
     * Help / help-chat page views vs support ticket volume (daily).
     *
     * @return array{ready: bool, hint: string, series: list<array{label: string, date: string, value: int, tickets: int}>}
     */
    private function helpVsTickets(Carbon $from, Carbon $to): array
    {
        $metrics = AnalyticsDailyMetric::query()
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('metric_date')
            ->get(['metric_date', 'page_help', 'page_help_chat']);

        $tickets = SupportTicket::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $byDate = $metrics->keyBy(fn ($row) => $row->metric_date->toDateString());

        $series = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $metric = $byDate->get($key);
            $helpViews = (int) ($metric?->page_help ?? 0) + (int) ($metric?->page_help_chat ?? 0);

            $series[] = [
                'label' => $day->format('j M'),
                'date' => $key,
                'value' => $helpViews,
                'tickets' => (int) ($tickets[$key] ?? 0),
            ];
        }

        $hasHelp = collect($series)->sum('value') > 0;
        $hasTickets = collect($series)->sum('tickets') > 0;

        return [
            'ready' => $hasHelp || $hasTickets,
            'hint' => 'Help page + help-chat opens vs tickets opened the same day. Per-article FAQ views are not instrumented yet — topic gaps below use ticket volume vs canned coverage.',
            'series' => $series,
        ];
    }

    /**
     * Ticket topics: volume vs whether a canned/FAQ reply covers them.
     *
     * @return list<array{label: string, key: string, tickets: int, covered: bool, tone: string, detail: string}>
     */
    private function faqEffectiveness(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $covered = SupportCannedReply::query()
            ->whereNotNull('topic_key')
            ->where('topic_key', '!=', '')
            ->pluck('topic_key')
            ->mapWithKeys(fn ($key) => [(string) $key => true]);

        $rows = SupportTicket::query()
            ->where('created_at', '>=', $from)
            ->whereNotNull('topic_key')
            ->where('topic_key', '!=', '')
            ->selectRaw('topic_key, COUNT(*) as total')
            ->groupBy('topic_key')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        return $rows->map(function ($row) use ($covered) {
            $key = (string) $row->topic_key;
            $isCovered = $covered->has($key);
            $total = (int) $row->total;

            return [
                'label' => $this->topicLabel($key),
                'key' => $key,
                'tickets' => $total,
                'value' => $total,
                'covered' => $isCovered,
                'tone' => $isCovered ? ($total >= 10 ? 'watch' : 'ok') : 'gap',
                'detail' => $isCovered
                    ? ($total >= 10
                        ? 'Covered in help, but tickets keep coming — article may not be solving it.'
                        : 'Covered by a canned / FAQ reply.')
                    : 'No canned reply for this topic — FAQ gap.',
            ];
        })->all();
    }

    /**
     * @return array{kpis: array, volume: list, resolution: list}
     */
    private function supportHealth(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();
        $summary = $this->support->summary($from, $to);

        $tickets = SupportTicket::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'resolved_at', 'status']);

        $byDay = $tickets->groupBy(fn (SupportTicket $t) => $t->created_at->toDateString());

        $volume = [];
        $resolution = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $key = $day->toDateString();
            $dayTickets = $byDay->get($key, collect());

            $volume[] = [
                'label' => $day->format('j M'),
                'date' => $key,
                'value' => $dayTickets->count(),
            ];

            $resolved = $dayTickets
                ->where('status', SupportTicket::STATUS_RESOLVED)
                ->filter(fn (SupportTicket $t) => $t->resolved_at && $t->created_at);

            $avgMinutes = null;
            if ($resolved->isNotEmpty()) {
                $avgMinutes = (int) round(
                    $resolved->avg(fn (SupportTicket $t) => $t->created_at->diffInSeconds($t->resolved_at) / 60)
                );
            }

            $resolution[] = [
                'label' => $day->format('j M'),
                'date' => $key,
                'value' => $avgMinutes ?? 0,
                'sample' => $resolved->count(),
            ];
        }

        return [
            'kpis' => [
                [
                    'label' => 'Tickets (90d)',
                    'value' => number_format((int) ($summary['volume'] ?? 0)),
                    'hint' => ($summary['resolved'] ?? 0).' resolved',
                ],
                [
                    'label' => 'Avg first reply',
                    'value' => $this->minutesLabel($summary['avg_first_response_minutes'] ?? null),
                    'hint' => 'Time to first agent response',
                ],
                [
                    'label' => 'Avg resolution',
                    'value' => $this->minutesLabel($summary['avg_resolution_minutes'] ?? null),
                    'hint' => 'Created → resolved',
                ],
                [
                    'label' => 'CSAT',
                    'value' => ($summary['csat_average'] ?? null) !== null
                        ? (string) $summary['csat_average']
                        : '—',
                    'hint' => ($summary['csat_count'] ?? 0)
                        ? $summary['csat_count'].' ratings'
                        : 'No ratings yet',
                ],
            ],
            'volume' => $volume,
            'resolution' => $resolution,
            'topics' => collect($summary['topics'] ?? [])
                ->map(fn (array $row) => [
                    'label' => $row['label'],
                    'value' => $row['total'],
                    'key' => $row['key'],
                ])
                ->all(),
        ];
    }

    /**
     * Directory search zero-results — not instrumented while search is client-side.
     *
     * @return array{ready: bool, hint: string, items: list}
     */
    private function zeroResultSearches(): array
    {
        return [
            'ready' => false,
            'hint' => 'Artisan directory search still runs in the browser. Once server-side discovery logs queries with zero matches, unmet demand (e.g. “generator repair”) will show here.',
            'items' => [],
        ];
    }

    private function topicLabel(string $key): string
    {
        foreach (config('support.starters', []) as $starter) {
            if (($starter['key'] ?? '') === $key) {
                return (string) $starter['label'];
            }
        }

        return $key === 'other' ? 'Something else' : Str::headline($key);
    }

    private function minutesLabel(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;

        return $rem ? "{$hours}h {$rem}m" : "{$hours}h";
    }
}
