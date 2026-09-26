<?php

namespace App\Support\WorkLog;

/**
 * Detect client-identifying details in public job titles (subjects).
 * Soft heuristics — prefer false negatives over blocking normal trade language.
 */
class JobSubjectPrivacy
{
    public const MESSAGE = 'Don’t put client names, phone numbers, or home addresses in the title — that shows on your public page. Use the private Client name field instead.';

    /**
     * @return string|null Error message when the subject looks unsafe, otherwise null.
     */
    public static function violation(?string $subject, ?string $clientName = null, ?string $clientWhatsapp = null): ?string
    {
        $subject = trim((string) $subject);
        if ($subject === '') {
            return null;
        }

        $haystack = ' '.$subject.' ';

        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $subject)) {
            return self::MESSAGE;
        }

        // Nigerian / intl phone patterns (digits with optional separators).
        $digits = preg_replace('/\D+/', '', $subject) ?? '';
        if (
            preg_match('/(?:\+?234|0)[789][01]\d{8}/', $digits)
            || preg_match('/(?<!\d)(?:\+?\d[\d\s().-]{8,}\d)(?!\d)/', $subject)
        ) {
            return self::MESSAGE;
        }

        if (filled($clientWhatsapp)) {
            $waDigits = preg_replace('/\D+/', '', (string) $clientWhatsapp) ?? '';
            if (strlen($waDigits) >= 10 && str_contains($digits, substr($waDigits, -10))) {
                return self::MESSAGE;
            }
        }

        // Honorific + CapWord name (Mrs Adeyemi, Chief Okonkwo, Engr. Musa…).
        if (preg_match(
            '/\b(?:mr|mrs|miss|ms|dr|prof|engr|eng|barr|chief|alhaji|alhaja|pastor|rev|sir|madam|mallam|hajiya)\.?\s+[A-ZÀ-ÖØ-Þ][\p{L}\'-]{1,}/iu',
            $subject
        )) {
            return self::MESSAGE;
        }

        // House / plot style address fragments.
        if (preg_match(
            '/\b(?:no\.?|plot|house|flat|apt\.?|apartment)\s*\d{1,5}\b/i',
            $subject
        )) {
            return self::MESSAGE;
        }

        if (preg_match(
            '/\b\d{1,4}\s+(?:[\p{L}][\p{L}\'-]{1,}[\s\-]+){0,3}[\p{L}][\p{L}\'-]{1,}\s+(?:street|st\.?|road|rd\.?|avenue|ave\.?|close|crescent|way|drive|dr\.?|lane|boulevard|blvd\.?)\b/iu',
            $subject
        )) {
            return self::MESSAGE;
        }

        if (filled($clientName) && self::containsClientNameTokens($subject, (string) $clientName)) {
            return self::MESSAGE;
        }

        return null;
    }

    private static function containsClientNameTokens(string $subject, string $clientName): bool
    {
        $subjectNorm = mb_strtolower($subject);
        $tokens = preg_split('/[\s,.\/\-]+/u', mb_strtolower(trim($clientName))) ?: [];

        $skip = [
            'mr', 'mrs', 'miss', 'ms', 'dr', 'prof', 'engr', 'eng', 'barr', 'chief',
            'alhaji', 'alhaja', 'pastor', 'rev', 'sir', 'madam', 'mallam', 'hajiya',
            'the', 'and', 'of', 'for',
        ];

        foreach ($tokens as $token) {
            $token = trim($token, " \t\n\r\0\x0B.'\"");
            if ($token === '' || mb_strlen($token) < 3 || in_array($token, $skip, true)) {
                continue;
            }
            if (preg_match('/\b'.preg_quote($token, '/').'\b/u', $subjectNorm)) {
                return true;
            }
        }

        return false;
    }
}
