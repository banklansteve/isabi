<?php

namespace App\Support\Tokens;

class TokenCatalog
{
    /**
     * @return list<array{key: string, name: string, tokens: int, price: int, per_token: float, popular: bool}>
     */
    public static function packs(): array
    {
        $packs = collect(config('pricing.credits.packs', []));
        $popularKey = 'standard';

        return $packs
            ->map(function (array $pack) use ($popularKey) {
                $tokens = (int) ($pack['credits'] ?? $pack['tokens'] ?? 0);
                $price = (int) ($pack['price'] ?? 0);

                return [
                    'key' => (string) $pack['key'],
                    'name' => (string) $pack['name'],
                    'tokens' => $tokens,
                    'price' => $price,
                    'per_token' => $tokens > 0 ? round($price / $tokens, 1) : 0,
                    'popular' => ($pack['key'] ?? '') === $popularKey,
                ];
            })
            ->filter(fn (array $pack) => $pack['tokens'] > 0 && $pack['price'] > 0)
            ->values()
            ->all();
    }

    public static function pack(string $key): ?array
    {
        return collect(self::packs())->firstWhere('key', $key);
    }
}
