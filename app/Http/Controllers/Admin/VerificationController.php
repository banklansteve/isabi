<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Email verification is automatic. This duty has been retired.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()
            ->route('admin.dashboard')
            ->with('toast', [
                'type' => 'info',
                'title' => 'Verification is automatic',
                'message' => 'Artisans verify by email code at signup. This ops queue has been removed.',
                'duration' => 5200,
            ]);
    }
}
