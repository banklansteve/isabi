<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight last_seen_at updater for all signed-in users (artisans + staff).
 * Used by the realtime ping so Live Presence reports stay current.
 */
class UserPresence
{
    public function heartbeat(User $user): void
    {
        $now = now();
        $throttleKey = 'user.seen.write.'.$user->id;
        $seconds = max(15, (int) config('audit.analytics.presence_write_seconds', 30));

        Cache::put('user.presence.'.$user->id, $now->timestamp, now()->addMinutes(5));

        if (Cache::has($throttleKey)) {
            return;
        }

        Cache::put($throttleKey, 1, $seconds);
        $user->forceFill(['last_seen_at' => $now])->saveQuietly();
    }
}
