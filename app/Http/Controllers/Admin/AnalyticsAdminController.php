<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\DashboardMetrics;
use App\Support\Analytics\LivePresenceReport;
use App\Support\Analytics\ProductAnalyticsReports;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsAdminController extends Controller
{
    public function index(
        Request $request,
        DashboardMetrics $metrics,
        ProductAnalyticsReports $product,
        LivePresenceReport $presence,
    ): Response {
        $legacy = $metrics->analytics();
        $productPayload = $product->forAnalyticsPage();

        return Inertia::render('Admin/Analytics/Index', [
            // Growth / geography (operational tables).
            'user_growth' => $legacy['user_growth'],
            'jobs_trend' => $legacy['jobs_trend'],
            'daily_signups' => $legacy['daily_signups'],
            'cumulative_users' => $legacy['cumulative_users'],
            'acquisition' => $legacy['acquisition'],
            'geography' => $legacy['geography'],
            'cities' => $legacy['cities'],
            'trades' => $legacy['trades'],
            'funnel' => $legacy['funnel'],

            // Legacy engagement / retention (job activity — not overwritten by product summaries).
            'job_active_daily' => $legacy['active_daily'],
            'job_active_weekly' => $legacy['active_users'],
            'job_active_monthly' => $legacy['monthly_active'],
            'jobs_per_active' => $legacy['jobs_per_active'],
            'job_retention' => $legacy['retention'],

            // Live presence (reads users.last_seen_at — not summary tables).
            'live_presence' => $presence->snapshot(),

            // Product-usage summaries (login / page / feature analytics).
            ...$productPayload,

            'filters' => [
                'tab' => (string) $request->query('tab', 'live'),
            ],
        ]);
    }
}
