<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_canned_replies', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('support_canned_replies', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('scope', 16)->default('personal')->after('user_id');
            $table->string('moment', 16)->default('general')->after('scope');
            $table->boolean('is_system')->default(false)->after('moment');
            $table->index(['scope', 'moment']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('support_canned_replies', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['scope', 'moment']);
            $table->dropColumn(['scope', 'moment', 'is_system']);
        });

        DB::table('support_canned_replies')->whereNull('user_id')->delete();

        Schema::table('support_canned_replies', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
