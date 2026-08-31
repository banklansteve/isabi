<?php

namespace App\Support\Quotes;

use App\Models\QuoteRequest;

class QuotePipelinePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function presentListItem(QuoteRequest $request): array
    {
        $request->refreshExpiry();
        $request->loadMissing(['workLog:id,description,reference', 'artisanQuote:id,quote_request_id,total_kobo,quote_number,valid_until,estimated_start']);

        $quote = $request->artisanQuote;

        return [
            'uid' => $request->uid,
            'name' => $request->name,
            'subject' => $request->displayTitle(),
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => $request->status,
            'status_label' => $this->statusLabel($request->status),
            'status_tone' => $this->statusTone($request->status),
            'submitted_relative' => $request->created_at
                ?->timezone(config('app.display_timezone'))
                ->diffForHumans(),
            'total_naira' => $quote?->total_kobo ? $quote->total_kobo / 100 : null,
            'show_url' => route('quotes.show', $request),
            'should_log_job' => $request->shouldPromptLogJob(),
            'log_job_url' => route('work-log.create', ['quote' => $request->uid]),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function tabCounts(int $userId): array
    {
        $rows = QuoteRequest::query()
            ->where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $new = (int) ($rows[QuoteRequest::STATUS_NEW] ?? 0);
        $draft = (int) ($rows[QuoteRequest::STATUS_DRAFT] ?? 0);

        return [
            'needs_response' => $new + $draft,
            'awaiting_client' => (int) ($rows[QuoteRequest::STATUS_AWAITING_CLIENT] ?? 0),
            'accepted' => (int) ($rows[QuoteRequest::STATUS_ACCEPTED] ?? 0),
            'all' => (int) $rows->sum(),
        ];
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            QuoteRequest::STATUS_NEW => 'New request',
            QuoteRequest::STATUS_DRAFT => 'Draft',
            QuoteRequest::STATUS_AWAITING_CLIENT => 'Awaiting client',
            QuoteRequest::STATUS_ACCEPTED => 'Accepted',
            QuoteRequest::STATUS_DECLINED => 'Declined',
            QuoteRequest::STATUS_EXPIRED => 'Expired',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function statusTone(string $status): string
    {
        return match ($status) {
            QuoteRequest::STATUS_NEW => 'rose',
            QuoteRequest::STATUS_DRAFT => 'blue',
            QuoteRequest::STATUS_AWAITING_CLIENT => 'amber',
            QuoteRequest::STATUS_ACCEPTED => 'emerald',
            QuoteRequest::STATUS_DECLINED => 'slate',
            QuoteRequest::STATUS_EXPIRED => 'slate',
            default => 'slate',
        };
    }
}
