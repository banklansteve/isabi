<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
            'brandLogo' => $this->embedBrandLogo($artisan),
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

    /**
     * Stream a finished PDF download with Content-Length set.
     * Missing length is a common cause of stuck Chrome `.crdownload` files.
     */
    public function download(
        QuoteRequest $request,
        ArtisanQuote $quote,
        User $artisan,
        bool $attachment = true,
    ): Response {
        $filename = $this->filename($quote, $artisan);
        $pdf = $this->make($request, $quote, $artisan);

        return $attachment
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function filename(ArtisanQuote $quote, User $artisan): string
    {
        $business = Str::slug($artisan->displayBusinessName() ?: 'quote') ?: 'quote';
        $number = Str::slug((string) $quote->quote_number) ?: 'quote';

        return "{$business}-{$number}.pdf";
    }

    /**
     * DomPDF has remote fetching off by default. Fetch the logo ourselves with a
     * short timeout and embed as a data URI so generation never hangs.
     */
    private function embedBrandLogo(User $artisan): ?string
    {
        $url = $artisan->brandLogoUrl();

        if (! filled($url)) {
            return null;
        }

        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        // Local public paths — DomPDF can read via chroot.
        if (str_starts_with($url, '/')) {
            $path = public_path(ltrim($url, '/'));
            if (is_file($path)) {
                return $this->dataUriFromPath($path);
            }
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return null;
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $bytes = $response->body();
            if ($bytes === '' || strlen($bytes) > 2_500_000) {
                return null;
            }

            $mime = $this->imageMime(
                $response->header('Content-Type'),
                $bytes,
            );

            if ($mime === null) {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($bytes);
        } catch (ConnectionException|Throwable $e) {
            Log::info('quote.pdf.logo_embed_skipped', [
                'artisan_id' => $artisan->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function dataUriFromPath(string $path): ?string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $mime = $this->imageMime(mime_content_type($path) ?: null, $bytes);

        return $mime ? 'data:'.$mime.';base64,'.base64_encode($bytes) : null;
    }

    private function imageMime(?string $header, string $bytes): ?string
    {
        $header = strtolower(trim(explode(';', (string) $header)[0]));
        if (in_array($header, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return $header === 'image/jpg' ? 'image/jpeg' : $header;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = strtolower((string) $finfo->buffer($bytes));

        return in_array($detected, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)
            ? $detected
            : null;
    }
}
