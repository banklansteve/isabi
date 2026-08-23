<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __invoke(): Response
    {
        $logs = ActivityLog::query()
            ->with(['user:id,name,email,first_name,last_name'])
            ->latest('created_at')
            ->limit(2000)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'title' => $log->titleFromAction(),
                'summary' => $log->summary,
                'icon' => $log->icon(),
                'user' => $log->user
                    ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ]
                    : null,
                'ip_address' => $log->ip_address,
                'created_iso' => $log->created_at?->toIso8601String(),
                'created_at_human' => $log->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'relative' => $log->created_at?->diffForHumans(),
            ])
            ->values();

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $logs,
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}
