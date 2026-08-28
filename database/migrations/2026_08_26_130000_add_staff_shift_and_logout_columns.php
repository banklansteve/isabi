<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_logout_at')) {
                $table->timestamp('last_logout_at')->nullable()->after('last_login_at');
            }

            if (! Schema::hasColumn('users', 'shift_days')) {
                $table->json('shift_days')->nullable()->after('last_seen_at');
            }

            if (! Schema::hasColumn('users', 'shift_starts_at')) {
                $table->string('shift_starts_at', 5)->nullable()->after('shift_days');
            }

            if (! Schema::hasColumn('users', 'shift_ends_at')) {
                $table->string('shift_ends_at', 5)->nullable()->after('shift_starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['last_logout_at', 'shift_days', 'shift_starts_at', 'shift_ends_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
