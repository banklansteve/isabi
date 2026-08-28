<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isStaff()) {
            abort(403, 'You do not have permission to access this area.');
        }

        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'This staff account has been suspended.']);
        }

        $epoch = (int) $user->session_epoch;
        $sessionEpoch = $request->session()->get('auth.staff_epoch');

        if ($sessionEpoch === null) {
            $request->session()->put('auth.staff_epoch', $epoch);
        } elseif ((int) $sessionEpoch !== $epoch) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Your session was signed out. Please sign in again.']);
        }

        if (! $user->hasSetPassword()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Open the invite link from your email to set up your account.']);
        }

        return $next($request);
    }
}
