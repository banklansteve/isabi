<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;
use App\Support\Patrol\PatrolText;

class ReusedClientContactRule implements PatrolRule
{
    public function key(): string
    {
        return 'reused_client_contact';
    }

    public function evaluate(WorkLog $log): ?array
    {
        $normalized = PatrolText::normalizeWhatsapp($log->client_whatsapp);
        if (! $normalized) {
            return null;
        }

        $config = config('patrol.rules.reused_client_contact');
        $minJobs = max(3, (int) ($config['min_jobs'] ?? 5));
        $windowDays = max(1, (int) ($config['window_days'] ?? 7));
        $createdAt = $log->created_at ?? now();
        $from = $createdAt->copy()->subDays($windowDays);
        $to = $createdAt->copy()->addDays($windowDays);

        $candidates = WorkLog::query()
            ->where('user_id', $log->user_id)
            ->whereNotNull('client_whatsapp')
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'client_whatsapp', 'created_at']);

        $cluster = $candidates->filter(
            fn (WorkLog $item) => PatrolText::normalizeWhatsapp($item->client_whatsapp) === $normalized,
        );

        if ($cluster->count() < $minJobs) {
            return null;
        }

        $spanDays = max(
            1,
            (int) ceil($cluster->min('created_at')->diffInSeconds($cluster->max('created_at')) / 86400),
        );

        return [
            'trigger' => sprintf(
                'same client WhatsApp on %d distinct jobs within %d days',
                $cluster->count(),
                $spanDays,
            ),
            'job_count' => $cluster->count(),
            'window_days' => $windowDays,
            'span_days' => $spanDays,
            'threshold' => $minJobs,
            'contact_suffix' => substr($normalized, -4),
            'work_log_ids' => $cluster->pluck('id')->all(),
        ];
    }
}
