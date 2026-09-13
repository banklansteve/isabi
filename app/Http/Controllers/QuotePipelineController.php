<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexQuotePipelineRequest;
use App\Models\QuoteRequest;
use App\Support\Quotes\QuotePipelinePresenter;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class QuotePipelineController extends Controller
{
    public function __construct(private readonly QuotePipelinePresenter $presenter) {}

    public function index(IndexQuotePipelineRequest $request): Response
    {
        $user = $request->user();
        $filters = $request->filters();

        $query = QuoteRequest::query()
            ->where('quote_requests.user_id', $user->id)
            ->with([
                'workLog:id,description,reference',
                'artisanQuote:id,quote_request_id,total_kobo,quote_number,valid_until,estimated_start,sent_at',
            ]);

        $this->applySearch($query, $filters['q']);
        $this->applyStatusScope($query, $filters);
        $this->applyFocus($query, $filters['focus']);
        $this->applySort($query, $filters['sort']);

        $quotes = $query
            ->paginate(12)
            ->withQueryString()
            ->through(fn (QuoteRequest $row) => $this->presenter->presentListItem($row));

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => $filters,
            'counts' => $this->presenter->tabCounts($user->id),
            'filterOptions' => $this->presenter->filterOptions(),
        ]);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $needle = '%'.$search.'%';

        $query->where(function (Builder $builder) use ($needle) {
            $builder->where('quote_requests.name', 'like', $needle)
                ->orWhere('quote_requests.subject', 'like', $needle)
                ->orWhere('quote_requests.email', 'like', $needle)
                ->orWhere('quote_requests.phone', 'like', $needle)
                ->orWhere('quote_requests.message', 'like', $needle)
                ->orWhereHas('workLog', fn (Builder $job) => $job->where('description', 'like', $needle))
                ->orWhereHas('artisanQuote', function (Builder $quote) use ($needle) {
                    $quote->where('quote_number', 'like', $needle);
                });
        });
    }

    /**
     * @param  array{tab: string, status: string}  $filters
     */
    private function applyStatusScope(Builder $query, array $filters): void
    {
        if ($filters['status'] !== '') {
            $query->where('quote_requests.status', $filters['status']);

            return;
        }

        match ($filters['tab']) {
            'needs_response' => $query->whereIn('quote_requests.status', [
                QuoteRequest::STATUS_NEW,
                QuoteRequest::STATUS_DRAFT,
            ]),
            'awaiting_client' => $query->where('quote_requests.status', QuoteRequest::STATUS_AWAITING_CLIENT),
            'accepted' => $query->where('quote_requests.status', QuoteRequest::STATUS_ACCEPTED),
            'closed' => $query->whereIn('quote_requests.status', [
                QuoteRequest::STATUS_DECLINED,
                QuoteRequest::STATUS_EXPIRED,
            ]),
            default => null,
        };
    }

    private function applyFocus(Builder $query, string $focus): void
    {
        if ($focus === 'ready_to_log') {
            $query->where('quote_requests.status', QuoteRequest::STATUS_ACCEPTED)
                ->whereNull('quote_requests.logged_work_log_id')
                ->where(function (Builder $builder) {
                    $builder->where('quote_requests.accepted_at', '<=', now()->subDays(3))
                        ->orWhereHas('artisanQuote', function (Builder $quote) {
                            $quote->whereNotNull('estimated_start')
                                ->where('estimated_start', '<=', now());
                        });
                });

            return;
        }

        if ($focus === 'expiring') {
            $query->where('quote_requests.status', QuoteRequest::STATUS_AWAITING_CLIENT)
                ->whereHas('artisanQuote', function (Builder $quote) {
                    $quote->whereNotNull('valid_until')
                        ->whereDate('valid_until', '>=', now()->toDateString())
                        ->whereDate('valid_until', '<=', now()->addDays(3)->toDateString());
                });
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        $needsJoin = in_array($sort, ['amount_high', 'amount_low', 'expiry'], true);

        if ($needsJoin) {
            $query->leftJoin('artisan_quotes', 'artisan_quotes.quote_request_id', '=', 'quote_requests.id')
                ->select('quote_requests.*');
        }

        match ($sort) {
            'oldest' => $query->orderBy('quote_requests.created_at', 'asc'),
            'name' => $query->orderBy('quote_requests.name', 'asc'),
            'amount_high' => $query->orderByRaw('artisan_quotes.total_kobo IS NULL')
                ->orderByDesc('artisan_quotes.total_kobo'),
            'amount_low' => $query->orderByRaw('artisan_quotes.total_kobo IS NULL')
                ->orderBy('artisan_quotes.total_kobo'),
            'expiry' => $query->orderByRaw('artisan_quotes.valid_until IS NULL')
                ->orderBy('artisan_quotes.valid_until'),
            default => $query->orderByDesc('quote_requests.updated_at'),
        };
    }
}
