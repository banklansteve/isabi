<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Soft verification screen — code entry stays in the signup browser context.
     */
    public function __invoke(Request $request, EmailVerificationService $verification): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->homeRouteName(), absolute: false));
        }

        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
            'email' => $user->email,
            'resendCooldown' => $verification->secondsUntilResend($user),
            'codeTtlMinutes' => EmailVerificationService::CODE_TTL_MINUTES,
        ]);
    }
}
