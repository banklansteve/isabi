<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 32)->unique();
            $table->string('type', 20); // asap | direct
            $table->string('name')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('staff_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_conversation_id')->constrained('staff_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_conversation_id', 'user_id'], 'staff_chat_participant_unique');
        });

        Schema::create('staff_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_conversation_id')->constrained('staff_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedInteger('attachment_size')->nullable();
            $table->timestamps();

            $table->index(['staff_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_messages');
        Schema::dropIfExists('staff_conversation_participants');
        Schema::dropIfExists('staff_conversations');
    }
};
