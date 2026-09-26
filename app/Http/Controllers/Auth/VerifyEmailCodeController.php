<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailCodeController extends Controller
{
    /**
     * Confirm email with the 6-digit code (preferred — stays in-context).
     */
    public function store(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->homeRouteName(), absolute: false));
        }

        $verification->verifyCode($user, (string) $request->input('code'));

        return redirect()->intended(route($user->homeRouteName(), absolute: false))
            ->with('toast', [
                'type' => 'success',
                'title' => 'Email verified',
                'message' => 'You’re all set — full access unlocked.',
                'duration' => 4800,
            ]);
    }
}
