<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Support\Quotes\QuoteListPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteRequestIndexController extends Controller
{
    public function __construct(private readonly QuoteListPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'newest');

        $query = QuoteRequest::query()
            ->where('user_id', $user->id)
            ->with([
                'workLog:id,description,reference,uid',
                'artisanQuote:id,quote_request_id,status,total_kobo,quote_number,uid',
            ]);

        if ($search !== '') {
            $needle = '%'.$search.'%';
            $query->where(function ($builder) use ($needle) {
                $builder->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('message', 'like', $needle)
                    ->orWhereHas('workLog', fn ($job) => $job->where('description', 'like', $needle));
            });
        }

        if ($status !== 'all' && in_array($status, [
            QuoteRequest::STATUS_NEW,
            QuoteRequest::STATUS_CONTACTED,
            QuoteRequest::STATUS_CLOSED,
        ], true)) {
            $query->where('status', $status);
        }

        [$column, $direction] = match ($sort) {
            'oldest' => ['created_at', 'asc'],
            'name' => ['name', 'asc'],
            default => ['created_at', 'desc'],
        };

        $requests = $query
            ->orderBy($column, $direction)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (QuoteRequest $row) => $this->presenter->presentRequest($row));

        return Inertia::render('Quotes/Requests/Index', [
            'requests' => $requests,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
            'counts' => $this->presenter->requestCounts($user->id),
            'quoteCounts' => $this->presenter->quoteCounts($user->id),
        ]);
    }
}
