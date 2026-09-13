<?php

namespace App\Support;

use App\Models\JobCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class JobCategories
{
    public const CACHE_KEY = 'kraftrack.job_categories.map';

    /**
     * Raw map: category => [ subcategory => review_phrase ]
     *
     * @return array<string, array<string, string>>
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            if (Schema::hasTable('job_categories') && JobCategory::query()->exists()) {
                return self::fromDatabase();
            }

            return self::fromConfig();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    public static function parents(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return list<string>
     */
    public static function subcategoriesFor(?string $parent): array
    {
        if ($parent === null || $parent === '') {
            return [];
        }

        return array_keys(self::all()[$parent] ?? []);
    }

    /**
     * Flat trade labels for registration / profile (subcategories + Other).
     *
     * @return list<string>
     */
    public static function tradeLabels(): array
    {
        $labels = [];
        foreach (self::all() as $subs) {
            foreach (array_keys($subs) as $sub) {
                $labels[$sub] = true;
            }
        }

        $list = array_keys($labels);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        if (! in_array('Other', $list, true)) {
            $list[] = 'Other';
        }

        return array_values($list);
    }

    public static function isValidParent(?string $parent): bool
    {
        return $parent !== null
            && $parent !== ''
            && array_key_exists($parent, self::all());
    }

    public static function isValidPair(?string $parent, ?string $subcategory): bool
    {
        if (! self::isValidParent($parent) || blank($subcategory)) {
            return false;
        }

        return array_key_exists($subcategory, self::all()[$parent] ?? []);
    }

    public static function reviewPhrase(?string $parent, ?string $subcategory): ?string
    {
        if (! self::isValidPair($parent, $subcategory)) {
            return null;
        }

        $phrase = self::all()[$parent][$subcategory] ?? null;

        return filled($phrase) ? (string) $phrase : null;
    }

    /**
     * @return array{parents: list<string>, groups: array<string, list<string>>}
     */
    public static function forFrontend(): array
    {
        $groups = [];
        foreach (self::all() as $parent => $subs) {
            $groups[$parent] = array_keys($subs);
        }

        return [
            'parents' => array_keys($groups),
            'groups' => $groups,
        ];
    }

    public static function displayLabel(?string $parent, ?string $subcategory): ?string
    {
        if (filled($subcategory) && filled($parent)) {
            return $subcategory;
        }

        if (filled($subcategory)) {
            return $subcategory;
        }

        if (filled($parent)) {
            return $parent;
        }

        return null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function fromDatabase(): array
    {
        $normalized = [];

        $categories = JobCategory::query()
            ->active()
            ->with(['subcategories' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $map = [];
            foreach ($category->subcategories as $sub) {
                $map[$sub->name] = filled($sub->review_phrase)
                    ? (string) $sub->review_phrase
                    : self::fallbackPhrase($sub->name);
            }
            if ($map !== []) {
                $normalized[$category->name] = $map;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function fromConfig(): array
    {
        /** @var array<string, array<string, string>|list<string>> $groups */
        $groups = config('job_categories', []);

        $normalized = [];

        foreach ($groups as $parent => $subs) {
            $map = [];
            foreach ($subs as $key => $value) {
                if (is_string($key) && ! is_int($key)) {
                    $map[$key] = (string) $value;
                } else {
                    $map[(string) $value] = self::fallbackPhrase((string) $value);
                }
            }
            $normalized[$parent] = $map;
        }

        return $normalized;
    }

    private static function fallbackPhrase(string $label): string
    {
        return 'recent work';
    }
}
