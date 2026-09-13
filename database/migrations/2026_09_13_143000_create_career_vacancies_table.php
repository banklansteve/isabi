<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable(); // full-time, contract, part-time
            $table->text('summary');
            $table->text('description')->nullable();
            $table->string('apply_email')->nullable();
            $table->string('apply_url')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_vacancies');
    }
};
