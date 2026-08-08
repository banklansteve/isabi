<?php

namespace App\Support;

class JobCategories
{
    /**
     * Raw config: category => [ subcategory => review_phrase ]
     *
     * @return array<string, array<string, string>>
     */
    public static function all(): array
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
                    // Legacy list format — phrase equals a safe fallback.
                    $map[(string) $value] = self::fallbackPhrase((string) $value);
                }
            }
            $normalized[$parent] = $map;
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public static function parents(): array
    {
        return array_keys(self::all());
    }

    /**
     * Subcategory labels for a parent category.
     *
     * @return list<string>
     */
    public static function subcategoriesFor(?string $parent): array
    {
        if ($parent === null || $parent === '') {
            return [];
        }

        return array_keys(self::all()[$parent] ?? []);
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

    /**
     * Private WhatsApp phrase for a subcategory (never public).
     */
    public static function reviewPhrase(?string $parent, ?string $subcategory): ?string
    {
        if (! self::isValidPair($parent, $subcategory)) {
            return null;
        }

        $phrase = self::all()[$parent][$subcategory] ?? null;

        return filled($phrase) ? (string) $phrase : null;
    }

    /**
     * Payload shape for Inertia forms (labels only — phrases stay server-side).
     *
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

    private static function fallbackPhrase(string $label): string
    {
        return 'recent work';
    }
}
