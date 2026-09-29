<?php

namespace App\Support\Admin;

use Illuminate\Http\Request;

/**
 * Helpers for ops console navigation so prefetch / partial reloads stay side-effect free.
 */
class AdminNavigation
{
    public static function isPrefetch(Request $request): bool
    {
        $purpose = strtolower((string) (
            $request->headers->get('Purpose')
            ?: $request->headers->get('Sec-Purpose')
            ?: ''
        ));

        return str_contains($purpose, 'prefetch')
            || $request->headers->has('X-Inertia-Prefetch');
    }

    /**
     * Attention clears, presence heartbeats, and similar mutations must not run on
     * background prefetches or Inertia partial prop reloads.
     */
    public static function shouldMutateAttention(Request $request): bool
    {
        if (self::isPrefetch($request)) {
            return false;
        }

        if ($request->header('X-Inertia-Partial-Data')) {
            return false;
        }

        return true;
    }
}
