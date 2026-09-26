<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WriteAnalyticsEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array{
     *     user_id: int|null,
     *     actor_kind: string,
     *     action: string,
     *     summary: string|null,
     *     properties: array<string, mixed>|null,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     created_at: string
     * }  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        AnalyticsEvent::query()->create($this->payload);
    }
}
