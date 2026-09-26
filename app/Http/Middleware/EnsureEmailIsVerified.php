<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Soft product gate — user stays logged in, but sensitive actions need a verified email.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            if ($user->isStaff()) {
                return $next($request);
            }

            return redirect()
                ->route('verification.notice')
                ->with('toast', [
                    'type' => 'info',
                    'title' => 'Verify your email',
                    'message' => 'Confirm your email to unlock this action.',
                    'duration' => 5200,
                ]);
        }

        return $next($request);
    }
}
