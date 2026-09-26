<?php

namespace App\Jobs;

use App\Models\AdminAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WriteAdminAuditLogJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array{
     *     actor_id: int|null,
     *     actor_kind: string,
     *     action: string,
     *     retention_tier: string,
     *     summary: string,
     *     subject_type: string|null,
     *     subject_id: int|string|null,
     *     old_values: array<string, mixed>|null,
     *     new_values: array<string, mixed>|null,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     created_at: string
     * }  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        AdminAuditLog::query()->create($this->payload);
    }
}
