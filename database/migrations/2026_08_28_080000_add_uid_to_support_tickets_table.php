<?php

use App\Models\SupportTicket;
use App\Support\SupportChat\SupportTicketUid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('uid', 36)->nullable()->unique()->after('id');
        });

        SupportTicket::query()->whereNull('uid')->orderBy('id')->each(function (SupportTicket $ticket) {
            $ticket->forceFill(['uid' => SupportTicketUid::unique()])->saveQuietly();
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('uid', 36)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropUnique(['uid']);
            $table->dropColumn('uid');
        });
    }
};
