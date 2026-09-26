<?php

namespace App\Mail;

use App\Mail\Concerns\TransactionalMailDefaults;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class QuoteClientRespondedArtisanMail extends Mailable
{
    use Queueable, SerializesModels, TransactionalMailDefaults;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public ArtisanQuote $quote,
        public User $artisan,
        public string $decision,
        public string $builderUrl,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            'accepted' => "Quote {$this->quote->quote_number} accepted",
            'declined' => "Quote {$this->quote->quote_number} declined",
            'adjustments' => "Changes requested on quote {$this->quote->quote_number}",
            default => "Update on quote {$this->quote->quote_number}",
        };

        return new Envelope(
            subject: $this->cleanSubject($subject),
            from: $this->kraftrackFrom(),
            tags: ['quote-client-response', $this->decision],
            metadata: [
                'quote_request_uid' => (string) $this->quoteRequest->uid,
                'quote_number' => (string) $this->quote->quote_number,
                'decision' => $this->decision,
            ],
        );
    }

    public function headers(): Headers
    {
        return $this->transactionalHeaders('quote-client-responded-artisan');
    }

    public function content(): Content
    {
        $headline = match ($this->decision) {
            'accepted' => 'Your quote was accepted',
            'declined' => 'Your quote was declined',
            'adjustments' => 'The client requested changes',
            default => 'Your quote was updated',
        };

        $summary = match ($this->decision) {
            'accepted' => 'Great news - '.$this->quoteRequest->name.' accepted quote '.$this->quote->quote_number.'.',
            'declined' => $this->quoteRequest->name.' declined quote '.$this->quote->quote_number.'.',
            'adjustments' => $this->quoteRequest->name.' asked for adjustments on quote '.$this->quote->quote_number.'. Review their note and send an updated quote.',
            default => 'There is an update on quote '.$this->quote->quote_number.'.',
        };

        return new Content(
            markdown: 'mail.quotes.client-responded-artisan',
            with: [
                'appName' => config('app.name'),
                'artisanName' => $this->artisan->first_name
                    ?: str($this->artisan->name)->before(' ')->toString(),
                'clientName' => $this->quoteRequest->name,
                'title' => $this->quoteRequest->displayTitle(),
                'quoteNumber' => $this->quote->quote_number,
                'decision' => $this->decision,
                'headline' => $headline,
                'summary' => $summary,
                'clientNote' => filled($this->quoteRequest->client_response)
                    ? (string) $this->quoteRequest->client_response
                    : null,
                'builderUrl' => $this->builderUrl,
                'ctaLabel' => $this->decision === 'adjustments'
                    ? 'Revise quote'
                    : 'Open quote',
            ],
        );
    }
}
