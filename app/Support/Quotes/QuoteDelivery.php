<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Support\ReviewInvite;
use Illuminate\Support\Str;

class QuoteDelivery
{
    public static function ensureToken(QuoteRequest $request, bool $forceNew = false): QuoteRequest
    {
        $days = (int) config('profiles.quote_token_days', 30);
        $expired = $request->client_token_expires_at
            && $request->client_token_expires_at->isPast();

        if ($forceNew || blank($request->client_token) || $expired) {
            $request->client_token = Str::lower(Str::random(40));
        }

        $request->client_token_expires_at = now()->addDays(max(1, $days));
        $request->save();

        return $request->fresh(['artisanQuote', 'artisan']);
    }

    public static function publicUrl(QuoteRequest $request): string
    {
        return url('/q/'.$request->client_token);
    }

    public static function pdfUrl(QuoteRequest $request): string
    {
        return url('/q/'.$request->client_token.'/pdf');
    }

    public static function message(QuoteRequest $request, ArtisanQuote $quote): string
    {
        $request->loadMissing('artisan');
        $artisan = $request->artisan;
        $business = $artisan?->displayBusinessName() ?? 'your artisan';
        $title = $request->displayTitle();
        $total = number_format($quote->total_kobo / 100, 0);
        $link = filled($request->client_token) ? self::publicUrl($request) : 'https://isabi.dev/q/…';

        $greeting = trim($request->name) !== '' ? "Hi {$request->name}," : 'Hi,';

        return implode("\n", array_filter([
            $greeting,
            '',
            "{$business} has sent you a quote for “{$title}”.",
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
