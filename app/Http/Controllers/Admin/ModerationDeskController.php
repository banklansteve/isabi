<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\ModerationDesk\ModerationDeskService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationDeskController extends Controller
{
    public function __construct(
        private readonly ModerationDeskService $desk,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($this->desk->canAccess($request->user()), 403);

        $filter = (string) $request->query('filter', ModerationDeskService::FILTER_ALL);
        $query = (string) $request->query('q', '');

        return Inertia::render('Admin/ModerationDesk/Index', [
            ...$this->desk->page($request->user(), $filter, $query),
            'filters' => [
                ['key' => ModerationDeskService::FILTER_ALL, 'label' => 'All'],
                ['key' => ModerationDeskService::FILTER_JOBS, 'label' => 'Jobs'],
                ['key' => ModerationDeskService::FILTER_REVIEWS, 'label' => 'Reviews'],
                ['key' => ModerationDeskService::FILTER_PATROL, 'label' => 'Patrol'],
                ['key' => ModerationDeskService::FILTER_REFERRALS, 'label' => 'Referrals'],
                ['key' => ModerationDeskService::FILTER_USERS, 'label' => 'Users'],
            ],
        ]);
    }
}
