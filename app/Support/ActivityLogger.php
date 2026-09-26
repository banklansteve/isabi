<?php

namespace App\Support;

use App\Enums\ActorKind;
use App\Jobs\WriteActivityLogJob;
use App\Models\User;
use App\Support\Audit\AuditRetention;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Queue a public-facing (or legacy) activity log write.
     * Does not write inline — see WriteActivityLogJob.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $action,
        string $summary,
        ?User $user = null,
        array $properties = [],
    ): void {
        $user ??= Auth::user();
        $actorKind = ActorKind::fromUser($user);
        $tier = AuditRetention::tierForAction($action, staffSide: $actorKind === ActorKind::Staff);

        WriteActivityLogJob::dispatch([
            'user_id' => $user?->id,
            'actor_kind' => $actorKind->value,
            'action' => $action,
            'retention_tier' => $tier->value,
            'summary' => $summary,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
