<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteResponseRequest;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Support\Quotes\QuoteBuilderService;
use App\Support\Quotes\QuoteDelivery;
use App\Support\Quotes\QuoteMailer;
use App\Support\Quotes\QuotePdfService;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicQuoteController extends Controller
{
    public function __construct(private readonly QuoteBuilderService $quotes) {}

    public function show(string $token): Response|RedirectResponse
    {
        $request = QuoteRequest::query()
            ->where('client_token', $token)
            ->with(['artisan', 'artisanQuote', 'workLog'])
            ->firstOrFail();

        if ($request->client_token_expires_at?->isPast() || $request->isOfferExpired()) {
            $request->refreshExpiry();

            return Inertia::render('Public/QuoteExpired', [
                'businessName' => $request->artisan?->displayBusinessName(),
            ]);
        }

        $request->refreshExpiry();

        if ($request->status === QuoteRequest::STATUS_EXPIRED) {
            return Inertia::render('Public/QuoteExpired', [
                'businessName' => $request->artisan?->displayBusinessName(),
            ]);
        }

        app(Seo::class)
            ->title('Quote from '.$request->artisan?->displayBusinessName())
            ->noindex();

        return Inertia::render('Public/Quote', $this->quotes->presentPublicPage($request));
    }

    public function downloadPage(string $token): IlluminateResponse|RedirectResponse
    {
        $request = QuoteRequest::query()
            ->where('client_token', $token)
            ->with(['artisan', 'artisanQuote'])
            ->firstOrFail();

        if ($request->client_token_expires_at?->isPast() || $request->isOfferExpired()) {
            $request->refreshExpiry();

            return redirect()->route('quotes.public.show', $token);
        }

        $quote = $request->artisanQuote;
        $artisan = $request->artisan;

        abort_unless($quote && $artisan, 404);

        return response()
            ->view('public.quote-pdf-download', [
                'appName' => config('app.name', 'Kraftrack'),
                'businessName' => $artisan->displayBusinessName(),
                'quoteNumber' => $quote->quote_number,
                'quoteUrl' => QuoteDelivery::publicUrl($request),
                'pdfUrl' => QuoteDelivery::pdfFileUrl($request),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function pdf(string $token): HttpResponse
    {
        $request = QuoteRequest::query()
            ->where('client_token', $token)
            ->with(['artisan', 'artisanQuote'])
            ->firstOrFail();

        if ($request->client_token_expires_at?->isPast() || $request->isOfferExpired()) {
            $request->refreshExpiry();
            abort(410, 'This quote link has expired.');
        }

        $quote = $request->artisanQuote;
        $artisan = $request->artisan;

        abort_unless($quote && $artisan, 404);

        return app(QuotePdfService::class)->download($request, $quote, $artisan, attachment: true);
    }

    public function respond(StoreQuoteResponseRequest $form, string $token): RedirectResponse
    {
        $request = QuoteRequest::query()
            ->where('client_token', $token)
            ->with(['artisanQuote', 'artisan'])
            ->firstOrFail();

        if ($request->client_token_expires_at?->isPast() || $request->isOfferExpired()) {
            $request->refreshExpiry();
            abort(410);
        }

        $request->refreshExpiry();

        if ($request->status !== QuoteRequest::STATUS_AWAITING_CLIENT) {
            return redirect()
                ->route('quotes.public.show', $token)
                ->with('toast', [
                    'type' => 'info',
                    'message' => 'This quote has already been answered.',
                    'duration' => 4500,
                ]);
        }

        $decision = $form->validated()['decision'];
        $message = trim((string) ($form->validated()['message'] ?? ''));
        $message = $message !== '' ? $message : null;

        $status = match ($decision) {
            'accepted' => QuoteRequest::STATUS_ACCEPTED,
            'adjustments' => QuoteRequest::STATUS_ADJUSTMENTS_REQUESTED,
            default => QuoteRequest::STATUS_DECLINED,
        };

        $request->forceFill([
            'status' => $status,
            'client_response' => $message,
            'client_responded_at' => now(),
            'accepted_at' => $decision === 'accepted' ? now() : null,
        ])->save();

        if ($decision === 'adjustments') {
            $request->artisanQuote?->forceFill([
                'status' => ArtisanQuote::STATUS_DRAFT,
            ])->save();
        }

        app(QuoteMailer::class)->notifyArtisanOfClientResponse(
            $request->fresh(['artisanQuote', 'artisan']),
            $decision,
        );

        return redirect()
            ->route('quotes.public.thanks', $token)
            ->with('decision', $decision);
    }

    public function thanks(Request $request, string $token): Response
    {
        $quoteRequest = QuoteRequest::query()
            ->where('client_token', $token)
            ->with('artisan')
            ->firstOrFail();

        app(Seo::class)->title('Thanks')->noindex();

        return Inertia::render('Public/QuoteThanks', [
            'businessName' => $quoteRequest->artisan?->displayBusinessName(),
            'decision' => $request->session()->get('decision', $quoteRequest->status),
            'clientName' => $quoteRequest->name,
        ]);
    }
}
