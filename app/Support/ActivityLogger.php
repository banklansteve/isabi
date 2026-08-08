<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(
        string $action,
        string $summary,
        ?User $user = null,
        array $properties = [],
    ): ActivityLog {
        $user ??= Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'summary' => $summary,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
