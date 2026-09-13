<?php

namespace App\Mail;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteDeliveredClientMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string|null  $pdfBytes  Raw PDF bytes; null skips the attachment.
     */
    public function __construct(
        public QuoteRequest $quoteRequest,
        public ArtisanQuote $quote,
        public User $artisan,
        public string $quoteUrl,
        public string $pdfUrl,
        public ?string $pdfBytes = null,
        public ?string $pdfFilename = null,
    ) {
        // Never queue large binary payloads.
        $this->afterCommit = false;
    }

    public function envelope(): Envelope
    {
        $business = $this->artisan->displayBusinessName();

        return new Envelope(
            subject: "Your quote from {$business} — {$this->quote->quote_number}",
            replyTo: filled($this->artisan->email)
                ? [new Address($this->artisan->email, $business)]
                : [],
            tags: ['quote-delivered', 'client'],
            metadata: [
                'quote_request_uid' => (string) $this->quoteRequest->uid,
                'quote_number' => (string) $this->quote->quote_number,
            ],
        );
    }

    public function content(): Content
    {
        $tz = config('app.display_timezone', config('app.timezone'));

        return new Content(
            markdown: 'mail.quotes.delivered-client',
            with: [
                'appName' => config('app.name'),
                'clientName' => $this->quoteRequest->name,
                'businessName' => $this->artisan->displayBusinessName(),
                'title' => $this->quoteRequest->displayTitle(),
                'quoteNumber' => $this->quote->quote_number,
                'totalNaira' => number_format($this->quote->total_kobo / 100, 0),
                'validUntil' => $this->quote->valid_until
                    ?->timezone($tz)
                    ->format('j M Y'),
                'estimatedStart' => $this->quote->estimated_start
                    ?->timezone($tz)
                    ->format('j M Y'),
                'quoteUrl' => $this->quoteUrl,
                'pdfUrl' => $this->pdfUrl,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->pdfBytes === null || $this->pdfBytes === '') {
            return [];
        }

        $name = $this->pdfFilename ?: 'quote.pdf';
        $bytes = $this->pdfBytes;

        return [
            Attachment::fromData(static fn () => $bytes, $name)
                ->withMime('application/pdf'),
        ];
    }
}
