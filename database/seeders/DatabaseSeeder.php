<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleUserSeeder::class,
            StaffRoleSeeder::class,
            AnnouncementTemplateSeeder::class,
            SupportChatTemplateSeeder::class,
            FaqSeeder::class,
            JobTaxonomySeeder::class,
        ]);
    }
}
