<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        $logs = AdminAuditLog::query()
            ->with(['actor:id,name,email'])
            ->latest('id')
            ->limit(2000)
            ->get()
            ->map(fn (AdminAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'summary' => $log->summary,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
                'when' => $log->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'relative' => $log->created_at?->diffForHumans(),
                'created_iso' => $log->created_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Admin/Audit/Index', [
            'logs' => $logs,
            'actions' => AdminAuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
