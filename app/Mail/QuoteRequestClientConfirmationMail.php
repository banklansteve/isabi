<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteRequestClientConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public User $artisan,
    ) {}

    public function envelope(): Envelope
    {
        $business = $this->artisan->displayBusinessName();

        return new Envelope(
            subject: "Your quote request to {$business} is on its way",
            replyTo: filled($this->artisan->email)
                ? [new Address($this->artisan->email, $business)]
                : [],
            tags: ['quote-request', 'client'],
            metadata: [
                'quote_request_uid' => (string) $this->quoteRequest->uid,
            ],
        );
    }

    public function content(): Content
    {
        $this->quoteRequest->loadMissing('workLog');
        $tz = config('app.display_timezone', config('app.timezone'));

        return new Content(
            markdown: 'mail.quotes.client-confirmation',
            with: [
                'appName' => config('app.name'),
                'clientName' => $this->quoteRequest->name,
                'businessName' => $this->artisan->displayBusinessName(),
                'trade' => $this->artisan->trade,
                'subject' => $this->quoteRequest->subject ?: $this->quoteRequest->displayTitle(),
                'jobLabel' => $this->quoteRequest->workLog?->description,
                'messageText' => $this->quoteRequest->message,
                'submittedAt' => $this->quoteRequest->created_at
                    ?->timezone($tz)
                    ->format('j M Y · g:i A'),
                'profileUrl' => $this->artisan->publicUrl(),
            ],
        );
    }
}
