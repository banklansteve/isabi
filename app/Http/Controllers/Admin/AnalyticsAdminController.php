<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsAdminController extends Controller
{
    public function index(Request $request, DashboardMetrics $metrics): Response
    {
        return Inertia::render('Admin/Analytics/Index', [
            ...$metrics->analytics(),
            'filters' => [
                'tab' => (string) $request->query('tab', 'growth'),
            ],
        ]);
    }
}
