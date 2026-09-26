<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AnalyticsEventLogger;
use App\Support\Auth\SessionLifetime;
use App\Support\Patrol\PatrolIp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        $redirect = $request->query('redirect');

        if (
            is_string($redirect)
            && str_starts_with($redirect, '/')
            && ! str_starts_with($redirect, '//')
        ) {
            $request->session()->put('url.intended', $redirect);
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        app(SessionLifetime::class)->apply($request->user());

        $request->session()->regenerate();

        $user = $request->user();
        PatrolIp::rememberLogin($user, $request->ip());

        // Product analytics only — not the audit trail (anomaly detection deferred).
        AnalyticsEventLogger::log(
            action: 'auth.login',
            summary: "{$user->name} signed in to Kraftrack.",
            user: $user,
        );

        $user->forceFill(['last_seen_at' => now()])->saveQuietly();

        $home = route($user->homeRouteName(), absolute: false);

        return redirect()->intended($home);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Logout is not audited or sent to product analytics going forward.
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
