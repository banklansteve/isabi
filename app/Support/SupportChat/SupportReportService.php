<?php

namespace App\Support\SupportChat;

use App\Models\SupportTicket;
use Illuminate\Support\Carbon;

class SupportReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $tickets = SupportTicket::query()
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $resolved = $tickets->where('status', SupportTicket::STATUS_RESOLVED);
        $withResponse = $tickets->filter(fn (SupportTicket $ticket) => $ticket->first_response_at);
        $withCsat = $tickets->filter(fn (SupportTicket $ticket) => $ticket->csat_score);

        $volume = SupportTicket::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
            ])
            ->all();

        $byTopic = $tickets
            ->groupBy(fn (SupportTicket $ticket) => $ticket->topic_key ?: 'other')
            ->map(fn ($group, $key) => [
                'key' => $key,
                'label' => $this->topicLabel((string) $key),
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'volume' => $tickets->count(),
            'resolved' => $resolved->count(),
            'avg_first_response_minutes' => $this->averageMinutes(
                $withResponse->map(fn (SupportTicket $ticket) => $ticket->created_at?->diffInSeconds($ticket->first_response_at) ?? 0),
            ),
            'avg_resolution_minutes' => $this->averageMinutes(
                $resolved->map(fn (SupportTicket $ticket) => $ticket->created_at?->diffInSeconds($ticket->resolved_at) ?? 0),
            ),
            'csat_average' => $withCsat->avg('csat_score') ? round((float) $withCsat->avg('csat_score'), 1) : null,
            'csat_count' => $withCsat->count(),
            'series' => $volume,
            'topics' => $byTopic,
        ];
    }

    private function averageMinutes($seconds)
    {
        $values = collect($seconds)->filter(fn ($value) => $value > 0);

        if ($values->isEmpty()) {
            return null;
        }

        return (int) round($values->avg() / 60);
    }

    private function topicLabel(string $key): string
    {
        foreach (config('support.starters', []) as $starter) {
            if (($starter['key'] ?? '') === $key) {
                return (string) $starter['label'];
            }
        }

        return $key === 'other' ? 'Something else' : $key;
    }
}
