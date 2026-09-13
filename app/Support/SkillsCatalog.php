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

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
