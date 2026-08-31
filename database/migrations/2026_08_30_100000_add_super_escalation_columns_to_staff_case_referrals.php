<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_case_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_case_referrals', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('referred_at');
            }

            if (! Schema::hasColumn('staff_case_referrals', 'acknowledged_by_user_id')) {
                $table->foreignId('acknowledged_by_user_id')
                    ->nullable()
                    ->after('acknowledged_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_case_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('staff_case_referrals', 'acknowledged_by_user_id')) {
                $table->dropConstrainedForeignId('acknowledged_by_user_id');
            }

            if (Schema::hasColumn('staff_case_referrals', 'acknowledged_at')) {
                $table->dropColumn('acknowledged_at');
            }
        });
    }
};
