<?php

namespace App\Http\Controllers;

use App\Support\AnalyticsEventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsBeaconController extends Controller
{
    /**
     * Lightweight page-duration beacon (authenticated artisans).
     * Fire-and-forget from the SPA — does not require consent for signed-in users.
     */
    public function pageDuration(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->isStaff()) {
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'page' => ['required', 'string', 'max:64'],
            'seconds' => ['required', 'numeric', 'min:1', 'max:3600'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        AnalyticsEventLogger::log(
            action: 'page.duration',
            summary: 'Page dwell',
            user: $user,
            properties: [
                'page' => $data['page'],
                'seconds' => (int) round((float) $data['seconds']),
                'path' => $data['path'] ?? null,
            ],
        );

        return response()->json(['ok' => true]);
    }
}
