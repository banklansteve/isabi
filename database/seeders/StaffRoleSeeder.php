<?php

namespace Database\Seeders;

use App\Models\StaffRole;
use Illuminate\Database\Seeder;

class StaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        $stripMessaging = [
            'customer_support',
            'onboarding_followup',
            'reengagement',
        ];

        foreach (config('admin.roles', []) as $index => $role) {
            $existing = StaffRole::query()->where('slug', $role['slug'])->first();

            // Built-in duties are editable by the Super Admin, so we never
            // clobber a role that already exists — we only create the ones that
            // are missing and keep any customisations intact.
            if ($existing) {
                if ($existing->sort_order === null) {
                    $existing->forceFill(['sort_order' => $index + 1])->save();
                }

                // Announcements moved to Super Admin — strip from ops duties that
                // previously carried messaging.manage by default.
                if (in_array($role['slug'], $stripMessaging, true)) {
                    $permissions = collect($existing->permissions ?? [])
                        ->reject(fn ($key) => $key === 'admin.messaging.manage')
                        ->values()
                        ->all();

                    if ($permissions !== ($existing->permissions ?? [])) {
                        $existing->forceFill(['permissions' => $permissions])->save();
                    }
                }

                continue;
            }

            StaffRole::query()->create([
                'slug' => $role['slug'],
                'name' => $role['name'],
                'description' => $role['description'] ?? null,
                'icon' => $role['icon'] ?? null,
                'permissions' => $role['permissions'] ?? [],
                'is_system' => true,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
