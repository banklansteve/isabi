<?php

namespace Database\Seeders;

use App\Support\Hr\HrDefaults;
use Illuminate\Database\Seeder;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        HrDefaults::ensureAll();
    }
}
