<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAssigned
{
    /**
     * Gate a staff route by an assigned duty slug.
     * Super admin always passes. Operations staff pass when assigned.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();

        if (! $user?->isStaff() || ! $user->hasDuty($slug)) {
            abort(403, 'You are not assigned to this duty.');
        }

        return $next($request);
    }
}
