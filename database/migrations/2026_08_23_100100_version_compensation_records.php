<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compensation_records', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('compensation_records', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('compensation_records', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->index(['user_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::table('compensation_records', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'effective_from']);
            $table->dropColumn('reason');
            $table->dropForeign(['user_id']);
        });

        Schema::table('compensation_records', function (Blueprint $table) {
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
