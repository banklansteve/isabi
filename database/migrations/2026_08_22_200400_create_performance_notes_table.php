<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rating')->nullable(); // exceeding | meeting | needs_improvement
            $table->text('body');
            $table->date('noted_on');
            $table->timestamps();

            $table->index(['user_id', 'noted_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_notes');
    }
};
