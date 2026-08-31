<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        );
    }

    public function content(): Content
    {
        $this->quoteRequest->loadMissing('workLog');

        return new Content(
            markdown: 'mail.quotes.client-confirmation',
            with: [
                'appName' => config('app.name'),
                'clientName' => $this->quoteRequest->name,
                'businessName' => $this->artisan->displayBusinessName(),
                'trade' => $this->artisan->trade,
                'jobLabel' => $this->quoteRequest->workLog?->description,
                'messageText' => $this->quoteRequest->message,
                'profileUrl' => $this->artisan->publicUrl(),
            ],
        );
    }
}
