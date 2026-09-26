<?php

namespace App\Http\Controllers;

use App\Support\Staff\StaffPresence;
use App\Support\SupportChat\SupportPresence;
use App\Support\UserPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function ping(
        Request $request,
        SupportPresence $presence,
        StaffPresence $staffPresence,
        UserPresence $userPresence,
    ): JsonResponse {
        $user = $request->user();
        $presence->heartbeat($user);
        $userPresence->heartbeat($user);

        if ($request->boolean('interaction') && $user->isStaff()) {
            $staffPresence->touch($user);
        }

        return response()->json([
            'ok' => true,
            'uid' => $user->uid,
        ]);
    }
}
