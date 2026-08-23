<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics): Response
    {
        if ($request->user()?->isRestrictedStaff()) {
            return Inertia::render('Admin/Overview', [
                'restricted' => true,
            ]);
        }

        return Inertia::render('Admin/Overview', [
            ...$metrics->overview(),
            'restricted' => false,
        ]);
    }
}
