<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Support\Quotes\QuotePipelinePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuotePipelineController extends Controller
{
    public function __construct(private readonly QuotePipelinePresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $tab = (string) $request->query('tab', 'needs_response');
        $sort = (string) $request->query('sort', 'newest');

        $query = QuoteRequest::query()
            ->where('user_id', $user->id)
            ->with(['workLog:id,description,reference', 'artisanQuote:id,quote_request_id,total_kobo,quote_number,valid_until,estimated_start']);

        if ($search !== '') {
            $needle = '%'.$search.'%';
            $query->where(function ($builder) use ($needle) {
                $builder->where('name', 'like', $needle)
                    ->orWhere('subject', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('message', 'like', $needle)
                    ->orWhereHas('workLog', fn ($job) => $job->where('description', 'like', $needle));
            });
        }

        match ($tab) {
            'needs_response' => $query->whereIn('status', [
                QuoteRequest::STATUS_NEW,
                QuoteRequest::STATUS_DRAFT,
            ]),
            'awaiting_client' => $query->where('status', QuoteRequest::STATUS_AWAITING_CLIENT),
            'accepted' => $query->where('status', QuoteRequest::STATUS_ACCEPTED),
            default => null,
        };

        [$column, $direction] = match ($sort) {
            'oldest' => ['created_at', 'asc'],
            'name' => ['name', 'asc'],
            default => ['updated_at', 'desc'],
        };

        $quotes = $query
            ->orderBy($column, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (QuoteRequest $row) => $this->presenter->presentListItem($row));

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => [
                'q' => $search,
                'tab' => $tab,
                'sort' => $sort,
            ],
            'counts' => $this->presenter->tabCounts($user->id),
        ]);
    }
}
