<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class QuotePdfService
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(QuoteRequest $request, ArtisanQuote $quote, User $artisan): array
    {
        $request->loadMissing('workLog');
        $lineItems = QuoteLineItem::normalizeCollection($quote->line_items ?? []);

        return [
            'artisan' => $artisan,
            'request' => $request,
            'quote' => $quote,
            'lineItems' => $lineItems,
            'brandLogo' => $artisan->brandLogoUrl(),
            'generatedAt' => now()->timezone(config('app.display_timezone')),
            'client' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ],
            'totals' => [
                'subtotal' => $quote->subtotal_kobo / 100,
                'discount' => $quote->discount_kobo / 100,
                'vat_rate' => (float) $quote->vat_rate,
                'vat' => $quote->vat_kobo / 100,
                'total' => $quote->total_kobo / 100,
            ],
        ];
    }

    public function make(QuoteRequest $request, ArtisanQuote $quote, User $artisan)
    {
        return Pdf::loadView('pdf.quote', $this->viewData($request, $quote, $artisan))
            ->setPaper('a4');
    }

    public function output(QuoteRequest $request, ArtisanQuote $quote, User $artisan): string
    {
        return $this->make($request, $quote, $artisan)->output();
    }

    public function filename(ArtisanQuote $quote, User $artisan): string
    {
        $business = Str::slug($artisan->displayBusinessName() ?: 'quote');

        return "{$business}-{$quote->quote_number}.pdf";
    }
}
