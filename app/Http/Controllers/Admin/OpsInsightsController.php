<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterOpsInsightsRequest;
use App\Support\Admin\OpsInsightsService;
use App\Support\Staff\StaffPresence;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class OpsInsightsController extends Controller
{
    public function __construct(
        private readonly OpsInsightsService $insights,
        private readonly StaffPresence $presence,
    ) {}

    public function index(FilterOpsInsightsRequest $request): Response
    {
        $this->presence->touch($request->user());

        $payload = $this->insights->payload($request->user(), $request->filters());

        return Inertia::render('Admin/Insights/Index', $payload);
    }

    public function live(FilterOpsInsightsRequest $request): JsonResponse
    {
        return response()->json($this->insights->live($request->user()));
    }
}
