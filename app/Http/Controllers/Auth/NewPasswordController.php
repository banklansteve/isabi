<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                if ($user->isStaff()) {
                    throw ValidationException::withMessages([
                        'email' => 'This reset link isn’t valid. Use the staff sign-in page instead.',
                    ]);
                }

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    'password_set_at' => now(),
                ])->save();

                // Kill other sessions so a stolen session can’t linger after the reset.
                $this->invalidateOtherSessions((int) $user->id);

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Password updated — sign in with your new password.')
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Password updated',
                    'message' => 'Sign in with your new password to continue.',
                    'duration' => 5200,
                ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    private function invalidateOtherSessions(int $userId): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        DB::table('sessions')->where('user_id', $userId)->delete();
    }
}
