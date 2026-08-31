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
            $table->dropUnique(['uid']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('uid', 36)->nullable()->change();
        });

        SupportTicket::query()->orderBy('id')->each(function (SupportTicket $ticket) {
            if (! SupportTicketUid::isValid($ticket->uid)) {
                $ticket->forceFill(['uid' => SupportTicketUid::unique()])->saveQuietly();
            }
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unique('uid');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropUnique(['uid']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('uid', 16)->nullable(false)->change();
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unique('uid');
        });
    }
};
