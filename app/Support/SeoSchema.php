<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Collection;

/**
 * Builds schema.org JSON-LD graphs.
 *
 * Ratings are only ever emitted from reviews a client submitted through their
 * own invite link — an artisan can't write, edit or approve one — so the
 * aggregateRating markup reflects genuine third-party feedback.
 */
class SeoSchema
{
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => config('app.name', 'Isabi'),
            'url' => url('/'),
            'description' => 'Isabi gives Nigerian artisans a public page built from real finished jobs and reviews written by their clients.',
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Nigeria',
            ],
        ];
    }

    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => config('app.name', 'Isabi'),
            'url' => url('/'),
            'publisher' => ['@id' => url('/').'#organization'],
        ];
    }

    /**
     * @param  Collection<int, WorkLog>  $reviewedJobs  jobs that carry a client review
     */
    public static function artisan(
        User $user,
        ?float $avgRating,
        int $reviewCount,
        Collection $reviewedJobs,
    ): array {
        $url = route('public.profile', $user->slug);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            '@id' => $url.'#business',
            'name' => $user->displayBusinessName(),
            'url' => $url,
            'isPartOf' => ['@id' => url('/').'#website'],
        ];

        if (filled($user->bio)) {
            $schema['description'] = $user->bio;
        }

        if (filled($user->trade)) {
            $schema['additionalType'] = $user->trade;
            $schema['knowsAbout'] = array_values(array_filter(
                array_merge([$user->trade], $user->skills ?? []),
            ));
        }

        if (filled($user->avatar_url)) {
            $schema['image'] = $user->avatar_url;
        }

        if (filled($user->state) || filled($user->lga)) {
            $schema['address'] = array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $user->lga,
                'addressRegion' => $user->state,
                'addressCountry' => 'NG',
            ]);
        }

        $areas = array_values(array_filter(array_merge(
            $user->coverage_areas ?? [],
            [$user->lga, $user->state],
        )));

        if ($areas !== []) {
            $schema['areaServed'] = array_map(
                fn (string $area) => ['@type' => 'Place', 'name' => $area],
                array_slice(array_unique($areas), 0, 12),
            );
        }

        if ($avgRating !== null && $reviewCount > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $avgRating,
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $reviews = $reviewedJobs
            ->take(10)
            ->map(fn (WorkLog $log) => self::review($log, $user))
            ->filter()
            ->values()
            ->all();

        if ($reviews !== []) {
            $schema['review'] = $reviews;
        }

        return $schema;
    }

    public static function review(WorkLog $log, User $user): ?array
    {
        $review = $log->review;

        if (! $review) {
            return null;
        }

        return array_filter([
            '@type' => 'Review',
            'datePublished' => $review->submitted_at?->toDateString(),
            'reviewBody' => $review->comment,
            'name' => $log->description,
            'author' => [
                '@type' => 'Person',
                'name' => $review->client_display_name ?: 'Verified client',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (float) $review->rating,
                'bestRating' => 5,
                'worstRating' => 1,
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** A single finished job, presented as the service that was delivered. */
    public static function job(WorkLog $log, User $user): array
    {
        $url = route('public.job', [$user->slug, $log->slug]);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            '@id' => $url.'#service',
            'name' => $log->description,
            'url' => $url,
            'serviceType' => JobCategories::displayLabel($log->job_category, $log->job_subcategory)
                ?: $user->trade,
            'provider' => [
                '@type' => 'ProfessionalService',
                '@id' => route('public.profile', $user->slug).'#business',
                'name' => $user->displayBusinessName(),
                'url' => route('public.profile', $user->slug),
            ],
        ];

        $area = collect([$log->service_city, $log->service_lga, $log->service_state])
            ->filter()
            ->implode(', ');

        if ($area !== '') {
            $schema['areaServed'] = ['@type' => 'Place', 'name' => $area];
        }

        $images = $log->media
            ->filter(fn ($m) => $m->isImage())
            ->map(fn ($m) => $m->previewUrl(1600))
            ->filter()
            ->take(6)
            ->values()
            ->all();

        if ($images !== []) {
            $schema['image'] = $images;
        }

        if ($review = self::review($log, $user)) {
            $schema['review'] = [$review];
        }

        return $schema;
    }

    /** @param array<int, array{name: string, url: string}> $crumbs */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (int $i, array $crumb) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ],
                array_keys($crumbs),
                $crumbs,
            ),
        ];
    }

    /** @param array<int, array{name: string, url: string}> $items */
    public static function itemList(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'numberOfItems' => count($items),
            'itemListElement' => array_map(
                fn (int $i, array $item) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $item['name'],
                    'url' => $item['url'],
                ],
                array_keys($items),
                $items,
            ),
        ];
    }

    /** @param array<int, array{question: string, answer: string}> $entries */
    public static function faq(array $entries): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(
                fn (array $entry) => [
                    '@type' => 'Question',
                    'name' => $entry['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $entry['answer'],
                    ],
                ],
                $entries,
            ),
        ];
    }
}
