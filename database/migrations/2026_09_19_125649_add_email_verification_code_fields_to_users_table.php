<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_code_hash')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_code_expires_at')->nullable()->after('email_verification_code_hash');
            $table->unsignedTinyInteger('email_verification_attempts')->default(0)->after('email_verification_code_expires_at');
            $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_attempts');
            $table->string('email_verification_link_hash', 64)->nullable()->after('email_verification_sent_at');
            $table->timestamp('email_verification_link_expires_at')->nullable()->after('email_verification_link_hash');
            $table->string('email_verification_session_id', 120)->nullable()->after('email_verification_link_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_code_hash',
                'email_verification_code_expires_at',
                'email_verification_attempts',
                'email_verification_sent_at',
                'email_verification_link_hash',
                'email_verification_link_expires_at',
                'email_verification_session_id',
            ]);
        });
    }
};
