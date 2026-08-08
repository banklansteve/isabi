<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => UserRole::tryFrom($role))
            ->filter()
            ->all();

        if ($allowed === [] || ! $user->hasRole(...$allowed)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
