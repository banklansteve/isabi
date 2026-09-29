<?php

namespace Database\Seeders;

use App\Models\StaffRole;
use App\Models\StaffRoleAssignment;
use Illuminate\Database\Seeder;

class StaffRoleSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $growthLegacy = [
        'growth_ops',
        'referral_monitoring',
        'onboarding_followup',
        'reengagement',
        'verification_officer',
        'verification',
    ];

    public function run(): void
    {
        foreach (config('admin.roles', []) as $index => $role) {
            $assignable = (bool) ($role['assignable'] ?? true);

            $model = StaffRole::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'icon' => $role['icon'] ?? null,
                    'is_system' => true,
                    'is_active' => true,
                    'is_assignable' => $assignable,
                    'sort_order' => $index + 1,
                ],
            );

            // Always refresh built-in permission bundles from config so merges stick.
            $model->forceFill([
                'permissions' => $role['permissions'] ?? [],
                'is_assignable' => $assignable,
                'is_active' => true,
                'name' => $role['name'],
                'description' => $role['description'] ?? null,
            ])->save();
        }

        $this->retireLegacyGrowthRoles();
        $this->retireUnknownSystemRoles();
        $this->purgeRetiredOpsDutyRows();
    }

    private function retireLegacyGrowthRoles(): void
    {
        $canonical = StaffRole::query()->where('slug', 'growth_lifecycle')->first();
        $legacy = StaffRole::query()
            ->whereIn('slug', ['growth_ops', 'referral_monitoring', 'onboarding_followup', 'reengagement'])
            ->get();

        if ($canonical) {
            foreach ($legacy as $old) {
                $assignments = StaffRoleAssignment::query()
                    ->where('staff_role_id', $old->id)
                    ->get();

                foreach ($assignments as $assignment) {
                    StaffRoleAssignment::query()->firstOrCreate(
                        [
                            'user_id' => $assignment->user_id,
                            'staff_role_id' => $canonical->id,
                        ],
                        [
                            'assigned_by_user_id' => $assignment->assigned_by_user_id,
                            'assigned_at' => $assignment->assigned_at ?? now(),
                        ],
                    );
                }

                StaffRoleAssignment::query()->where('staff_role_id', $old->id)->delete();
            }
        }

        StaffRole::query()
            ->whereIn('slug', ['growth_ops', 'referral_monitoring', 'onboarding_followup', 'reengagement'])
            ->update([
                'is_active' => false,
                'is_assignable' => false,
            ]);

        StaffRole::query()
            ->where('is_assignable', false)
            ->whereIn('slug', ['knowledge_base', 'people_hr', 'people_discipline', 'content_comms'])
            ->get()
            ->each(function (StaffRole $role) {
                StaffRoleAssignment::query()->where('staff_role_id', $role->id)->delete();
            });

        $this->foldLegacyInto([
            'trust_safety' => 'moderation',
            'verification_officer' => 'moderation',
            'verification' => 'moderation',
        ]);
    }

    /**
     * Remove retired ops duty rows so they never appear under Super Admin–only.
     */
    private function purgeRetiredOpsDutyRows(): void
    {
        $legacySlugs = [
            'growth_ops',
            'referral_monitoring',
            'onboarding_followup',
            'reengagement',
            'verification_officer',
            'verification',
            'trust_safety',
            'support_agent',
        ];

        $ids = StaffRole::query()->whereIn('slug', $legacySlugs)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        StaffRoleAssignment::query()->whereIn('staff_role_id', $ids)->delete();
        StaffRole::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param  array<string, string>  $map
     */
    private function foldLegacyInto(array $map): void
    {
        foreach ($map as $fromSlug => $toSlug) {
            $from = StaffRole::query()->where('slug', $fromSlug)->first();
            $to = StaffRole::query()->where('slug', $toSlug)->first();

            if (! $from || ! $to) {
                continue;
            }

            $assignments = StaffRoleAssignment::query()
                ->where('staff_role_id', $from->id)
                ->get();

            foreach ($assignments as $assignment) {
                StaffRoleAssignment::query()->firstOrCreate(
                    [
                        'user_id' => $assignment->user_id,
                        'staff_role_id' => $to->id,
                    ],
                    [
                        'assigned_by_user_id' => $assignment->assigned_by_user_id,
                        'assigned_at' => $assignment->assigned_at ?? now(),
                    ],
                );
            }

            StaffRoleAssignment::query()->where('staff_role_id', $from->id)->delete();
            $from->forceFill([
                'is_active' => false,
                'is_assignable' => false,
            ])->save();
        }
    }

    private function retireUnknownSystemRoles(): void
    {
        $known = collect(config('admin.roles', []))->pluck('slug')->filter()->all();

        StaffRole::query()
            ->where('is_system', true)
            ->whereNotIn('slug', $known)
            ->update([
                'is_active' => false,
                'is_assignable' => false,
            ]);
    }
}
