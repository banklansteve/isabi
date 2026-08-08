<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalDocsAccess
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('internal.docs_enabled')) {
            abort(404);
        }

        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isStaff()) {
            return $next($request);
        }

        $allowed = config('internal.allowed_emails', []);

        if ($allowed !== [] && in_array(strtolower((string) $user->email), $allowed, true)) {
            return $next($request);
        }

        abort(403);
    }
}
