<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Dashboard', [
            'roleLabel' => $user->role?->label(),
            'roleDescription' => $user->role?->description(),
            'abilities' => $user->role?->abilities() ?? [],
        ]);
    }
}
