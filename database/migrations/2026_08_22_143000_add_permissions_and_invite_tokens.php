<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_roles', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('icon');
        });

        Schema::table('staff_invitations', function (Blueprint $table) {
            $table->string('token_hash', 64)->nullable()->unique()->after('email');
            $table->foreignId('suggested_staff_role_id')
                ->nullable()
                ->after('invited_by_user_id')
                ->constrained('staff_roles')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('consumed_at');
        });

        Schema::table('staff_invitations', function (Blueprint $table) {
            $table->string('code_hash')->nullable()->change();
        });

        foreach (config('admin.roles', []) as $role) {
            if (empty($role['slug'])) {
                continue;
            }

            DB::table('staff_roles')
                ->where('slug', $role['slug'])
                ->update([
                    'permissions' => json_encode($role['permissions'] ?? []),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('staff_invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suggested_staff_role_id');
            $table->dropUnique(['token_hash']);
            $table->dropColumn(['token_hash', 'revoked_at']);
        });

        Schema::table('staff_roles', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
