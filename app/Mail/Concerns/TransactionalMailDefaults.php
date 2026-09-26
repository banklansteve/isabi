<?php

namespace App\Mail\Concerns;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Headers;

trait TransactionalMailDefaults
{
    protected function kraftrackFrom(): Address
    {
        $app = (string) config('app.name', 'Kraftrack');

        return new Address(
            (string) config('mail.from.address'),
            (string) config('mail.from.name', $app),
        );
    }

    protected function transactionalHeaders(string $purpose): Headers
    {
        $from = (string) config('mail.from.address');
        $appUrl = rtrim((string) config('app.url'), '/');

        return new Headers(
            text: array_filter([
                'X-Kraftrack-Mail' => $purpose,
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
                // Helps Gmail/Outlook treat this as legitimate transactional mail.
                'List-Unsubscribe' => $from !== ''
                    ? '<mailto:'.$from.'?subject=unsubscribe>'
                    : null,
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Entity-Ref-ID' => hash('sha256', $purpose.'|'.$appUrl.'|'.microtime(true)),
            ]),
        );
    }

    protected function cleanSubject(string $subject): string
    {
        // Avoid fancy dashes / currency glyphs that some filters score poorly.
        return str_replace(['—', '–', '₦'], ['-', '-', 'NGN '], $subject);
    }
}
