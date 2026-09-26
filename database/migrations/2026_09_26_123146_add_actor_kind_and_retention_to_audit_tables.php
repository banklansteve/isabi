<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('actor_kind', 20)->default('customer')->after('user_id');
            $table->string('retention_tier', 40)->default('standard_public')->after('action');

            $table->index(['actor_kind', 'created_at']);
            $table->index(['retention_tier', 'created_at']);
        });

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->string('actor_kind', 20)->default('staff')->after('actor_id');
            $table->string('retention_tier', 40)->default('staff')->after('action');

            $table->index(['actor_kind', 'created_at']);
            $table->index(['retention_tier', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_kind', 'created_at']);
            $table->dropIndex(['retention_tier', 'created_at']);
            $table->dropColumn(['actor_kind', 'retention_tier']);
        });

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_kind', 'created_at']);
            $table->dropIndex(['retention_tier', 'created_at']);
            $table->dropColumn(['actor_kind', 'retention_tier']);
        });
    }
};
