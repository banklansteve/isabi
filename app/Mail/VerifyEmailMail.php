<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        $app = config('app.name', 'Kraftrack');

        return new Envelope(
            subject: "{$this->code} is your {$app} verification code",
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name', $app),
            ),
            replyTo: [
                new Address(
                    (string) config('mail.from.address'),
                    (string) config('mail.from.name', $app),
                ),
            ],
            tags: ['email-verification'],
            metadata: [
                'user_id' => (string) $this->user->id,
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Kraftrack-Mail' => 'email-verification',
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.verify-email',
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'verifyUrl' => $this->verifyUrl,
                'expiresMinutes' => 15,
                'appName' => config('app.name', 'Kraftrack'),
            ],
        );
    }
}
