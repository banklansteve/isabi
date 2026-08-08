<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->date('worked_on');
            $table->string('client_whatsapp', 32)->nullable();
            $table->unsignedBigInteger('amount_charged')->nullable(); // kobo / smallest unit (₦ × 100)
            $table->timestamps();

            $table->index(['user_id', 'worked_on']);
        });

        Schema::create('work_log_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_log_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->string('kind', 16); // image | video
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_log_media');
        Schema::dropIfExists('work_logs');
    }
};
