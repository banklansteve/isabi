<?php

namespace Database\Seeders;

use App\Support\SupportChat\SupportChatTemplates;
use Illuminate\Database\Seeder;

class SupportChatTemplateSeeder extends Seeder
{
    public function run(): void
    {
        SupportChatTemplates::ensure();
    }
}
