<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ResendInvitationRequest;
use App\Http\Requests\Admin\Auth\VerifyInvitationRequest;
use App\Support\Staff\StaffInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifyInvitationController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Auth/VerifyInvitation', [
            'email' => $request->query('email', ''),
            'status' => session('status'),
        ]);
    }

    public function store(VerifyInvitationRequest $request, StaffInvitationService $invitations): RedirectResponse
    {
        $user = $invitations->verify(
            $request->validated('email'),
            $request->validated('code'),
        );

        $invitations->rememberVerified($user);

        return redirect()
            ->route('admin.invite.password')
            ->with('status', 'Email confirmed. Choose a password to finish setting up your account.');
    }

    public function resend(ResendInvitationRequest $request, StaffInvitationService $invitations): RedirectResponse
    {
        $invitations->resend($request->validated('email'));

        return back()->with('status', 'A new confirmation code is on its way.');
    }
}
