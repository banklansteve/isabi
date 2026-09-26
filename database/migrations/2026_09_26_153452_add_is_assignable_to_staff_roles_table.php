<?php

use App\Models\StaffRole;
use App\Models\StaffRoleAssignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Growth duties that fold into growth_lifecycle.
     *
     * @var list<string>
     */
    private array $growthLegacy = [
        'growth_ops',
        'referral_monitoring',
        'onboarding_followup',
        'reengagement',
    ];

    /**
     * Super-admin-only duties — never assignable to ops staff.
     *
     * @var list<string>
     */
    private array $superAdminOnly = [
        'knowledge_base',
        'people_hr',
        'people_discipline',
        'content_comms',
    ];

    public function up(): void
    {
        Schema::table('staff_roles', function (Blueprint $table) {
            $table->boolean('is_assignable')->default(true)->after('is_active');
        });

        $this->syncFromConfig();
        $this->mergeGrowthAssignments();
        $this->detachSuperAdminOnlyAssignments();
    }

    public function down(): void
    {
        Schema::table('staff_roles', function (Blueprint $table) {
            $table->dropColumn('is_assignable');
        });
    }

    private function syncFromConfig(): void
    {
        foreach (config('admin.roles', []) as $index => $role) {
            $assignable = (bool) ($role['assignable'] ?? true);

            StaffRole::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'icon' => $role['icon'] ?? null,
                    'permissions' => $role['permissions'] ?? [],
                    'is_system' => true,
                    'is_active' => true,
                    'is_assignable' => $assignable,
                    'sort_order' => $index + 1,
                ],
            );
        }

        // Legacy growth slugs stay in DB for history but are no longer assignable.
        StaffRole::query()
            ->whereIn('slug', $this->growthLegacy)
            ->update([
                'is_assignable' => false,
                'is_active' => false,
            ]);
    }

    private function mergeGrowthAssignments(): void
    {
        $canonical = StaffRole::query()->where('slug', 'growth_lifecycle')->first();
        if (! $canonical) {
            return;
        }

        $legacyIds = StaffRole::query()
            ->whereIn('slug', $this->growthLegacy)
            ->pluck('id');

        if ($legacyIds->isEmpty()) {
            return;
        }

        $staffIds = StaffRoleAssignment::query()
            ->whereIn('staff_role_id', $legacyIds)
            ->pluck('user_id')
            ->unique();

        foreach ($staffIds as $staffId) {
            StaffRoleAssignment::query()->firstOrCreate(
                [
                    'user_id' => $staffId,
                    'staff_role_id' => $canonical->id,
                ],
                [
                    'assigned_at' => now(),
                ],
            );
        }

        StaffRoleAssignment::query()
            ->whereIn('staff_role_id', $legacyIds)
            ->delete();
    }

    private function detachSuperAdminOnlyAssignments(): void
    {
        $ids = StaffRole::query()
            ->whereIn('slug', $this->superAdminOnly)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        StaffRoleAssignment::query()
            ->whereIn('staff_role_id', $ids)
            ->delete();

        StaffRole::query()
            ->whereIn('id', $ids)
            ->update(['is_assignable' => false]);
    }
};
