<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;

class QuoteReference
{
    /** Uppercase letters + digits — no ambiguous I/O/0/1. */
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const MIN_BODY = 6;

    public const MAX_BODY = 7;

    public const PREFIX = 'Q-';

    /**
     * Short public quote id, e.g. Q-A3K9XM or Q-7H2BN4K.
     */
    public static function unique(?int $ignoreQuoteId = null, ?int $ignoreRequestId = null): string
    {
        do {
            $id = self::generate();
        } while (self::exists($id, $ignoreQuoteId, $ignoreRequestId));

        return $id;
    }

    public static function isValid(?string $id): bool
    {
        if ($id === null || $id === '') {
            return false;
        }

        return (bool) preg_match(
            '/^Q-['.self::CHARSET.']{'.self::MIN_BODY.','.self::MAX_BODY.'}$/',
            $id,
        );
    }

    private static function generate(): string
    {
        $length = random_int(self::MIN_BODY, self::MAX_BODY);
        $body = '';
        $max = strlen(self::CHARSET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $body .= self::CHARSET[random_int(0, $max)];
        }

        return self::PREFIX.$body;
    }

    private static function exists(string $id, ?int $ignoreQuoteId, ?int $ignoreRequestId): bool
    {
        $quoteTaken = ArtisanQuote::query()
            ->when($ignoreQuoteId, fn ($q) => $q->where('id', '!=', $ignoreQuoteId))
            ->where('quote_number', $id)
            ->exists();

        if ($quoteTaken) {
            return true;
        }

        return QuoteRequest::query()
            ->when($ignoreRequestId, fn ($q) => $q->where('id', '!=', $ignoreRequestId))
            ->where('client_token', $id)
            ->exists();
    }
}
