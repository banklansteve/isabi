<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = ActivityLog::query()
            ->with(['user:id,name,email,first_name,last_name'])
            ->latest('created_at');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('summary', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $logs = $query
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
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
                'created_at' => $log->created_at?->toIso8601String(),
                'created_at_human' => $log->created_at?->timezone(config('app.timezone'))->format('j M Y · g:ia'),
                'relative' => $log->created_at?->diffForHumans(),
            ]);

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $logs,
            'filters' => [
                'q' => $search,
                'action' => $action,
            ],
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}
