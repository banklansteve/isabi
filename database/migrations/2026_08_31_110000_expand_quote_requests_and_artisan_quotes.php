<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropForeign(['work_log_id']);
        });

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreignId('work_log_id')->nullable()->change();
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreign('work_log_id')->references('id')->on('work_logs')->nullOnDelete();
        });

        Schema::create('artisan_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('quote_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quote_number', 32);
            $table->date('valid_until')->nullable();
            $table->date('estimated_start')->nullable();
            $table->unsignedSmallInteger('estimated_duration_days')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->json('line_items')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('payment_terms', 500)->nullable();
            $table->unsignedInteger('subtotal_kobo')->default(0);
            $table->unsignedTinyInteger('vat_rate')->default(0);
            $table->unsignedInteger('vat_kobo')->default(0);
            $table->unsignedInteger('discount_kobo')->default(0);
            $table->unsignedInteger('total_kobo')->default(0);
            $table->string('status', 24)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artisan_quotes');

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->dropForeign(['work_log_id']);
            $table->foreignId('work_log_id')->nullable(false)->change();
        });
    }
};
