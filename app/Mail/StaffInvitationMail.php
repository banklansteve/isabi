<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $invitee,
        public string $acceptUrl,
        public int $expiresHours,
        public ?User $invitedBy = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Join the Isabi operations team',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.staff.invitation',
        );
    }
}
