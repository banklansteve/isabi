<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow half-star ratings (e.g. 4.5).
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE reviews MODIFY rating DECIMAL(2,1) NOT NULL');
        } else {
            Schema::table('reviews', function (Blueprint $table) {
                $table->decimal('rating', 2, 1)->change();
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('would_recommend')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('would_recommend');
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE reviews MODIFY rating TINYINT UNSIGNED NOT NULL');
        } else {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unsignedTinyInteger('rating')->change();
            });
        }
    }
};
