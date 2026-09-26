<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_case_referrals', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('queue');
        });
    }

    public function down(): void
    {
        Schema::table('staff_case_referrals', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
