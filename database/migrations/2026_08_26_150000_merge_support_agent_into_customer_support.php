<?php

use App\Models\StaffRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_roles')) {
            return;
        }

        $canonical = StaffRole::query()->where('slug', 'customer_support')->first();
        $duplicate = StaffRole::query()->where('slug', 'support_agent')->first();

        if (! $canonical && $duplicate) {
            $duplicate->forceFill([
                'slug' => 'customer_support',
                'name' => 'Customer support',
                'description' => $duplicate->description ?: 'Attend to in-app chats and artisan support queues.',
                'icon' => $duplicate->icon ?: 'ti ti-headset',
                'is_system' => true,
                'is_active' => true,
            ])->save();

            return;
        }

        if (! $canonical || ! $duplicate || $canonical->is($duplicate)) {
            return;
        }

        if (Schema::hasTable('staff_role_assignments')) {
            $existing = DB::table('staff_role_assignments')
                ->where('staff_role_id', $canonical->id)
                ->pluck('user_id')
                ->all();

            DB::table('staff_role_assignments')
                ->where('staff_role_id', $duplicate->id)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($canonical, $existing) {
                    if (in_array((int) $row->user_id, array_map('intval', $existing), true)) {
                        DB::table('staff_role_assignments')->where('id', $row->id)->delete();

                        return;
                    }

                    DB::table('staff_role_assignments')->where('id', $row->id)->update([
                        'staff_role_id' => $canonical->id,
                    ]);
                    $existing[] = (int) $row->user_id;
                });
        }

        $duplicate->delete();

        $canonical->forceFill([
            'name' => 'Customer support',
            'description' => $canonical->description ?: 'Attend to in-app chats and artisan support queues.',
            'icon' => $canonical->icon ?: 'ti ti-headset',
            'is_system' => true,
            'is_active' => true,
        ])->save();
    }

    public function down(): void
    {
        // Irreversible merge of duplicate support roles.
    }
};
