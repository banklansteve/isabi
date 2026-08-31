<?php

namespace App\Http\Controllers;

use App\Models\ArtisanQuote;
use App\Support\Quotes\QuoteListPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArtisanQuoteIndexController extends Controller
{
    public function __construct(private readonly QuoteListPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'newest');

        $query = ArtisanQuote::query()
            ->where('user_id', $user->id)
            ->with(['quoteRequest.workLog:id,description,reference']);

        if ($search !== '') {
            $needle = '%'.$search.'%';
            $query->where(function ($builder) use ($needle) {
                $builder->where('quote_number', 'like', $needle)
                    ->orWhereHas('quoteRequest', function ($requestQuery) use ($needle) {
                        $requestQuery->where('name', 'like', $needle)
                            ->orWhere('email', 'like', $needle)
                            ->orWhere('phone', 'like', $needle);
                    })
                    ->orWhereHas('quoteRequest.workLog', fn ($job) => $job->where('description', 'like', $needle));
            });
        }

        if ($status === ArtisanQuote::STATUS_DRAFT || $status === ArtisanQuote::STATUS_SENT) {
            $query->where('status', $status);
        }

        $query->orderBy(match ($sort) {
            'oldest' => 'created_at',
            'total_high' => 'total_kobo',
            'total_low' => 'total_kobo',
            default => 'updated_at',
        }, match ($sort) {
            'oldest', 'total_low' => 'asc',
            default => 'desc',
        });

        $quotes = $query
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ArtisanQuote $row) => $this->presenter->presentArtisanQuote($row));

        return Inertia::render('Quotes/Sent/Index', [
            'quotes' => $quotes,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
            'counts' => $this->presenter->quoteCounts($user->id),
            'requestCounts' => $this->presenter->requestCounts($user->id),
        ]);
    }
}
