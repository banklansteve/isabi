<?php

namespace Database\Seeders;

use App\Models\StaffRole;
use Illuminate\Database\Seeder;

class StaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('admin.roles', []) as $index => $role) {
            StaffRole::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'icon' => $role['icon'] ?? null,
                    'permissions' => $role['permissions'] ?? [],
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
