<?php

namespace App\Support;

use App\Models\ProfileSlugRedirect;
use App\Models\User;
use Illuminate\Support\Str;

class ProfileSlug
{
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Transliterate accents → ASCII where possible.
        $ascii = Str::ascii($value);
        $slug = Str::lower($ascii);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = preg_replace('/-+/', '-', $slug) ?? '';

        return $slug;
    }

    public static function isReserved(string $slug): bool
    {
        $slug = Str::lower($slug);
        $reserved = array_map('strtolower', config('profiles.reserved', []));

        return in_array($slug, $reserved, true);
    }

    /**
     * Build a unique slug from a preferred base (business name).
     * Appends -2, -3, … when needed.
     */
    public static function uniqueFrom(string $preferred, ?int $ignoreUserId = null): string
    {
        $base = self::normalize($preferred);
        if ($base === '' || self::isReserved($base)) {
            $base = 'artisan';
        }

        $candidate = $base;
        $suffix = 2;

        while (self::isTaken($candidate, $ignoreUserId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
            if ($suffix > 9999) {
                $candidate = $base.'-'.Str::lower(Str::random(4));
                break;
            }
        }

        return $candidate;
    }

    public static function isTaken(string $slug, ?int $ignoreUserId = null): bool
    {
        if (self::isReserved($slug)) {
            return true;
        }

        $exists = User::query()
            ->where('slug', $slug)
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();

        if ($exists) {
            return true;
        }

        return ProfileSlugRedirect::query()
            ->where('from_slug', $slug)
            ->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))
            ->exists();
    }

    public static function available(string $slug, ?int $ignoreUserId = null): bool
    {
        $slug = self::normalize($slug);

        return $slug !== '' && ! self::isTaken($slug, $ignoreUserId);
    }
}
