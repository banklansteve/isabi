<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('flagged_at')->nullable()->after('submitted_at');
            $table->string('flag_reason')->nullable()->after('flagged_at');
            $table->timestamp('hidden_at')->nullable()->after('flag_reason');
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->timestamp('flagged_at')->nullable()->after('review_reminder_sent_at');
            $table->string('flag_reason')->nullable()->after('flagged_at');
            $table->timestamp('hidden_at')->nullable()->after('flag_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['flagged_at', 'flag_reason', 'hidden_at']);
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn(['flagged_at', 'flag_reason', 'hidden_at']);
        });
    }
};
