<?php

namespace App\Support;

class SearchReferrer
{
    /**
     * Hosts (or suffixes) that indicate the visitor arrived from a search engine.
     * Checked against the HTTP Referer host only — never query params or utm tags.
     *
     * @var list<string>
     */
    private const SEARCH_HOSTS = [
        'google.',
        'bing.com',
        'yahoo.',
        'duckduckgo.com',
        'baidu.com',
        'yandex.',
        'ecosia.org',
        'brave.com',
        'search.brave.com',
        'ask.com',
        'aol.com',
        'startpage.com',
        'qwant.com',
        'seznam.cz',
        'naver.com',
        'daum.net',
    ];

    public static function isSearch(?string $referer): bool
    {
        $host = self::host($referer);

        if ($host === null) {
            return false;
        }

        foreach (self::SEARCH_HOSTS as $needle) {
            if (str_contains($host, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function host(?string $referer): ?string
    {
        if (! filled($referer)) {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower($host);
    }
}
