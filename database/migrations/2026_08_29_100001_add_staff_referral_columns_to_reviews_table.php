<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')
                    ->nullable()
                    ->after('removed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('reviews', 'referred_by_user_id')) {
                $table->foreignId('referred_by_user_id')
                    ->nullable()
                    ->after('assigned_to_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('reviews', 'referred_note')) {
                $table->text('referred_note')->nullable()->after('referred_by_user_id');
            }

            if (! Schema::hasColumn('reviews', 'referred_at')) {
                $table->timestamp('referred_at')->nullable()->after('referred_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'assigned_to_user_id')) {
                $table->dropConstrainedForeignId('assigned_to_user_id');
            }

            if (Schema::hasColumn('reviews', 'referred_by_user_id')) {
                $table->dropConstrainedForeignId('referred_by_user_id');
            }

            $drop = [];
            if (Schema::hasColumn('reviews', 'referred_note')) {
                $drop[] = 'referred_note';
            }
            if (Schema::hasColumn('reviews', 'referred_at')) {
                $drop[] = 'referred_at';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
