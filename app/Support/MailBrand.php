<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class MailBrand
{
    public static function logoPath(): string
    {
        return public_path('brand/kraftrack-kt-mark.png');
    }

    /**
     * Absolute URL fallback for clients that cannot use CID embeds.
     * Prefer MAIL_LOGO_URL (public HTTPS CDN) in production.
     */
    public static function logoUrl(): string
    {
        $configured = trim((string) config('mail.logo_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/brand/kraftrack-kt-mark.png';
    }

    public static function logoExists(): bool
    {
        $path = self::logoPath();

        return is_file($path) && filesize($path) > 0;
    }

    /**
     * Best source for HTML mail: CID embed when $message is available,
     * otherwise absolute URL (MAIL_LOGO_URL or APP_URL asset).
     */
    public static function logoSrc(mixed $message = null): string
    {
        if (self::logoExists() && is_object($message) && method_exists($message, 'embed')) {
            try {
                // Embed as inline image so Gmail/Outlook show the brand mark
                // without fetching APP_URL (often unreachable from inboxes).
                return $message->embed(self::logoPath());
            } catch (\Throwable $e) {
                Log::info('mail.logo_embed_failed', ['message' => $e->getMessage()]);
            }
        }

        return self::logoUrl();
    }
}
