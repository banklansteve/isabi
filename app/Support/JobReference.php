<?php

namespace App\Support;

use App\Models\WorkLog;

class JobReference
{
    /** Lowercase letters + digits, no ambiguous i/l/o/0/1. */
    private const CHARSET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public const MIN_LENGTH = 6;

    public const MAX_LENGTH = 8;

    /** Short, site-neutral share code — e.g. bath7k2m */
    public static function unique(?int $ignoreId = null, ?string $description = null): string
    {
        do {
            $reference = self::generate($description);
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

        return $length >= self::MIN_LENGTH
            && $length <= self::MAX_LENGTH
            && (bool) preg_match('/^[a-z0-9]+$/', $reference);
    }

    private static function generate(?string $description): string
    {
        $targetLength = random_int(self::MIN_LENGTH, self::MAX_LENGTH);
        $seed = self::seedFromDescription($description, min(4, $targetLength - 2));
        $suffixLength = max(2, $targetLength - strlen($seed));
        $suffix = self::randomString($suffixLength);

        $reference = substr($seed.$suffix, 0, $targetLength);

        if (strlen($reference) < self::MIN_LENGTH) {
            $reference .= self::randomString(self::MIN_LENGTH - strlen($reference));
        }

        return $reference;
    }

    private static function seedFromDescription(?string $description, int $max): string
    {
        if ($max < 1 || blank($description)) {
            return '';
        }

        $slug = JobSlug::normalize($description);
        $letters = preg_replace('/[^a-z]/', '', $slug) ?? '';

        return substr($letters, 0, $max);
    }

    private static function randomString(int $length): string
    {
        $out = '';
        $charsetLength = strlen(self::CHARSET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $out .= self::CHARSET[random_int(0, $charsetLength)];
        }

        return $out;
    }
}
