<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AdminAudit;
use App\Support\MaintenanceMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaintenanceModeController extends Controller
{
    public function enable(Request $request, MaintenanceMode $maintenance): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'backup_confirmed' => ['accepted'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'backup_confirmed.accepted' => 'Confirm that a database backup or snapshot has been taken before enabling maintenance.',
        ]);

        if ($maintenance->enabled()) {
            return back()->with('toast', [
                'type' => 'info',
                'title' => 'Already offline',
                'message' => 'Maintenance mode is already on.',
                'duration' => 4200,
            ]);
        }

        $secret = $maintenance->enable($request->user(), $data['note'] ?? null);

        AdminAudit::record(
            'maintenance.enabled',
            "{$request->user()->name} enabled maintenance mode after confirming a backup.",
            null,
            null,
            [
                'backup_confirmed' => true,
                'note' => $data['note'] ?? null,
                'secret_suffix' => substr($secret, -4),
            ],
            $request->user(),
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Maintenance on',
            'message' => 'Public and artisan pages are offline. Staff can still work. Use the bypass URL to preview public pages.',
            'duration' => 6200,
        ]);
    }

    public function disable(Request $request, MaintenanceMode $maintenance): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        if (! $maintenance->enabled()) {
            return back()->with('toast', [
                'type' => 'info',
                'title' => 'Already live',
                'message' => 'Maintenance mode is already off.',
                'duration' => 4200,
            ]);
        }

        $maintenance->disable($request->user());

        AdminAudit::record(
            'maintenance.disabled',
            "{$request->user()->name} disabled maintenance mode.",
            null,
            null,
            null,
            $request->user(),
        );

        return back()
            ->withCookie($maintenance->forgetBypassCookie())
            ->with('toast', [
                'type' => 'success',
                'title' => 'Site live again',
                'message' => 'Artisans and guests can continue from where they left off.',
                'duration' => 5200,
            ]);
    }

    public function regenerate(Request $request, MaintenanceMode $maintenance): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        if (! $maintenance->enabled()) {
            throw ValidationException::withMessages([
                'maintenance' => 'Turn on maintenance mode before regenerating the bypass secret.',
            ]);
        }

        $secret = $maintenance->regenerateSecret($request->user());

        AdminAudit::record(
            'maintenance.secret_rotated',
            "{$request->user()->name} regenerated the maintenance bypass secret.",
            null,
            null,
            ['secret_suffix' => substr($secret, -4)],
            $request->user(),
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Bypass link refreshed',
            'message' => 'Previous bypass cookies no longer work. Copy the new URL below.',
            'duration' => 5600,
        ]);
    }

    private function assertSuperAdmin(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403);
        }
    }
}
