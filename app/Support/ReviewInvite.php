<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Str;

class ReviewInvite
{
    public const KIND_INVITE = 'invite';

    public const KIND_REMINDER = 'reminder';

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

        return $workLog->fresh(['user']);
    }

    public static function publicUrl(WorkLog $workLog): string
    {
        return url('/r/'.$workLog->review_token);
    }

    /**
     * Pre-written client message for the WhatsApp share sheet.
     *
     * @param  self::KIND_*  $kind
     */
    public static function message(WorkLog $workLog, string $kind = self::KIND_INVITE): string
    {
        $user = $workLog->relationLoaded('user')
            ? $workLog->user
            : $workLog->user()->first();

        $template = $kind === self::KIND_REMINDER
            ? ($user?->reviewReminderTemplate() ?? (string) config('review_messages.reminder'))
            : ($user?->reviewInviteTemplate() ?? (string) config('review_messages.invite'));

        return self::renderTemplate($template, $workLog);
    }

    public static function renderTemplate(string $template, WorkLog $workLog, ?string $link = null): string
    {
        $name = trim((string) $workLog->client_name);
        $greeting = $name !== '' ? "Hi {$name}," : 'Hi,';
        $job = self::jobPhrase($workLog);
        $resolvedLink = $link ?? (filled($workLog->review_token) ? self::publicUrl($workLog) : 'https://isabi.dev/r/…');

        $rendered = str_replace(
            ['{greeting}', '{client_name}', '{job}', '{link}'],
            [
                $greeting,
                $name !== '' ? $name : '',
                $job,
                $resolvedLink,
            ],
            $template,
        );

        // Clean up “Hi ,” / double spaces when {client_name} is empty inside a greeting.
        $rendered = preg_replace('/\bHi\s+,/u', 'Hi,', $rendered) ?? $rendered;
        $rendered = preg_replace('/[ \t]{2,}/u', ' ', $rendered) ?? $rendered;

        return trim($rendered);
    }

    /**
     * Preview helper for the profile settings form (no live work log).
     */
    public static function preview(string $template, User $user): string
    {
        $sample = new WorkLog([
            'client_name' => 'Ada',
            'job_review_phrase' => 'rewiring',
            'review_token' => 'preview',
        ]);
        $sample->setRelation('user', $user);

        return self::renderTemplate(
            $template,
            $sample,
            url('/r/preview-link'),
        );
    }

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
     * @param  self::KIND_*  $kind
     */
    public static function whatsappAppUrl(WorkLog $workLog, string $kind = self::KIND_INVITE): string
    {
        $encoded = rawurlencode(self::message($workLog, $kind));
        $phone = self::normalizeWhatsapp($workLog->client_whatsapp);

        if ($phone !== null) {
            return "https://wa.me/{$phone}?text={$encoded}";
        }

        return "https://wa.me/?text={$encoded}";
    }

    /**
     * @deprecated Not used by the frontend.
     *
     * @param  self::KIND_*  $kind
     */
    public static function whatsappProtocolUrl(WorkLog $workLog, string $kind = self::KIND_INVITE): string
    {
        $encoded = rawurlencode(self::message($workLog, $kind));
        $phone = self::normalizeWhatsapp($workLog->client_whatsapp);

        if ($phone !== null) {
            return "whatsapp://send?phone={$phone}&text={$encoded}";
        }

        return "whatsapp://send?text={$encoded}";
    }

    /**
     * @param  self::KIND_*  $kind
     */
    public static function whatsappWebUrl(WorkLog $workLog, string $kind = self::KIND_INVITE): string
    {
        $encoded = rawurlencode(self::message($workLog, $kind));
        $phone = self::normalizeWhatsapp($workLog->client_whatsapp);

        if ($phone !== null) {
            return "https://web.whatsapp.com/send?phone={$phone}&text={$encoded}";
        }

        return "https://api.whatsapp.com/send?text={$encoded}";
    }

    /** @deprecated Use whatsappAppUrl() */
    public static function whatsappShareUrl(WorkLog $workLog, string $artisanFirstName = ''): string
    {
        return self::whatsappAppUrl($workLog);
    }

    /**
     * @param  self::KIND_*  $kind
     * @return array{
     *     review_url: string,
     *     whatsapp_url: string,
     *     whatsapp_app_url: string,
     *     whatsapp_protocol_url: string,
     *     whatsapp_web_url: string,
     *     message: string,
     *     kind: string
     * }|null
     */
    public static function payload(WorkLog $workLog, string $artisanFirstName = '', string $kind = self::KIND_INVITE): ?array
    {
        if (blank($workLog->review_token)) {
            return null;
        }

        $appUrl = self::whatsappAppUrl($workLog, $kind);

        return [
            'review_url' => self::publicUrl($workLog),
            'whatsapp_url' => $appUrl,
            'whatsapp_app_url' => $appUrl,
            'whatsapp_protocol_url' => self::whatsappProtocolUrl($workLog, $kind),
            'whatsapp_web_url' => self::whatsappWebUrl($workLog, $kind),
            'message' => self::message($workLog, $kind),
            'kind' => $kind,
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
