<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckRegistrationEmailController extends Controller
{
    /**
     * Early uniqueness check while the user is still on the registration form.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $taken = User::query()->where('email', $email)->exists();

        return response()->json([
            'available' => ! $taken,
            'message' => $taken
                ? 'An account with this email already exists. Log in instead.'
                : null,
        ]);
    }
}
