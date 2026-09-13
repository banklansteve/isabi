<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteRequestArtisanAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public User $artisan,
        public string $viewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New quote request from '.$this->quoteRequest->name,
            tags: ['quote-request', 'artisan'],
            metadata: [
                'quote_request_uid' => (string) $this->quoteRequest->uid,
            ],
        );
    }

    public function content(): Content
    {
        $this->quoteRequest->loadMissing('workLog');

        return new Content(
            markdown: 'mail.quotes.artisan-alert',
            with: [
                'appName' => config('app.name'),
                'artisanName' => $this->artisan->first_name ?: str($this->artisan->name)->before(' ')->toString(),
                'clientName' => $this->quoteRequest->name,
                'clientPhone' => $this->quoteRequest->phone,
                'clientEmail' => $this->quoteRequest->email,
                'subject' => $this->quoteRequest->subject ?: $this->quoteRequest->displayTitle(),
                'messageText' => $this->quoteRequest->message,
                'jobLabel' => $this->quoteRequest->workLog?->description,
                'viewUrl' => $this->viewUrl,
                'requestsUrl' => route('quotes.index', absolute: true),
            ],
        );
    }
}
