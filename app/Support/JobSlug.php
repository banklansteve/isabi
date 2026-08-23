<?php

namespace App\Support;

use App\Models\WorkLog;
use Illuminate\Support\Str;

/**
 * Readable, stable URL segments for a public job page.
 *
 * Slugs are unique per artisan rather than globally, so two artisans can both
 * have a "rewired-a-three-bedroom-flat" without either of them getting an
 * ugly numeric suffix.
 */
class JobSlug
{
    private const MAX_LENGTH = 70;

    public static function normalize(?string $value): string
    {
        $slug = Str::slug(Str::ascii((string) $value));

        if ($slug === '') {
            return 'job';
        }

        if (strlen($slug) <= self::MAX_LENGTH) {
            return $slug;
        }

        // Trim on a word boundary so the tail doesn't read as a truncated word.
        $trimmed = substr($slug, 0, self::MAX_LENGTH);
        $lastHyphen = strrpos($trimmed, '-');

        return $lastHyphen !== false && $lastHyphen > 20
            ? substr($trimmed, 0, $lastHyphen)
            : $trimmed;
    }

    public static function uniqueFor(int $userId, ?string $description, ?int $ignoreId = null): string
    {
        $base = self::normalize($description);

        if (in_array($base, ['reviews', 'jobs', 'share', 'about', 'edit'], true)) {
            $base .= '-job';
        }

        $slug = $base;
        $suffix = 2;

        while (self::taken($userId, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;

            if ($suffix > 200) {
                return $base.'-'.Str::lower(Str::random(6));
            }
        }

        return $slug;
    }

    private static function taken(int $userId, string $slug, ?int $ignoreId): bool
    {
        return WorkLog::query()
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
