<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ops_message_templates', function (Blueprint $table) {
            $table->text('whatsapp_body')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('ops_message_templates', function (Blueprint $table) {
            $table->dropColumn('whatsapp_body');
        });
    }
};
