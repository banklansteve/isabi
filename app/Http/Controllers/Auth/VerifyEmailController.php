<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VerifyEmailController extends Controller
{
    /**
     * One-click verification from email (may open in an in-app browser).
     */
    public function __invoke(Request $request, EmailVerificationService $verification): RedirectResponse|Response
    {
        $user = User::query()->findOrFail($request->route('id'));

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            abort(403, 'Invalid verification link.');
        }

        $token = (string) $request->query('token', '');

        try {
            $result = $verification->verifyLink($user, $token, $request->session()->getId());
        } catch (ValidationException $e) {
            if ($request->user()) {
                return redirect()
                    ->route('verification.notice')
                    ->withErrors($e->errors())
                    ->with('toast', [
                        'type' => 'error',
                        'title' => 'Link expired',
                        'message' => 'Enter the 6-digit code from your email instead.',
                        'duration' => 6000,
                    ]);
            }

            return Inertia::render('Auth/VerifyEmailResult', [
                'status' => 'expired',
                'message' => 'This verification link is invalid or has expired. Open Kraftrack and enter the code from your email, or request a new one.',
            ]);
        }

        $authedAsUser = $request->user()?->is($user) ?? false;

        if ($authedAsUser) {
            return redirect()->intended(route($user->homeRouteName(), absolute: false))
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Email verified',
                    'message' => 'You’re all set — full access unlocked.',
                    'duration' => 4800,
                ]);
        }

        // In-app browser / different context: email is verified; nudge them back.
        return Inertia::render('Auth/VerifyEmailResult', [
            'status' => 'verified',
            'same_session' => $result['same_session'],
            'message' => 'Your email is verified. Return to the browser tab where you were using Kraftrack and continue — you can close this window.',
            'dashboardUrl' => route($user->homeRouteName()),
            'loginUrl' => route('login'),
        ]);
    }
}
