<?php

namespace App\Http\Controllers;

use App\Support\MaintenanceMode;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public const INTENDED_SESSION_KEY = 'maintenance.intended';

    public function show(Request $request, MaintenanceMode $maintenance): Response|RedirectResponse
    {
        if (! $maintenance->enabled()) {
            $intended = $request->session()->pull(self::INTENDED_SESSION_KEY);

            if (is_string($intended) && $intended !== '' && $this->isSafeIntended($intended, $request)) {
                return redirect()->to($intended);
            }

            return redirect()->route('home');
        }

        // Staff / bypass should not linger on the holding page.
        if ($request->user()?->isStaff() || $maintenance->hasValidBypass($request)) {
            $intended = $request->session()->pull(self::INTENDED_SESSION_KEY);

            return redirect()->to(
                (is_string($intended) && $this->isSafeIntended($intended, $request))
                    ? $intended
                    : route('home'),
            );
        }

        app(Seo::class)
            ->title('Maintenance')
            ->description('Kraftrack is temporarily offline for scheduled maintenance.')
            ->noindex();

        return Inertia::render('Errors/Maintenance');
    }

    public static function rememberIntended(Request $request): void
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        $url = $request->fullUrl();

        if (! self::isSafeIntendedStatic($url, $request)) {
            return;
        }

        $request->session()->put(self::INTENDED_SESSION_KEY, $url);
    }

    private function isSafeIntended(string $url, Request $request): bool
    {
        return self::isSafeIntendedStatic($url, $request);
    }

    private static function isSafeIntendedStatic(string $url, Request $request): bool
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && ! str_starts_with($url, $appUrl) && ! str_starts_with($url, $request->root())) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        if (str_starts_with($path, '/maintenance')) {
            return false;
        }

        if (str_starts_with($path, '/admin')) {
            return false;
        }

        return true;
    }
}
