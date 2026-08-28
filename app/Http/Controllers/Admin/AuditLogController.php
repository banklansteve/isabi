<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterAdminAuditRequest;
use App\Models\AdminAuditLog;
use App\Support\Admin\AdminAuditPresenter;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(FilterAdminAuditRequest $request): Response
    {
        $filters = $request->filters();
        $tz = (string) config('app.display_timezone', 'Africa/Lagos');

        $query = AdminAuditLog::query()
            ->with(['actor:id,name,email,first_name,last_name'])
            ->latest('id');

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('summary', 'like', '%'.$term.'%')
                    ->orWhereHas('actor', function ($actor) use ($term) {
                        $like = '%'.$term.'%';
                        $actor->where(function ($inner) use ($like) {
                            $inner->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like);
                        });
                    });
            });
        }

        if ($filters['action'] !== '') {
            $query->where('action', $filters['action']);
        }

        $from = Carbon::parse($filters['from'], $tz)->startOfDay()->utc();
        $to = Carbon::parse($filters['to'], $tz)->endOfDay()->utc();
        $query->whereBetween('created_at', [$from, $to]);

        $paginator = $query
            ->paginate(25)
            ->withQueryString();

        $actions = AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action) => [
                'value' => $action,
                'label' => AdminAuditPresenter::actionLabel($action),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return Inertia::render('Admin/Audit/Index', [
            'logs' => [
                'data' => collect($paginator->items())
                    ->map(fn (AdminAuditLog $log) => AdminAuditPresenter::row($log))
                    ->values(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => $filters,
            'actions' => $actions,
        ]);
    }
}
