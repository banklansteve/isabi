<?php

namespace App\Support;

use Illuminate\Support\Str;

class ArtisanDirectory
{
    /**
     * URL slug for a trade label (e.g. "Electrician" → "electrician").
     */
    public static function tradeSlug(string $trade): string
    {
        return Str::slug($trade);
    }

    public static function stateSlug(string $state): string
    {
        return Str::slug($state);
    }

    /**
     * Resolve a trade slug back to the canonical label from config.
     */
    public static function tradeFromSlug(string $slug): ?string
    {
        $slug = Str::slug($slug);

        foreach (config('trades', []) as $trade) {
            if (! is_string($trade) || $trade === '' || strcasecmp($trade, 'Other') === 0) {
                continue;
            }

            if (self::tradeSlug($trade) === $slug) {
                return $trade;
            }
        }

        return null;
    }

    /**
     * Resolve a state slug back to the canonical NigeriaLocations label.
     */
    public static function stateFromSlug(string $slug): ?string
    {
        $slug = Str::slug($slug);

        foreach (NigeriaLocations::states() as $state) {
            if (self::stateSlug($state) === $slug) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Pluralised display name for SEO headings (e.g. "Electrician" → "Electricians").
     */
    public static function tradePlural(string $trade): string
    {
        $trade = trim($trade);

        if ($trade === '') {
            return 'Artisans';
        }

        // Already plural-ish compound labels keep as-is.
        if (str_contains($trade, ' / ') || str_ends_with(strtolower($trade), 's')) {
            return $trade;
        }

        return $trade.'s';
    }

    /**
     * @return list<string>
     */
    public static function catalogTrades(): array
    {
        return collect(config('trades', []))
            ->filter(fn ($t) => is_string($t) && $t !== '' && strcasecmp($t, 'Other') !== 0)
            ->values()
            ->all();
    }
}
