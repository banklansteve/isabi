<?php

namespace App\Mail;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Support\Quotes\QuoteDelivery;
use App\Support\Quotes\QuotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteDeliveredClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public ArtisanQuote $quote,
        public User $artisan,
        public string $quoteUrl,
    ) {}

    public function envelope(): Envelope
    {
        $business = $this->artisan->displayBusinessName();

        return new Envelope(
            subject: "Your quote from {$business} — {$this->quoteRequest->displayTitle()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.delivered-client',
            with: [
                'appName' => config('app.name'),
                'clientName' => $this->quoteRequest->name,
                'businessName' => $this->artisan->displayBusinessName(),
                'title' => $this->quoteRequest->displayTitle(),
                'totalNaira' => number_format($this->quote->total_kobo / 100, 0),
                'validUntil' => $this->quote->valid_until
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'quoteUrl' => $this->quoteUrl,
                'pdfUrl' => QuoteDelivery::pdfUrl($this->quoteRequest),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(QuotePdfService::class);

        return [
            Attachment::fromData(
                fn () => $pdf->output($this->quoteRequest, $this->quote, $this->artisan),
                $pdf->filename($this->quote, $this->artisan),
            )->withMime('application/pdf'),
        ];
    }
}
