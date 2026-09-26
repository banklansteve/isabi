<?php

namespace App\Support;

use App\Models\Faq;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FaqContent
{
    /**
     * Category → tabler icon for the public accordion.
     *
     * @var array<string, string>
     */
    private const CATEGORY_ICONS = [
        'Getting started' => 'ti ti-rocket',
        'Reviews' => 'ti ti-star',
        'Referrals' => 'ti ti-users-group',
        'Logging jobs' => 'ti ti-clipboard-list',
        'My Page' => 'ti ti-world',
        'Your public page' => 'ti ti-world',
        'Subscriptions' => 'ti ti-coin',
        'Pricing' => 'ti ti-coin',
        'General' => 'ti ti-help',
    ];

    /**
     * Preferred public category order.
     *
     * @var list<string>
     */
    private const CATEGORY_ORDER = [
        'Getting started',
        'Reviews',
        'Referrals',
        'Logging jobs',
        'My Page',
        'Your public page',
        'Subscriptions',
        'Pricing',
        'General',
    ];

    /**
     * @return list<array{id: string, title: string, icon: string, items: list<array{id: string, question: string, answer: string, links?: list<array<string, mixed>>}>}>
     */
    public static function publicGroups(): array
    {
        $faqs = Faq::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return self::groupFaqs($faqs);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function featuredForHome(int $limit = 5): array
    {
        return Faq::query()
            ->featured()
            ->limit($limit)
            ->get()
            ->map(fn (Faq $faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])
            ->values()
            ->all();
    }

    /**
     * Schema.org FAQ entries for SEO.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function schemaEntries(int $limit = 12): array
    {
        return Faq::query()
            ->published()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(fn (Faq $faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Faq>  $faqs
     * @return list<array{id: string, title: string, icon: string, items: list<array{id: string, question: string, answer: string, links?: list<array<string, mixed>>}>}>
     */
    public static function groupFaqs(Collection $faqs): array
    {
        $grouped = $faqs
            ->groupBy(fn (Faq $faq) => $faq->category ?: 'General')
            ->map(function (Collection $items, string $category) {
                return [
                    'id' => Str::slug($category) ?: 'general',
                    'title' => $category,
                    'icon' => self::CATEGORY_ICONS[$category] ?? 'ti ti-help',
                    'items' => $items->map(fn (Faq $faq) => [
                        'id' => 'faq-'.$faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'links' => self::linksFor($faq),
                    ])->values()->all(),
                ];
            });

        return collect(self::CATEGORY_ORDER)
            ->filter(fn (string $cat) => $grouped->has($cat))
            ->map(fn (string $cat) => $grouped->get($cat))
            ->concat($grouped->reject(fn ($_, string $cat) => in_array($cat, self::CATEGORY_ORDER, true))->values())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function linksFor(Faq $faq): array
    {
        $q = Str::lower($faq->question);
        $cat = $faq->category ?: 'General';

        return match (true) {
            str_contains($q, 'how it works') || str_contains($q, 'full product flow') => [
                ['label' => 'How it works', 'route' => 'how-it-works'],
                ['label' => 'Create account', 'route' => 'register', 'guestOnly' => true],
            ],
            str_contains($q, 'set up my page') || str_contains($q, 'first time') => [
                ['label' => 'How it works', 'route' => 'how-it-works'],
                ['label' => 'Sign up free', 'route' => 'register', 'guestOnly' => true],
                ['label' => 'Dashboard', 'route' => 'dashboard', 'authOnly' => true],
            ],
            str_contains($q, 'client actually leave') || str_contains($q, 'request a review') || str_contains($q, 'review requests') => [
                ['label' => 'How reviews work', 'route' => 'how-it-works'],
                ['label' => 'Open dashboard', 'route' => 'dashboard', 'authOnly' => true],
            ],
            str_contains($q, 'write my own') => [
                ['label' => 'Why honesty matters', 'route' => 'about'],
            ],
            str_contains($q, 'trades') || str_contains($q, 'directory') => [
                ['label' => 'Browse artisans', 'route' => 'public.directory'],
            ],
            str_contains($q, 'share my page') || str_contains($q, 'public page') || ($cat === 'My Page' && ! str_contains($q, 'contact details')) => [
                ['label' => 'How it works', 'route' => 'how-it-works'],
                ['label' => 'Edit profile', 'route' => 'profile.edit', 'authOnly' => true],
            ],
            str_contains($q, 'upgrade') || str_contains($q, 'subscription') || str_contains($q, 'pay for') || str_contains($q, 'hidden fees') || $cat === 'Subscriptions' => [
                ['label' => 'Buy tokens', 'route' => 'tokens.buy', 'authOnly' => true],
                ['label' => 'Contact us', 'route' => 'contact'],
            ],
            str_contains($q, 'privacy') || str_contains($q, 'data protected') => [
                ['label' => 'Privacy Policy', 'route' => 'privacy'],
                ['label' => 'Terms of use', 'route' => 'terms'],
            ],
            str_contains($q, 'contact support') => [
                ['label' => 'Contact', 'route' => 'contact'],
                ['label' => 'Help & support', 'route' => 'help.index', 'authOnly' => true],
                ['label' => 'Careers', 'route' => 'careers'],
            ],
            str_contains($q, 'log a job') || str_contains($q, 'logging') || str_contains($q, 'include when i log') || str_contains($q, 'edit a job') || str_contains($q, 'photos and media') || $cat === 'Logging jobs' => [
                ['label' => 'How it works', 'route' => 'how-it-works'],
                ['label' => 'Log a job', 'route' => 'work-log.create', 'authOnly' => true],
            ],
            str_contains($q, 'referral') || $cat === 'Referrals' => [
                ['label' => 'How it works', 'route' => 'how-it-works'],
            ],
            str_contains($q, 'marketplace') || str_contains($q, 'leads') => [
                ['label' => 'About Kraftrack', 'route' => 'about'],
                ['label' => 'How it works', 'route' => 'how-it-works'],
            ],
            default => [],
        };
    }
}
