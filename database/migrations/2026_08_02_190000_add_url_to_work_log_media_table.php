<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_log_media', function (Blueprint $table) {
            $table->string('cdn_url', 2048)->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('work_log_media', function (Blueprint $table) {
            $table->dropColumn('cdn_url');
        });
    }
};
