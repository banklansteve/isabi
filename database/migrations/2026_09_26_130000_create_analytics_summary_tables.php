<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_user_activity_days', function (Blueprint $table) {
            $table->id();
            $table->date('activity_date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['activity_date', 'user_id']);
            $table->index(['user_id', 'activity_date']);
        });

        Schema::create('analytics_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date')->unique();
            $table->unsignedInteger('dau')->default(0);
            $table->unsignedInteger('wau')->default(0);
            $table->unsignedInteger('mau')->default(0);
            $table->unsignedInteger('page_my_page')->default(0);
            $table->unsignedInteger('page_work_log')->default(0);
            $table->unsignedInteger('page_credits')->default(0);
            $table->unsignedInteger('page_help')->default(0);
            $table->unsignedInteger('page_help_chat')->default(0);
            $table->unsignedInteger('page_referrals')->default(0);
            $table->unsignedInteger('page_credits_users')->default(0);
            $table->unsignedInteger('purchase_users')->default(0);
            $table->unsignedInteger('referral_signups')->default(0);
            $table->unsignedInteger('export_users')->default(0);
            $table->unsignedInteger('review_messages_users')->default(0);
            $table->unsignedInteger('login_freq_0')->default(0);
            $table->unsignedInteger('login_freq_1_3')->default(0);
            $table->unsignedInteger('login_freq_4_10')->default(0);
            $table->unsignedInteger('login_freq_11_plus')->default(0);
            $table->unsignedInteger('active_users_30d')->default(0);
            $table->timestamps();
        });

        Schema::create('analytics_retention_cohorts', function (Blueprint $table) {
            $table->id();
            $table->date('cohort_month')->unique();
            $table->unsignedInteger('signed_up')->default(0);
            $table->unsignedInteger('active_30')->default(0);
            $table->unsignedInteger('active_60')->default(0);
            $table->unsignedInteger('active_90')->default(0);
            $table->unsignedTinyInteger('rate_30')->default(0);
            $table->unsignedTinyInteger('rate_60')->default(0);
            $table->unsignedTinyInteger('rate_90')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_retention_cohorts');
        Schema::dropIfExists('analytics_daily_metrics');
        Schema::dropIfExists('analytics_user_activity_days');
    }
};
