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
        'Your public page' => 'ti ti-world',
        'Pricing' => 'ti ti-coin',
        'General' => 'ti ti-help',
    ];

    /**
     * @return list<array{id: string, title: string, icon: string, items: list<array{id: string, question: string, answer: string}>}>
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
     * @return list<array{id: string, title: string, icon: string, items: list<array{id: string, question: string, answer: string}>}>
     */
    public static function groupFaqs(Collection $faqs): array
    {
        return $faqs
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
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
