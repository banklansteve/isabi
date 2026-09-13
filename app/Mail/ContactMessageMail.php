<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, phone: ?string, topic: string, message: string}  $payload
     */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        $topic = strtoupper($this->payload['topic']);

        return new Envelope(
            replyTo: [
                new Address($this->payload['email'], $this->payload['name']),
            ],
            subject: "[Kraftrack contact · {$topic}] {$this->payload['name']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-message',
        );
    }
}
