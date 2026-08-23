<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pack_key', 40);
            $table->string('pack_name');
            $table->unsignedInteger('tokens');
            $table->unsignedInteger('price'); // NGN whole naira
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 24)->default('pending'); // pending | completed | failed | cancelled
            $table->string('reference')->unique();
            $table->string('processor', 40)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_purchases');
    }
};
