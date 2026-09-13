<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use App\Models\SkillTag;
use App\Support\JobCategories;
use App\Support\SkillsCatalog;
use Illuminate\Database\Seeder;

class JobTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        if (! JobCategory::query()->exists()) {
            $sort = 10;
            foreach (config('job_categories', []) as $parent => $subs) {
                $category = JobCategory::query()->create([
                    'name' => $parent,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]);
                $sort += 10;

                $subSort = 10;
                foreach ($subs as $key => $value) {
                    if (is_string($key) && ! is_int($key)) {
                        $name = $key;
                        $phrase = (string) $value;
                    } else {
                        $name = (string) $value;
                        $phrase = 'recent work';
                    }

                    $category->subcategories()->create([
                        'name' => $name,
                        'review_phrase' => $phrase,
                        'sort_order' => $subSort,
                        'is_active' => true,
                    ]);
                    $subSort += 10;
                }
            }
        }

        if (! SkillTag::query()->exists()) {
            $sort = 10;
            foreach (config('skills', []) as $name) {
                SkillTag::query()->create([
                    'name' => $name,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]);
                $sort += 10;
            }
        }

        JobCategories::forgetCache();
        SkillsCatalog::forgetCache();
    }
}
