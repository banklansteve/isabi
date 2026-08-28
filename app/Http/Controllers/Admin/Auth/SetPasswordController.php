<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\SetPasswordRequest;
use App\Support\ActivityLogger;
use App\Support\Staff\StaffInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SetPasswordController extends Controller
{
    public function create(StaffInvitationService $invitations): Response
    {
        $user = $invitations->verifiedUserFromSession();

        return Inertia::render('Admin/Auth/SetPassword', [
            'email' => $user?->email,
            'name' => $user?->first_name ?: $user?->name,
            'status' => session('status'),
        ]);
    }

    public function store(SetPasswordRequest $request, StaffInvitationService $invitations): RedirectResponse
    {
        $user = $request->attributes->get('staffInvitationUser')
            ?? $invitations->verifiedUserFromSession();

        abort_unless($user, 403);

        $user = $invitations->completePassword($user, $request->validated('password'));

        $invitations->forgetVerified();

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now(), 'last_logout_at' => null])->save();
        $request->session()->put('auth.staff_epoch', (int) $user->session_epoch);

        ActivityLogger::log(
            action: 'auth.admin_login',
            summary: "{$user->name} signed in to the Isabi admin portal after setup.",
            user: $user,
        );

        return redirect()->route('admin.dashboard');
    }
}
