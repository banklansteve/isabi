<?php

namespace App\Mail;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteExpiryNudgeArtisanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public ArtisanQuote $quote,
        public User $artisan,
        public string $builderUrl,
        public ?string $quoteUrl,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->daysLeft === 1
                ? "Quote {$this->quote->quote_number} expires tomorrow — {$this->quoteRequest->name}"
                : "Quote {$this->quote->quote_number} expires in {$this->daysLeft} days — {$this->quoteRequest->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.expiry-nudge-artisan',
            with: [
                'appName' => config('app.name'),
                'artisanName' => $this->artisan->name,
                'clientName' => $this->quoteRequest->name,
                'clientEmail' => $this->quoteRequest->email,
                'title' => $this->quoteRequest->displayTitle(),
                'quoteNumber' => $this->quote->quote_number,
                'totalNaira' => number_format($this->quote->total_kobo / 100, 0),
                'validUntil' => $this->quote->valid_until
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'daysLeft' => $this->daysLeft,
                'builderUrl' => $this->builderUrl,
                'quoteUrl' => $this->quoteUrl,
            ],
        );
    }
}
