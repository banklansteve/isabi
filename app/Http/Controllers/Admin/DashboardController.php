<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\DashboardMetrics;
use App\Support\Admin\OpsAttentionFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics, OpsAttentionFeed $feed): Response
    {
        $user = $request->user();

        if ($user?->isOperationsAdmin()) {
            return Inertia::render('Admin/Ops/Home', $feed->home($user));
        }

        return Inertia::render('Admin/Overview', [
            ...$metrics->overview(),
            'restricted' => false,
        ]);
    }
}
