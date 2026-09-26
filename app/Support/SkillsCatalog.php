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
     * Skills prioritised for a job category (group first, then full catalog).
     *
     * @return list<string>
     */
    public static function suggestionsForCategory(?string $category): array
    {
        $all = self::suggestions();
        if ($category === null || $category === '') {
            return $all;
        }

        /** @var array<string, list<string>> $groups */
        $groups = config('skill_groups', []);
        $preferred = array_values(array_unique($groups[$category] ?? []));

        if ($preferred === []) {
            return $all;
        }

        $preferredLower = array_map(fn (string $s) => mb_strtolower($s), $preferred);
        $rest = array_values(array_filter(
            $all,
            fn (string $skill) => ! in_array(mb_strtolower($skill), $preferredLower, true),
        ));

        // Prefer config group order, then any matching catalog names, then the rest.
        $ordered = [];
        foreach ($preferred as $skill) {
            $match = collect($all)->first(
                fn (string $s) => mb_strtolower($s) === mb_strtolower($skill),
            );
            $ordered[] = $match ?: $skill;
        }

        return array_values(array_unique([...$ordered, ...$rest]));
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
