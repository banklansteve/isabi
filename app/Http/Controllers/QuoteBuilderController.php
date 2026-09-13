<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArtisanQuoteRequest;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Support\ActivityLogger;
use App\Support\Quotes\QuoteBuilderService;
use App\Support\Quotes\QuoteDelivery;
use App\Support\Quotes\QuoteMailer;
use App\Support\Quotes\QuotePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QuoteBuilderController extends Controller
{
    public function __construct(
        private readonly QuoteBuilderService $quotes,
        private readonly QuoteMailer $mailer,
    ) {}

    public function show(Request $request, QuoteRequest $quoteRequest): Response
    {
        abort_unless((int) $quoteRequest->user_id === (int) $request->user()->id, 403);

        $artisan = $request->user();
        $draft = $this->quotes->ensureDraft($quoteRequest, $artisan);

        return Inertia::render('Quotes/Builder', array_merge(
            $this->quotes->presentForPage($quoteRequest, $artisan, $draft),
            [
                'whatsappShare' => $request->session()->pull('whatsapp_share')
                    ?: ($quoteRequest->status === QuoteRequest::STATUS_AWAITING_CLIENT
                        ? QuoteDelivery::payload($quoteRequest->fresh(['artisanQuote', 'artisan']), $draft)
                        : null),
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

    public function send(UpdateArtisanQuoteRequest $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        abort_unless($quoteRequest->isEditableByArtisan()
            || $quoteRequest->status === QuoteRequest::STATUS_AWAITING_CLIENT, 403);

        if (! in_array($quoteRequest->status, [
            QuoteRequest::STATUS_NEW,
            QuoteRequest::STATUS_DRAFT,
            QuoteRequest::STATUS_AWAITING_CLIENT,
        ], true)) {
            abort(403);
        }

        $artisan = $request->user();
        $draft = $this->quotes->ensureDraft($quoteRequest, $artisan);
        $draft = $this->quotes->syncDraft($draft, $request->validated());

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
        $quoteRequest = QuoteDelivery::ensureToken($quoteRequest->fresh(['artisanQuote', 'artisan']));
        $draft = $quoteRequest->artisanQuote;

        $publicUrl = QuoteDelivery::publicUrl($quoteRequest);

        $mailed = $this->mailer->sendQuoteDelivery(
            $quoteRequest,
            $draft,
            $artisan,
            $publicUrl,
        );

        $payload = QuoteDelivery::payload($quoteRequest, $draft);

        ActivityLogger::log(
            action: 'quote.sent',
            summary: "{$artisan->name} sent a quote to {$quoteRequest->name}.",
            user: $artisan,
            properties: [
                'quote_request_uid' => $quoteRequest->uid,
                'quote_number' => $draft->quote_number,
                'total_kobo' => $draft->total_kobo,
                'mailed_client' => $mailed['client'],
                'mailed_artisan' => $mailed['artisan'],
            ],
        );

        if (! filled($quoteRequest->email)) {
            $emailNote = 'No client email on file — share the link on WhatsApp.';
        } elseif ($mailed['client'] && $mailed['artisan']) {
            $emailNote = 'We emailed the client and sent you a confirmation.';
        } elseif ($mailed['client']) {
            $emailNote = 'We emailed the client. Your confirmation email could not be delivered — check spam or SMTP settings.';
        } elseif ($mailed['artisan']) {
            $emailNote = 'We emailed you a confirmation, but the client email failed — share the link on WhatsApp.';
        } else {
            $emailNote = 'Quote is ready, but emails could not be sent. Share the link on WhatsApp and check SMTP settings.';
        }

        return redirect()
            ->route('quotes.show', $quoteRequest)
            ->with('whatsapp_share', $payload)
            ->with('toast', [
                'type' => ($mailed['client'] || ! filled($quoteRequest->email)) ? 'success' : 'warning',
                'title' => 'Quote sent',
                'message' => $emailNote,
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

    public function pdf(Request $request, QuoteRequest $quoteRequest): HttpResponse
    {
        abort_unless((int) $quoteRequest->user_id === (int) $request->user()->id, 403);

        $artisan = $request->user();
        $quote = $this->quotes->ensureDraft($quoteRequest, $artisan);

        $pdf = app(QuotePdfService::class);
        $filename = $pdf->filename($quote, $artisan);

        return response($pdf->output($quoteRequest, $quote, $artisan), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
