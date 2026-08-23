<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Self-declared trade credentials. Never treated as verified.
            $table->json('credentials')->nullable()->after('skills');
            $table->smallInteger('experience_started_year')->nullable()->after('credentials');
            $table->json('coverage_areas')->nullable()->after('lga');
            $table->string('coverage_note', 180)->nullable()->after('coverage_areas');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'credentials',
                'experience_started_year',
                'coverage_areas',
                'coverage_note',
            ]);
        });
    }
};
