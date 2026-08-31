<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use Illuminate\Support\Str;

class QuoteListPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function presentRequest(QuoteRequest $request): array
    {
        $request->loadMissing(['workLog:id,description,reference,uid', 'artisanQuote:id,quote_request_id,status,total_kobo,quote_number,uid']);

        $quote = $request->artisanQuote;

        return [
            'uid' => $request->uid,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'message' => $request->message ? Str::limit($request->message, 140) : null,
            'status' => $request->status,
            'status_label' => $this->requestStatusLabel($request->status, $quote?->status),
            'submitted_at' => $request->created_at
                ?->timezone(config('app.display_timezone'))
                ->format('j M Y · g:i A'),
            'submitted_relative' => $request->created_at
                ?->timezone(config('app.display_timezone'))
                ->diffForHumans(),
            'job' => $request->workLog ? [
                'description' => $request->workLog->description,
                'reference' => $request->workLog->reference,
            ] : null,
            'quote' => $quote ? [
                'uid' => $quote->uid,
                'quote_number' => $quote->quote_number,
                'status' => $quote->status,
                'total_naira' => $quote->total_kobo / 100,
            ] : null,
            'builder_url' => route('quotes.requests.show', $request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentArtisanQuote(ArtisanQuote $quote): array
    {
        $quote->loadMissing(['quoteRequest.workLog:id,description,reference']);

        $request = $quote->quoteRequest;

        return [
            'uid' => $quote->uid,
            'quote_number' => $quote->quote_number,
            'status' => $quote->status,
            'status_label' => $quote->status === ArtisanQuote::STATUS_SENT ? 'Ready to share' : 'Draft',
            'total_naira' => $quote->total_kobo / 100,
            'valid_until' => $quote->valid_until
                ?->timezone(config('app.display_timezone'))
                ->format('j M Y'),
            'sent_at_label' => $quote->sent_at
                ?->timezone(config('app.display_timezone'))
                ->format('j M Y · g:i A'),
            'updated_relative' => $quote->updated_at
                ?->timezone(config('app.display_timezone'))
                ->diffForHumans(),
            'client' => $request ? [
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
            ] : null,
            'job' => $request?->workLog ? [
                'description' => $request->workLog->description,
                'reference' => $request->workLog->reference,
            ] : null,
            'request_uid' => $request?->uid,
            'builder_url' => $request ? route('quotes.requests.show', $request) : null,
        ];
    }

    /**
     * @return array{new: int, contacted: int, closed: int, total: int}
     */
    public function requestCounts(int $userId): array
    {
        $rows = QuoteRequest::query()
            ->where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'new' => (int) ($rows[QuoteRequest::STATUS_NEW] ?? 0),
            'contacted' => (int) ($rows[QuoteRequest::STATUS_CONTACTED] ?? 0),
            'closed' => (int) ($rows[QuoteRequest::STATUS_CLOSED] ?? 0),
            'total' => (int) $rows->sum(),
        ];
    }

    /**
     * @return array{draft: int, sent: int, total: int}
     */
    public function quoteCounts(int $userId): array
    {
        $rows = ArtisanQuote::query()
            ->where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'draft' => (int) ($rows[ArtisanQuote::STATUS_DRAFT] ?? 0),
            'sent' => (int) ($rows[ArtisanQuote::STATUS_SENT] ?? 0),
            'total' => (int) $rows->sum(),
        ];
    }

    private function requestStatusLabel(string $status, ?string $quoteStatus): string
    {
        if ($quoteStatus === ArtisanQuote::STATUS_SENT) {
            return 'Quote ready';
        }

        return match ($status) {
            QuoteRequest::STATUS_NEW => 'New',
            QuoteRequest::STATUS_CONTACTED => 'In progress',
            QuoteRequest::STATUS_CLOSED => 'Closed',
            default => ucfirst($status),
        };
    }
}
