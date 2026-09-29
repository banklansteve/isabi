<?php

namespace App\Support\Admin;

use App\Enums\ActorKind;
use App\Enums\RetentionTier;
use App\Jobs\WriteAdminAuditLogJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminAudit
{
    /**
     * Queue a staff/ops audit write. Never writes inline — see WriteAdminAuditLogJob.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(
        string $action,
        string $summary,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?User $actor = null,
    ): void {
        $actor ??= Auth::user();
        $actorKind = $actor
            ? ActorKind::fromUser($actor)
            : ActorKind::System;

        // Persist immediately — staff audit must not depend on a queue worker.
        WriteAdminAuditLogJob::dispatchSync([
            'actor_id' => $actor?->id,
            'actor_kind' => $actorKind->value,
            'action' => $action,
            'retention_tier' => RetentionTier::Staff->value,
            'summary' => $summary,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
