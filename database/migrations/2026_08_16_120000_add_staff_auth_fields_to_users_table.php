<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();

            $table->timestamp('password_set_at')->nullable()->after('password');
            $table->string('staff_status', 32)->nullable()->after('role');
            $table->foreignId('invited_by_user_id')
                ->nullable()
                ->after('staff_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('suspended_at')->nullable()->after('invited_by_user_id');
            $table->string('suspension_reason')->nullable()->after('suspended_at');

            $table->index('staff_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['staff_status']);
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropColumn([
                'password_set_at',
                'staff_status',
                'suspended_at',
                'suspension_reason',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
