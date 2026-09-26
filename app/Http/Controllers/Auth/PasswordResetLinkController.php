<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'sentTo' => session('sentTo'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Always return a generic success so we don’t leak whether an email is registered.
     * Verified-email requirement is the exception — account owners need a clear next step.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');
        $user = User::query()->where('email', $email)->first();

        $generic = [
            'status' => 'reset-link-sent',
            'sentTo' => $email,
            'toast' => [
                'type' => 'success',
                'title' => 'Check your email',
                'message' => 'If that address has an account, a reset link is on its way.',
                'duration' => 5200,
            ],
        ];

        // Staff use the admin reset flow — never send a public reset from here.
        if ($user?->isStaff()) {
            return back()->with($generic);
        }

        if ($user && method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'email' => 'Verify your email before resetting your password. Log in and enter the code we sent you, or resend it from your dashboard.',
            ]);
        }

        if ($user) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with($generic);
    }
}
