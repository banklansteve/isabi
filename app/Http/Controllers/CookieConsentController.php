<?php

namespace App\Http\Controllers;

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

        // Browser cookie only — no audit/analytics row (guests have no user record).
        return back()->withCookie(CookieConsent::makeCookie($data['status']));
    }
}
