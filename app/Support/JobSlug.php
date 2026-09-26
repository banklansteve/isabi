<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * SEO-safe public path context for jobs.
 *
 * Built only from catalog category/subcategory labels (never free-text subject,
 * description, client names, or locations). The opaque job reference is a
 * separate path segment: /p/{artisan}/{seo-context}/{reference}
 */
class JobSlug
{
    private const MAX_WORDS = 5;

    private const MIN_WORDS = 3;

    private const MAX_LENGTH = 60;

    /** @var list<string> */
    private const RESERVED = ['reviews', 'jobs', 'share', 'about', 'edit', 'quote', 'embed'];

    public static function normalize(?string $value): string
    {
        $slug = Str::slug(Str::ascii((string) $value));

        return $slug !== '' ? $slug : 'job';
    }

    /**
     * 3–5 word SEO context from safe catalog fields only.
     */
    public static function seoPrefix(?string $category, ?string $subcategory): string
    {
        $phrase = JobCategories::reviewPhrase($category, $subcategory);

        if (blank($phrase)) {
            $phrase = collect([$subcategory, $category])
                ->map(fn ($part) => is_string($part) ? trim($part) : '')
                ->filter()
                ->implode(' ');
        }

        if (blank($phrase)) {
            $phrase = 'completed job work';
        }

        $slug = self::limitWords(self::normalize($phrase));

        if (in_array($slug, self::RESERVED, true) || $slug === 'job') {
            $slug = 'completed-job-work';
        }

        return $slug;
    }

    /**
     * Canonical relative path after the artisan slug: {seo-context}/{reference}
     */
    public static function compose(?string $prefix, string $reference): string
    {
        $prefix = self::normalize($prefix);
        if ($prefix === '' || $prefix === 'job') {
            $prefix = 'completed-job-work';
        }

        return $prefix.'/'.Str::lower($reference);
    }

    /**
     * Legacy hyphenated segment: {seo-context}-{reference}
     */
    public static function composeLegacy(?string $prefix, string $reference): string
    {
        $prefix = self::normalize($prefix);
        if ($prefix === '' || $prefix === 'job') {
            $prefix = 'completed-job-work';
        }

        return $prefix.'-'.Str::lower($reference);
    }

    /**
     * Extract the opaque reference from a path segment or full relative path.
     */
    public static function extractReference(string $segment): ?string
    {
        $segment = Str::lower(trim($segment, "/ \t"));

        if (str_contains($segment, '/')) {
            $parts = explode('/', $segment);
            $tail = end($parts) ?: '';

            return JobReference::isValid($tail) ? $tail : null;
        }

        if (JobReference::isValid($segment)) {
            return $segment;
        }

        if (preg_match('/-([a-z0-9]{'.JobReference::MIN_LENGTH.','.JobReference::MAX_LENGTH.'})$/', $segment, $matches)) {
            $candidate = $matches[1];
            if (JobReference::isValid($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @deprecated Use seoPrefix() — kept for callers that still pass a free-text source.
     */
    public static function uniqueFor(int $userId, ?string $description, ?int $ignoreId = null): string
    {
        return self::seoPrefix(null, $description);
    }

    private static function limitWords(string $slug): string
    {
        $parts = array_values(array_filter(explode('-', $slug), fn ($p) => $p !== ''));

        if ($parts === []) {
            $parts = ['completed', 'job', 'work'];
        }

        $pads = ['work', 'job', 'done'];
        $i = 0;
        while (count($parts) < self::MIN_WORDS && $i < count($pads)) {
            $parts[] = $pads[$i];
            $i++;
        }

        $parts = array_slice($parts, 0, self::MAX_WORDS);
        $joined = implode('-', $parts);

        if (strlen($joined) <= self::MAX_LENGTH) {
            return $joined;
        }

        $trimmed = substr($joined, 0, self::MAX_LENGTH);
        $lastHyphen = strrpos($trimmed, '-');

        return $lastHyphen !== false && $lastHyphen > 12
            ? substr($trimmed, 0, $lastHyphen)
            : $trimmed;
    }
}
