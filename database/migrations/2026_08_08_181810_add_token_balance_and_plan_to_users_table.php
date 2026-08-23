<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('token_balance')->default(0)->after('profile_completion');
            $table->string('plan', 32)->default('free')->after('token_balance');
            $table->timestamp('annual_expires_at')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['token_balance', 'plan', 'annual_expires_at']);
        });
    }
};
