<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft product gate — account stays signed in; trust actions need a verified email
 * (logging jobs, sending client review requests). Browse / profile stay usable.
 */
class EnsureEmailIsVerified
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            if ($user->isStaff()) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Verify your email to unlock this action.',
                ], 403);
            }

            $toast = [
                'type' => 'info',
                'title' => 'Verify your email',
                'message' => 'Confirm your email to log jobs and send review requests.',
                'duration' => 5200,
            ];

            return redirect()
                ->route('verification.notice')
                ->with('toast', $toast);
        }

        return $next($request);
    }
}
