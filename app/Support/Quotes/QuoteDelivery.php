<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Support\ReviewInvite;

class QuoteDelivery
{
    public static function ensureToken(QuoteRequest $request, bool $forceNew = false): QuoteRequest
    {
        $request->loadMissing('artisanQuote');
        $days = (int) config('profiles.quote_token_days', 30);
        $expired = $request->client_token_expires_at
            && $request->client_token_expires_at->isPast();

        $needsNew = $forceNew
            || blank($request->client_token)
            || $expired
            || ! QuoteReference::isValid($request->client_token);

        if ($needsNew) {
            $quote = $request->artisanQuote;
            $id = $quote && QuoteReference::isValid($quote->quote_number)
                ? $quote->quote_number
                : QuoteReference::unique($quote?->id, $request->id);

            if ($quote && $quote->quote_number !== $id) {
                $quote->forceFill(['quote_number' => $id])->save();
            }

            $request->client_token = $id;
        }

        // Link lifetime follows the quote's valid-until day when set.
        $validUntil = $request->artisanQuote?->valid_until;
        $request->client_token_expires_at = $validUntil
            ? $validUntil->copy()->endOfDay()
            : now()->addDays(max(1, $days));
        $request->expiry_nudge_sent_at = null;
        $request->save();

        return $request->fresh(['artisanQuote', 'artisan']);
    }

    public static function publicUrl(QuoteRequest $request): string
    {
        return route('quotes.public.show', ['token' => $request->client_token], absolute: true);
    }

    public static function pdfUrl(QuoteRequest $request): string
    {
        return route('quotes.public.pdf', [
            'token' => $request->client_token,
            'download' => 1,
        ], absolute: true);
    }

    public static function message(QuoteRequest $request, ArtisanQuote $quote): string
    {
        $request->loadMissing('artisan');
        $artisan = $request->artisan;
        $business = $artisan?->displayBusinessName() ?? 'your artisan';
        $title = $request->displayTitle();
        $total = number_format($quote->total_kobo / 100, 0);
        $link = filled($request->client_token) ? self::publicUrl($request) : 'https://kraftrack.com/q/…';

        $greeting = trim($request->name) !== '' ? "Hi {$request->name}," : 'Hi,';

        return implode("\n", array_filter([
            $greeting,
            '',
            "{$business} has sent you a quote for “{$title}”.",
            "Quote {$quote->quote_number}",
            "Total: ₦{$total}",
            '',
            'Open the link below to review the full breakdown and accept or decline — no account needed.',
            '',
            $link,
            '',
            'PDF copy: '.(filled($request->client_token) ? self::pdfUrl($request) : $link.'/pdf'),
            '',
            'Thank you,',
            $business,
        ]));
    }

    public static function whatsappAppUrl(QuoteRequest $request, ArtisanQuote $quote): string
    {
        $encoded = rawurlencode(self::message($request, $quote));
        $phone = ReviewInvite::normalizeWhatsapp($request->phone);

        if ($phone !== null) {
            return "https://wa.me/{$phone}?text={$encoded}";
        }

        return "https://wa.me/?text={$encoded}";
    }

    public static function whatsappWebUrl(QuoteRequest $request, ArtisanQuote $quote): string
    {
        $encoded = rawurlencode(self::message($request, $quote));
        $phone = ReviewInvite::normalizeWhatsapp($request->phone);

        if ($phone !== null) {
            return "https://web.whatsapp.com/send?phone={$phone}&text={$encoded}";
        }

        return "https://api.whatsapp.com/send?text={$encoded}";
    }

    /**
     * @return array{
     *     quote_url: string,
     *     pdf_url: string,
     *     whatsapp_app_url: string,
     *     whatsapp_web_url: string,
     *     message: string
     * }|null
     */
    public static function payload(QuoteRequest $request, ArtisanQuote $quote): ?array
    {
        if (blank($request->client_token)) {
            return null;
        }

        return [
            'quote_url' => self::publicUrl($request),
            'pdf_url' => self::pdfUrl($request),
            'whatsapp_app_url' => self::whatsappAppUrl($request, $quote),
            'whatsapp_web_url' => self::whatsappWebUrl($request, $quote),
            'message' => self::message($request, $quote),
        ];
    }
}
