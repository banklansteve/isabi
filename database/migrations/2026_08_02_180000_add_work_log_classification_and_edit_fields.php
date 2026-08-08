<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->string('client_name', 120)->nullable()->after('worked_on');
            $table->string('job_category', 120)->nullable()->after('client_name');
            $table->string('service_state', 80)->nullable()->after('job_category');
            $table->string('service_lga', 120)->nullable()->after('service_state');
            $table->string('service_city', 120)->nullable()->after('service_lga');
            $table->timestamp('review_requested_at')->nullable()->after('amount_charged');
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn([
                'client_name',
                'job_category',
                'service_state',
                'service_lga',
                'service_city',
                'review_requested_at',
            ]);
        });
    }
};
