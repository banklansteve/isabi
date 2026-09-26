<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Resend verification email (code + link) with cooldown.
     */
    public function store(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($request->user()->homeRouteName(), absolute: false));
        }

        $verification->assertCanResend($request->user());
        $verification->issue($request->user(), $request->session()->getId());

        // Prefer returning to the signup screen so verify + resend never feel like a detour.
        if ($request->header('X-Inertia') && str_contains((string) $request->headers->get('referer'), '/register')) {
            return redirect()->route('register')->with('status', 'verification-link-sent')->with('toast', [
                'type' => 'success',
                'title' => 'Code sent',
                'message' => 'Check your email for a fresh 6-digit code.',
                'duration' => 4500,
            ]);
        }

        return back()->with('status', 'verification-link-sent')->with('toast', [
            'type' => 'success',
            'title' => 'Code sent',
            'message' => 'Check your email for a fresh 6-digit code.',
            'duration' => 4500,
        ]);
    }
}