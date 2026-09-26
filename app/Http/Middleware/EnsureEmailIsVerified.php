<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft product gate — account stays signed in; only outward-facing / trust actions need a verified email
 * (e.g. sending a client review request). Everything else in the app stays usable.
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
                'message' => 'Confirm your email to send review requests and other client-facing actions.',
                'duration' => 5200,
            ];

            // Stay in context when possible — banner + toast, not a hard detour.
            if ($request->headers->get('referer')) {
                return redirect()->back()->with('toast', $toast);
            }

            return redirect()
                ->route('register')
                ->with('toast', $toast);
        }

        return $next($request);
    }
}
