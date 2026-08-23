<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Placeholders: {client_name}, {job}, {link}
            $table->text('review_invite_template')->nullable()->after('bio');
            $table->text('review_reminder_template')->nullable()->after('review_invite_template');
            // 0 = off. Null falls back to config default. Otherwise wait this many days.
            $table->unsignedTinyInteger('review_reminder_days')
                ->nullable()
                ->default(3)
                ->after('review_reminder_template');
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->timestamp('review_reminder_sent_at')->nullable()->after('review_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'review_invite_template',
                'review_reminder_template',
                'review_reminder_days',
            ]);
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn('review_reminder_sent_at');
        });
    }
};
