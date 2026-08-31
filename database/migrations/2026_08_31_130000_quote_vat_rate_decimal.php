<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artisan_quotes', function (Blueprint $table) {
            $table->decimal('vat_rate', 4, 1)->default(7.5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('artisan_quotes', function (Blueprint $table) {
            $table->unsignedTinyInteger('vat_rate')->default(0)->change();
        });
    }
};
