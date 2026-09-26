<?php

namespace Tests\Unit;

use App\Support\ArtisanDirectory;
use App\Support\SearchReferrer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchReferrerAndDirectoryTest extends TestCase
{
    #[DataProvider('searchReferers')]
    public function test_detects_search_engine_referers(?string $referer, bool $expected): void
    {
        $this->assertSame($expected, SearchReferrer::isSearch($referer));
    }

    public static function searchReferers(): array
    {
        return [
            'google' => ['https://www.google.com/search?q=electrician+lagos', true],
            'bing' => ['https://www.bing.com/search?q=plumber', true],
            'duckduckgo' => ['https://duckduckgo.com/?q=tiler', true],
            'direct' => [null, false],
            'empty' => ['', false],
            'whatsapp' => ['https://wa.me/', false],
            'self' => ['https://kraftrack.com/artisans', false],
            'utm_only_ignored' => ['https://example.com/?utm_source=google', false],
        ];
    }

    public function test_trade_and_state_slug_round_trip(): void
    {
        $this->assertSame('electrician', ArtisanDirectory::tradeSlug('Electrician'));
        $this->assertSame('Electrician', ArtisanDirectory::tradeFromSlug('electrician'));
        $this->assertSame('lagos', ArtisanDirectory::stateSlug('Lagos'));
        $this->assertSame('Lagos', ArtisanDirectory::stateFromSlug('lagos'));
        $this->assertSame('fct', ArtisanDirectory::stateSlug('FCT'));
        $this->assertSame('FCT', ArtisanDirectory::stateFromSlug('fct'));
        $this->assertSame('Electricians', ArtisanDirectory::tradePlural('Electrician'));
    }
}
