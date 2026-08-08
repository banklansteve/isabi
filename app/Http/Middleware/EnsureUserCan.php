<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCan
{
    /**
     * Gate a route by a named ability from UserRole::abilities().
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canDo($ability)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
