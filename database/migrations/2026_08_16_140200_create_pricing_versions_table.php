<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version');
            $table->json('payload');
            $table->string('summary')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_versions');
    }
};
