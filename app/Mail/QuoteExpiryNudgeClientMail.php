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

class QuoteExpiryNudgeClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public ArtisanQuote $quote,
        public User $artisan,
        public string $quoteUrl,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $business = $this->artisan->displayBusinessName();

        return new Envelope(
            subject: $this->daysLeft === 1
                ? "Last day — your quote from {$business} expires tomorrow"
                : "Reminder — your quote from {$business} expires in {$this->daysLeft} days",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.expiry-nudge-client',
            with: [
                'appName' => config('app.name'),
                'clientName' => $this->quoteRequest->name,
                'businessName' => $this->artisan->displayBusinessName(),
                'title' => $this->quoteRequest->displayTitle(),
                'quoteNumber' => $this->quote->quote_number,
                'totalNaira' => number_format($this->quote->total_kobo / 100, 0),
                'validUntil' => $this->quote->valid_until
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'daysLeft' => $this->daysLeft,
                'quoteUrl' => $this->quoteUrl,
            ],
        );
    }
}
