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
        $request->loadMissing([
            'workLog:id,description,reference',
            'artisanQuote:id,quote_request_id,total_kobo,quote_number,valid_until,estimated_start,sent_at',
        ]);

        $quote = $request->artisanQuote;
        $tz = config('app.display_timezone', config('app.timezone'));
        $total = $quote?->total_kobo;

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
                ?->timezone($tz)
                ->diffForHumans(),
            'updated_relative' => $request->updated_at
                ?->timezone($tz)
                ->diffForHumans(),
            'quote_number' => $quote?->quote_number,
            'total_naira' => $total !== null ? $total / 100 : null,
            'total_label' => $total !== null ? '₦'.number_format($total / 100, 0) : null,
            'valid_until_label' => $quote?->valid_until
                ?->timezone($tz)
                ->format('j M Y'),
            'estimated_start_label' => $quote?->estimated_start
                ?->timezone($tz)
                ->format('j M Y'),
            'expiring_soon' => $request->status === QuoteRequest::STATUS_AWAITING_CLIENT
                && $quote?->valid_until
                && $quote->valid_until->copy()->startOfDay()->gte(now()->startOfDay())
                && $quote->valid_until->copy()->startOfDay()->lte(now()->addDays(3)->startOfDay()),
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
        $declined = (int) ($rows[QuoteRequest::STATUS_DECLINED] ?? 0);
        $expired = (int) ($rows[QuoteRequest::STATUS_EXPIRED] ?? 0);

        $readyToLog = QuoteRequest::query()
            ->where('user_id', $userId)
            ->where('status', QuoteRequest::STATUS_ACCEPTED)
            ->whereNull('logged_work_log_id')
            ->where(function ($builder) {
                $builder->where('accepted_at', '<=', now()->subDays(3))
                    ->orWhereHas('artisanQuote', function ($quote) {
                        $quote->whereNotNull('estimated_start')
                            ->where('estimated_start', '<=', now());
                    });
            })
            ->count();

        $expiring = QuoteRequest::query()
            ->where('user_id', $userId)
            ->where('status', QuoteRequest::STATUS_AWAITING_CLIENT)
            ->whereHas('artisanQuote', function ($quote) {
                $quote->whereNotNull('valid_until')
                    ->whereDate('valid_until', '>=', now()->toDateString())
                    ->whereDate('valid_until', '<=', now()->addDays(3)->toDateString());
            })
            ->count();

        return [
            'needs_response' => $new + $draft,
            'awaiting_client' => (int) ($rows[QuoteRequest::STATUS_AWAITING_CLIENT] ?? 0),
            'accepted' => (int) ($rows[QuoteRequest::STATUS_ACCEPTED] ?? 0),
            'closed' => $declined + $expired,
            'all' => (int) $rows->sum(),
            'ready_to_log' => $readyToLog,
            'expiring' => $expiring,
        ];
    }

    /**
     * @return array{
     *     statuses: list<array{value: string, label: string}>,
     *     sorts: list<array{value: string, label: string}>
     * }
     */
    public function filterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => '', 'label' => 'Any status'],
                ['value' => QuoteRequest::STATUS_NEW, 'label' => 'New request'],
                ['value' => QuoteRequest::STATUS_DRAFT, 'label' => 'Draft'],
                ['value' => QuoteRequest::STATUS_AWAITING_CLIENT, 'label' => 'Awaiting client'],
                ['value' => QuoteRequest::STATUS_ACCEPTED, 'label' => 'Accepted'],
                ['value' => QuoteRequest::STATUS_DECLINED, 'label' => 'Declined'],
                ['value' => QuoteRequest::STATUS_EXPIRED, 'label' => 'Expired'],
            ],
            'sorts' => [
                ['value' => 'newest', 'label' => 'Newest first'],
                ['value' => 'oldest', 'label' => 'Oldest first'],
                ['value' => 'name', 'label' => 'Client name'],
                ['value' => 'amount_high', 'label' => 'Highest amount'],
                ['value' => 'amount_low', 'label' => 'Lowest amount'],
                ['value' => 'expiry', 'label' => 'Expiring soonest'],
            ],
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
