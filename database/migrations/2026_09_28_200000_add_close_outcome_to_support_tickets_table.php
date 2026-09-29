<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('close_outcome', 32)->nullable()->after('resolved_at');
            $table->timestamp('close_message_sent_at')->nullable()->after('close_outcome');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['close_outcome', 'close_message_sent_at']);
        });
    }
};
