<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('public_page_enabled')->default(true)->after('public_page_views');
            $table->timestamp('public_page_disabled_at')->nullable()->after('public_page_enabled');
            $table->foreignId('public_page_disabled_by')->nullable()->after('public_page_disabled_at')
                ->constrained('users')->nullOnDelete();
            $table->string('public_page_disabled_reason')->nullable()->after('public_page_disabled_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('public_page_disabled_by');
            $table->dropColumn([
                'public_page_enabled',
                'public_page_disabled_at',
                'public_page_disabled_reason',
            ]);
        });
    }
};
