<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('work_logs', 'referred_to_user_id')) {
                $table->foreignId('referred_to_user_id')
                    ->nullable()
                    ->after('removed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('work_logs', 'referred_by_user_id')) {
                $table->foreignId('referred_by_user_id')
                    ->nullable()
                    ->after('referred_to_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('work_logs', 'referred_note')) {
                $table->text('referred_note')->nullable()->after('referred_by_user_id');
            }

            if (! Schema::hasColumn('work_logs', 'referred_at')) {
                $table->timestamp('referred_at')->nullable()->after('referred_note');
            }
        });

        if (Schema::hasColumn('work_logs', 'hidden_reason')) {
            DB::statement('ALTER TABLE work_logs MODIFY hidden_reason VARCHAR(500) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('work_logs', 'referred_to_user_id')) {
                $table->dropConstrainedForeignId('referred_to_user_id');
            }

            if (Schema::hasColumn('work_logs', 'referred_by_user_id')) {
                $table->dropConstrainedForeignId('referred_by_user_id');
            }

            $drop = [];
            if (Schema::hasColumn('work_logs', 'referred_note')) {
                $drop[] = 'referred_note';
            }
            if (Schema::hasColumn('work_logs', 'referred_at')) {
                $drop[] = 'referred_at';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
