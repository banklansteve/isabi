<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AcceptInviteRequest;
use App\Support\Staff\StaffInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInviteController extends Controller
{
    public function create(string $token, StaffInvitationService $invitations): Response
    {
        $invitation = $invitations->findPendingByToken($token);

        if (! $invitation) {
            return Inertia::render('Admin/Auth/InviteExpired');
        }

        $user = $invitation->user;

        return Inertia::render('Admin/Auth/AcceptInvite', [
            'token' => $token,
            'email' => $user->email,
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: '',
            'needs_name' => blank($user->first_name),
            'expires_at' => $invitation->expires_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            'suggested_role' => $invitation->suggestedRole?->name,
        ]);
    }

    public function store(AcceptInviteRequest $request, string $token, StaffInvitationService $invitations): RedirectResponse
    {
        $invitation = $invitations->findPendingByToken($token);

        if (! $invitation) {
            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'That invite is no longer valid. Ask a Super Admin to send a new one.']);
        }

        $data = $request->validated();
        $user = $invitation->user;

        if (blank($user->first_name) && blank($data['name'] ?? null)) {
            throw ValidationException::withMessages([
                'name' => 'Add your name so the team knows who you are.',
            ]);
        }

        $user = $invitations->accept($invitation, $data['password'], $data['name'] ?? null);

        // TODO: enforce 2FA before production
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.dashboard')
            ->with('toast', [
                'type' => 'success',
                'title' => 'You’re in',
                'message' => 'Your operations account is ready.',
            ]);
    }
}
