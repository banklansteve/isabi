<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 32)->unique();
            $table->string('action', 80);
            $table->nullableMorphs('subject');
            $table->json('payload')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('action');
        });

        Schema::create('ops_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 32)->unique();
            $table->string('slug', 80)->unique();
            $table->string('title');
            $table->string('category', 40)->default('general')->index();
            $table->string('subject');
            $table->text('body');
            $table->json('editable_keys')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('billing_issues', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('token_purchase_id')->nullable()->constrained('token_purchases')->nullOnDelete();
            $table->string('type', 40)->index(); // payment_failed | multi_charge | chargeback | other
            $table->string('status', 20)->default('open')->index(); // open | assigned | resolved | dismissed
            $table->string('title');
            $table->text('details')->nullable();
            $table->unsignedInteger('amount_kobo')->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('reference')->nullable()->index();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_to_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_issues');
        Schema::dropIfExists('ops_message_templates');
        Schema::dropIfExists('admin_approvals');
    }
};
