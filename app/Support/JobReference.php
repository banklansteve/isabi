<?php

namespace App\Support;

use App\Models\WorkLog;

/**
 * Opaque public job IDs — never derived from subject, client, or free text.
 *
 * Fixed-length, cryptographically random, URL-safe alphabet (no ambiguous 0/O/1/I/l).
 * Always a mix of lowercase letters and digits.
 */
class JobReference
{
    /** Lowercase letters, no ambiguous i/l/o. */
    private const LETTERS = 'abcdefghjkmnpqrstuvwxyz';

    /** Digits, no ambiguous 0/1. */
    private const DIGITS = '23456789';

    /** Canonical length for new references. */
    public const LENGTH = 6;

    /** Accept legacy lengths during redirects (pre–6-char era). */
    public const MIN_LENGTH = 6;

    public const MAX_LENGTH = 8;

    /** Short opaque share code — never derived from client or job free text. */
    public static function unique(?int $ignoreId = null, ?string $unused = null): string
    {
        do {
            $reference = self::generate();
        } while (
            WorkLog::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('reference', $reference)
                ->exists()
        );

        return $reference;
    }

    public static function isValid(?string $reference): bool
    {
        if ($reference === null || $reference === '') {
            return false;
        }

        $length = strlen($reference);

        // Accept legacy 6–8 during redirects; new IDs are always LENGTH.
        return $length >= self::MIN_LENGTH
            && $length <= self::MAX_LENGTH
            && (bool) preg_match('/^[a-z0-9]+$/', $reference);
    }

    public static function isCanonical(?string $reference): bool
    {
        if (! is_string($reference) || strlen($reference) !== self::LENGTH) {
            return false;
        }

        if (! preg_match('/^[a-z0-9]+$/', $reference)) {
            return false;
        }

        // Canonical IDs always mix letters and digits.
        return (bool) preg_match('/[a-z]/', $reference)
            && (bool) preg_match('/[0-9]/', $reference);
    }

    private static function generate(): string
    {
        $length = self::LENGTH;
        $letters = self::LETTERS;
        $digits = self::DIGITS;
        $charset = $letters.$digits;
        $charsetLen = strlen($charset) - 1;

        // Guarantee at least one letter and one digit.
        $chars = [
            $letters[random_int(0, strlen($letters) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        for ($i = 2; $i < $length; $i++) {
            $chars[] = $charset[random_int(0, $charsetLen)];
        }

        // Fisher–Yates shuffle so the guaranteed pair isn't positional.
        for ($i = $length - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
