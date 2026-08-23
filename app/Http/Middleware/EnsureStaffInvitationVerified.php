<?php

namespace App\Http\Middleware;

use App\Support\Staff\StaffInvitationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffInvitationVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = app(StaffInvitationService::class)->verifiedUserFromSession();

        if (! $user) {
            return redirect()
                ->route('admin.invite.verify')
                ->withErrors(['code' => 'Confirm your email with the code we sent before setting a password.']);
        }

        $request->attributes->set('staffInvitationUser', $user);

        return $next($request);
    }
}
