<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user?->isStaff() && $user->hasSetPassword() && ! $user->isSuspended()) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', __('A reset link will be sent if that email belongs to an active staff account.'));
    }
}
