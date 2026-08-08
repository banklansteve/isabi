<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use App\Support\CookieConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CookieConsentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([CookieConsent::STATUS_ACCEPTED, CookieConsent::STATUS_REJECTED])],
        ]);

        $status = $data['status'];
        $accepted = $status === CookieConsent::STATUS_ACCEPTED;

        ActivityLogger::log(
            action: $accepted ? 'cookie.accepted' : 'cookie.rejected',
            summary: $accepted
                ? ($request->user()
                    ? $request->user()->name.' accepted cookies (analytics & preferences allowed).'
                    : 'A visitor accepted cookies (analytics & preferences allowed).')
                : ($request->user()
                    ? $request->user()->name.' rejected non-essential cookies. Only essential cookies will be used.'
                    : 'A visitor rejected non-essential cookies. Only essential cookies will be used.'),
            user: $request->user(),
            properties: [
                'status' => $status,
                'version' => CookieConsent::VERSION,
            ],
        );

        return back()->withCookie(CookieConsent::makeCookie($status));
    }
}
