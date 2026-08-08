<?php

namespace App\Support;

use App\Models\WorkLog;
use Illuminate\Support\Str;

class ReviewInvite
{
    public static function ensureToken(WorkLog $workLog, bool $forceNew = false): WorkLog
    {
        $days = (int) config('profiles.review_token_days', 30);
        $expired = $workLog->review_token_expires_at
            && $workLog->review_token_expires_at->isPast();

        if (
            $forceNew
            || blank($workLog->review_token)
            || $expired
        ) {
            $workLog->review_token = Str::lower(Str::random(40));
        }

        $workLog->review_token_expires_at = now()->addDays(max(1, $days));
        $workLog->review_requested_at = $workLog->review_requested_at ?? now();
        $workLog->save();

        return $workLog->fresh();
    }

    public static function publicUrl(WorkLog $workLog): string
    {
        return url('/r/'.$workLog->review_token);
    }

    /**
     * Pre-written client message for the WhatsApp share sheet.
     */
    public static function message(WorkLog $workLog): string
    {
        $name = trim((string) $workLog->client_name);
        $greeting = $name !== '' ? "Hi {$name}," : 'Hi,';
        $job = self::jobPhrase($workLog);
        $link = self::publicUrl($workLog);

        return "{$greeting} thanks for trusting me with your {$job}! "
            ."I'd really appreciate it if you could leave a quick review here: {$link} "
            ."— it only takes a minute, and it helps others know they can trust my work too.";
    }

    /**
     * Natural phrase for “your ___” in the WhatsApp invite.
     * Uses the private subcategory review_phrase — never the long job description
     * and never the raw subcategory title (e.g. “Python Developer”).
     */
    public static function jobPhrase(WorkLog $workLog): string
    {
        $stored = trim((string) $workLog->job_review_phrase);
        if ($stored !== '') {
            return $stored;
        }

        $fromConfig = JobCategories::reviewPhrase(
            $workLog->job_category,
            $workLog->job_subcategory,
        );

        if (filled($fromConfig)) {
            return $fromConfig;
        }

        return 'recent work';
    }

    /**
     * Universal https link — opens the WhatsApp app on mobile when installed.
     */
    public static function whatsappAppUrl(WorkLog $workLog): string
    {
        $encoded = rawurlencode(self::message($workLog));
        $phone = self::normalizeWhatsapp($workLog->client_whatsapp);

        if ($phone !== null) {
            return "https://wa.me/{$phone}?text={$encoded}";
        }

        return "https://wa.me/?text={$encoded}";
    }

    /**
     * @deprecated Not used by the frontend. Custom protocols (whatsapp://) always
     * trigger a second browser “Open WhatsApp?” dialog after our share modal.
     * Kept only for backward-compatible payload shape.
     */
    public static function whatsappProtocolUrl(WorkLog $workLog): string
    {
        $encoded = rawurlencode(self::message($workLog));
        $phone = self::normalizeWhatsapp($workLog->client_whatsapp);

        if ($phone !== null) {
            return "whatsapp://send?phone={$phone}&text={$encoded}";
        }

        return "whatsapp://send?text={$encoded}";
    }

    /**
     * Desktop HTTPS click-to-chat (no custom protocol — avoids browser alerts).
     */
    public static function whatsappWebUrl(WorkLog $workLog): string
    {
        $encoded = rawurlencode(self::message($workLog));
        $phone = self::normalizeWhatsapp($workLog->client_whatsapp);

        if ($phone !== null) {
            return "https://web.whatsapp.com/send?phone={$phone}&text={$encoded}";
        }

        return "https://api.whatsapp.com/send?text={$encoded}";
    }

    /** @deprecated Use whatsappAppUrl() — kept for call-site compatibility. */
    public static function whatsappShareUrl(WorkLog $workLog, string $artisanFirstName = ''): string
    {
        return self::whatsappAppUrl($workLog);
    }

    /**
     * @return array{
     *     review_url: string,
     *     whatsapp_url: string,
     *     whatsapp_app_url: string,
     *     whatsapp_protocol_url: string,
     *     whatsapp_web_url: string,
     *     message: string
     * }|null
     */
    public static function payload(WorkLog $workLog, string $artisanFirstName = ''): ?array
    {
        if (blank($workLog->review_token)) {
            return null;
        }

        $appUrl = self::whatsappAppUrl($workLog);

        return [
            'review_url' => self::publicUrl($workLog),
            'whatsapp_url' => $appUrl,
            'whatsapp_app_url' => $appUrl,
            'whatsapp_protocol_url' => self::whatsappProtocolUrl($workLog),
            'whatsapp_web_url' => self::whatsappWebUrl($workLog),
            'message' => self::message($workLog),
        ];
    }

    public static function normalizeWhatsapp(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234'.substr($digits, 1);
        }

        if (str_starts_with($digits, '234') && strlen($digits) >= 13) {
            return $digits;
        }

        return $digits !== '' ? $digits : null;
    }
}
