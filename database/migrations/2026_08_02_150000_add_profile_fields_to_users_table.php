<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('trade')->nullable()->after('role');
            $table->string('state')->nullable()->after('trade');
            $table->string('lga')->nullable()->after('state');
            $table->string('office_address')->nullable()->after('lga');
            $table->string('whatsapp', 32)->nullable()->after('office_address');
            $table->unsignedTinyInteger('profile_completion')->default(0)->after('whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'trade',
                'state',
                'lga',
                'office_address',
                'whatsapp',
                'profile_completion',
            ]);
        });
    }
};
