<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Support\Admin\AdminAudit;
use App\Support\MaintenanceMode;
use App\Support\Staff\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request, AppSettingsService $settings, MaintenanceMode $maintenance): Response
    {
        $rows = collect($settings->allForAdmin())
            ->reject(fn (array $row) => str_starts_with($row['key'], 'maintenance.'))
            ->map(function (array $row) {
                if (str_contains($row['key'], 'secret') && filled($row['value'])) {
                    $row['value'] = str($row['value'])->mask('*', 0, max(0, strlen((string) $row['value']) - 4))->toString();
                    $row['masked'] = true;
                }

                return $row;
            });

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $rows->groupBy('group')->map(fn ($group) => $group->values()->all())->all(),
            'maintenance' => $maintenance->statusForAdmin($request->user()),
            'filters' => [
                'tab' => (string) $request->query('tab', 'general'),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request, AppSettingsService $settings): RedirectResponse
    {
        $before = collect($settings->allForAdmin())->mapWithKeys(fn (array $row) => [$row['key'] => $row['value']])->all();

        $incoming = $request->validated('settings');

        foreach ($incoming as $key => $value) {
            if (str_contains((string) $key, 'secret') && is_string($value) && str_contains($value, '*')) {
                unset($incoming[$key]);
            }
        }

        $readonlyKeys = collect(config('admin.settings', []))
            ->filter(fn (array $row) => (bool) ($row['readonly'] ?? false))
            ->pluck('key')
            ->all();

        foreach ($readonlyKeys as $key) {
            unset($incoming[$key]);
        }

        if ($incoming === []) {
            return back()->with('toast', [
                'type' => 'info',
                'title' => 'Nothing to save',
                'message' => 'No editable settings were submitted.',
                'duration' => 4200,
            ]);
        }

        $settings->updateMany($incoming, $request->user());

        $after = collect($settings->allForAdmin())->mapWithKeys(fn (array $row) => [$row['key'] => $row['value']])->all();

        AdminAudit::record(
            'settings.updated',
            "{$request->user()->name} updated application settings.",
            null,
            array_intersect_key($before, $incoming),
            $incoming,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Settings saved',
            'message' => 'Application settings are now live.',
            'duration' => 4200,
        ]);
    }
}
