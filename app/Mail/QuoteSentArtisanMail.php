<?php

namespace App\Mail;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentArtisanMail extends Mailable
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
        public string $builderUrl,
        public ?string $pdfBytes = null,
        public ?string $pdfFilename = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your quote has been sent — {$this->quote->quote_number}",
            tags: ['quote-sent', 'artisan'],
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
            markdown: 'mail.quotes.sent-artisan',
            with: [
                'appName' => config('app.name'),
                'artisanName' => $this->artisan->first_name ?: str($this->artisan->name)->before(' ')->toString(),
                'clientName' => $this->quoteRequest->name,
                'clientEmail' => $this->quoteRequest->email,
                'title' => $this->quoteRequest->displayTitle(),
                'quoteNumber' => $this->quote->quote_number,
                'totalNaira' => number_format($this->quote->total_kobo / 100, 0),
                'validUntil' => $this->quote->valid_until
                    ?->timezone($tz)
                    ->format('j M Y'),
                'quoteUrl' => $this->quoteUrl,
                'pdfUrl' => $this->pdfUrl,
                'builderUrl' => $this->builderUrl,
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
