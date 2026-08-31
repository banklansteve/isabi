<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArtisanQuoteRequest;
use App\Mail\QuoteDeliveredClientMail;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Support\ActivityLogger;
use App\Support\Quotes\QuoteBuilderService;
use App\Support\Quotes\QuoteDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class QuoteBuilderController extends Controller
{
    public function __construct(private readonly QuoteBuilderService $quotes) {}

    public function show(Request $request, QuoteRequest $quoteRequest): Response
    {
        abort_unless((int) $quoteRequest->user_id === (int) $request->user()->id, 403);

        $artisan = $request->user();
        $draft = $this->quotes->ensureDraft($quoteRequest, $artisan);

        return Inertia::render('Quotes/Builder', array_merge(
            $this->quotes->presentForPage($quoteRequest, $artisan, $draft),
            [
                'whatsappShare' => $request->session()->pull('whatsapp_share'),
                'openQuoteShare' => (bool) $request->session()->pull('open_quote_share'),
            ],
        ));
    }

    public function update(UpdateArtisanQuoteRequest $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        abort_unless($quoteRequest->isEditableByArtisan(), 403, 'This quote can no longer be edited.');

        $artisan = $request->user();
        $draft = $this->quotes->ensureDraft($quoteRequest, $artisan);
        $this->quotes->syncDraft($draft, $request->validated());

        if ($quoteRequest->status === QuoteRequest::STATUS_NEW) {
            $quoteRequest->forceFill(['status' => QuoteRequest::STATUS_DRAFT])->save();
        }

        ActivityLogger::log(
            action: 'quote.draft_saved',
            summary: "{$artisan->name} saved a quote draft for {$quoteRequest->name}.",
            user: $artisan,
            properties: ['quote_request_uid' => $quoteRequest->uid],
        );

        return redirect()
            ->route('quotes.show', $quoteRequest)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Draft saved',
                'message' => 'Your quote is saved — send it when you’re ready.',
                'duration' => 4200,
            ]);
    }

    public function send(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        abort_unless((int) $quoteRequest->user_id === (int) $request->user()->id, 403);

        if (! in_array($quoteRequest->status, [
            QuoteRequest::STATUS_NEW,
            QuoteRequest::STATUS_DRAFT,
            QuoteRequest::STATUS_AWAITING_CLIENT,
        ], true)) {
            abort(403);
        }

        $artisan = $request->user();
        $draft = $this->quotes->ensureDraft($quoteRequest, $artisan);

        if ($draft->total_kobo <= 0) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Add at least one priced line item before sending.',
                'duration' => 4500,
            ]);
        }

        $draft->forceFill([
            'status' => ArtisanQuote::STATUS_SENT,
            'sent_at' => now(),
        ])->save();

        $quoteRequest->forceFill(['status' => QuoteRequest::STATUS_AWAITING_CLIENT])->save();
        QuoteDelivery::ensureToken($quoteRequest->fresh(['artisanQuote', 'artisan']));

        if (filled($quoteRequest->email)) {
            Mail::to($quoteRequest->email)->send(new QuoteDeliveredClientMail(
                $quoteRequest,
                $draft,
                $artisan,
                QuoteDelivery::publicUrl($quoteRequest),
            ));
        }

        $payload = QuoteDelivery::payload($quoteRequest, $draft);

        ActivityLogger::log(
            action: 'quote.sent',
            summary: "{$artisan->name} sent a quote to {$quoteRequest->name}.",
            user: $artisan,
            properties: [
                'quote_request_uid' => $quoteRequest->uid,
                'total_kobo' => $draft->total_kobo,
            ],
        );

        return redirect()
            ->route('quotes.show', $quoteRequest)
            ->with('whatsapp_share', $payload)
            ->with('open_quote_share', true)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Quote ready to send',
                'message' => 'Share the link on WhatsApp or let the client use the email we sent.',
                'duration' => 5500,
            ]);
    }

    public function revise(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        abort_unless((int) $quoteRequest->user_id === (int) $request->user()->id, 403);
        abort_unless($quoteRequest->status === QuoteRequest::STATUS_AWAITING_CLIENT, 403);

        $quoteRequest->forceFill(['status' => QuoteRequest::STATUS_DRAFT])->save();
        $quoteRequest->artisanQuote?->forceFill(['status' => ArtisanQuote::STATUS_DRAFT])->save();

        return redirect()
            ->route('quotes.show', $quoteRequest)
            ->with('toast', [
                'type' => 'info',
                'title' => 'Quote unlocked',
                'message' => 'Revise your numbers, then send the updated quote.',
                'duration' => 4800,
            ]);
    }
}
