<?php

namespace App\Support\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminResponse
{
    /**
     * @param  array<string, mixed>  $toast
     * @param  array<string, mixed>  $payload
     */
    public static function mutation(Request $request, array $toast, array $payload = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'ok' => true,
                'toast' => $toast,
                ...$payload,
            ]);
        }

        return back()->with('toast', $toast);
    }
}
