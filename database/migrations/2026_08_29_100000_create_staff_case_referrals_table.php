<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_case_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 32);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('assignee_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->string('queue', 32)->default('general');
            $table->string('status', 32)->default('active');
            $table->timestamp('referred_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['assignee_user_id', 'status', 'queue']);
            $table->index(['subject_type', 'subject_id', 'status']);
            $table->index(['referred_by_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_case_referrals');
    }
};
