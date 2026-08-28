<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('topic_key', 40)->nullable()->after('subject');
            $table->json('tags')->nullable()->after('topic_key');
            $table->timestamp('first_response_at')->nullable()->after('last_reply_at');
            $table->timestamp('last_customer_message_at')->nullable()->after('first_response_at');
            $table->timestamp('last_staff_message_at')->nullable()->after('last_customer_message_at');
            $table->timestamp('customer_last_read_at')->nullable()->after('last_staff_message_at');
            $table->timestamp('staff_last_read_at')->nullable()->after('customer_last_read_at');
            $table->unsignedTinyInteger('csat_score')->nullable()->after('resolved_at');
            $table->string('csat_comment', 500)->nullable()->after('csat_score');
            $table->timestamp('csat_dismissed_at')->nullable()->after('csat_comment');
            $table->timestamp('assigned_at')->nullable()->after('assigned_to_user_id');
        });

        Schema::table('support_ticket_messages', function (Blueprint $table) {
            $table->string('kind', 16)->default('message')->after('is_staff');
            $table->string('attachment_disk', 32)->nullable()->after('body');
            $table->string('attachment_path', 1024)->nullable()->after('attachment_disk');
            $table->string('attachment_url', 2048)->nullable()->after('attachment_path');
            $table->string('attachment_name', 255)->nullable()->after('attachment_url');
            $table->string('attachment_mime', 127)->nullable()->after('attachment_name');
            $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime');
        });

        Schema::create('support_canned_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('topic_key', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_canned_replies');

        Schema::table('support_ticket_messages', function (Blueprint $table) {
            $table->dropColumn([
                'kind',
                'attachment_disk',
                'attachment_path',
                'attachment_url',
                'attachment_name',
                'attachment_mime',
                'attachment_size',
            ]);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'topic_key',
                'tags',
                'first_response_at',
                'last_customer_message_at',
                'last_staff_message_at',
                'customer_last_read_at',
                'staff_last_read_at',
                'csat_score',
                'csat_comment',
                'csat_dismissed_at',
                'assigned_at',
            ]);
        });
    }
};
