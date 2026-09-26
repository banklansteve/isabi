<?php

namespace App\Support;

use App\Models\SkillTag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SkillsCatalog
{
    public const CACHE_KEY = 'kraftrack.skill_tags';

    /**
     * @return list<string>
     */
    public static function suggestions(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            if (Schema::hasTable('skill_tags') && SkillTag::query()->exists()) {
                return SkillTag::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name')
                    ->values()
                    ->all();
            }

            /** @var list<string> $skills */
            $skills = config('skills', []);

            return array_values($skills);
        });
    }

    /**
     * Skills for a job category only — not the full catalog.
     *
     * @return list<string>
     */
    public static function suggestionsForCategory(?string $category): array
    {
        if ($category === null || $category === '') {
            return [];
        }

        /** @var array<string, list<string>> $groups */
        $groups = config('skill_groups', []);
        $preferred = array_values(array_unique($groups[$category] ?? []));

        if ($preferred === []) {
            return [];
        }

        $all = self::suggestions();
        $ordered = [];

        foreach ($preferred as $skill) {
            $match = collect($all)->first(
                fn (string $s) => mb_strtolower($s) === mb_strtolower($skill),
            );
            $ordered[] = $match ?: $skill;
        }

        return array_values(array_unique($ordered));
    }

    /**
     * Skills relevant to selected trades (and optional explicit category).
     *
     * @param  list<string>|null  $trades
     * @return list<string>
     */
    public static function suggestionsForTrades(?array $trades, ?string $category = null): array
    {
        $parents = [];

        if (filled($category)) {
            $parents[] = (string) $category;
        }

        foreach ($trades ?? [] as $trade) {
            $parent = JobCategories::parentFor(is_string($trade) ? $trade : null);
            if ($parent) {
                $parents[] = $parent;
            }
        }

        $parents = array_values(array_unique($parents));

        if ($parents === []) {
            return [];
        }

        $skills = [];
        foreach ($parents as $parent) {
            $skills = [...$skills, ...self::suggestionsForCategory($parent)];
        }

        return array_values(array_unique($skills));
    }

    /**
     * @return array{all: list<string>, groups: array<string, list<string>>}
     */
    public static function forFrontend(): array
    {
        return [
            'all' => self::suggestions(),
            'groups' => config('skill_groups', []),
        ];
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
