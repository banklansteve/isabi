<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Support\ActivityLogger;
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
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login', [
            'canResetPassword' => Route::has('admin.password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // TODO: enforce 2FA before production

        app(SessionLifetime::class)->apply($request->user());

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now(), 'last_logout_at' => null])->save();
        $request->session()->put('auth.staff_epoch', (int) $user->session_epoch);
        PatrolIp::rememberLogin($user, $request->ip());

        ActivityLogger::log(
            action: 'auth.admin_login',
            summary: "{$user->name} signed in to the Kraftrack admin portal.",
            user: $user,
        );

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $user->forceFill(['last_logout_at' => now()])->save();
            ActivityLogger::log(
                action: 'auth.admin_logout',
                summary: "{$user->name} signed out of the admin portal.",
                user: $user,
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
