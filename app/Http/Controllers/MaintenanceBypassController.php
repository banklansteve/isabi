<?php

namespace App\Http\Controllers;

use App\Support\MaintenanceMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceBypassController extends Controller
{
    public function __invoke(Request $request, string $token, MaintenanceMode $maintenance): RedirectResponse
    {
        if (! $maintenance->enabled() || ! $maintenance->tokenMatches($token)) {
            return redirect()
                ->route('home')
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Bypass unavailable',
                    'message' => 'That maintenance bypass link is invalid or expired.',
                    'duration' => 5200,
                ]);
        }

        return redirect()
            ->route('home')
            ->withCookie($maintenance->makeBypassCookie($token))
            ->with('toast', [
                'type' => 'success',
                'title' => 'Bypass active',
                'message' => 'You can browse public pages while maintenance stays on for everyone else.',
                'duration' => 5600,
            ]);
    }
}
