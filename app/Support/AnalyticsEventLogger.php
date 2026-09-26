<?php

namespace App\Support;

use App\Enums\ActorKind;
use App\Jobs\WriteAnalyticsEventJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AnalyticsEventLogger
{
    /**
     * Queue a product-analytics event into analytics_events (not the audit trail).
     *
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $action,
        ?string $summary = null,
        ?User $user = null,
        array $properties = [],
    ): void {
        $user ??= Auth::user();

        WriteAnalyticsEventJob::dispatch([
            'user_id' => $user?->id,
            'actor_kind' => ActorKind::fromUser($user)->value,
            'action' => $action,
            'summary' => $summary,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
