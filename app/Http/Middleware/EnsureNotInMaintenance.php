<?php

namespace App\Http\Middleware;

use App\Http\Controllers\MaintenanceController;
use App\Support\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInMaintenance
{
    public function __construct(
        private readonly MaintenanceMode $maintenance,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenance->enabled()) {
            return $next($request);
        }

        if ($this->maintenance->pathIsAllowed($request->path())) {
            return $next($request);
        }

        $user = $request->user();
        if ($user?->isStaff()) {
            return $next($request);
        }

        if ($this->maintenance->hasValidBypass($request)) {
            return $next($request);
        }

        // Soft block only — never logout or flush session.
        // Use a normal redirect to a 200 holding page so Inertia does not
        // treat a 503 as a fatal “Laravel error” overlay. When maintenance
        // ends, refreshing /maintenance returns the user to their intended URL.
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Kraftrack is temporarily offline for maintenance. Please try again shortly.',
            ], 503)->header('Retry-After', '3600');
        }

        MaintenanceController::rememberIntended($request);

        return redirect()
            ->route('maintenance.show')
            ->header('Retry-After', '3600');
    }
}
