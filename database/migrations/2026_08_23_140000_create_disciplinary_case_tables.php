<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('outcome_type', 40);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->string('category', 40);
            $table->string('category_label')->nullable();
            $table->string('severity', 20);
            $table->date('incident_on');
            $table->text('description');
            $table->string('status', 32);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('incident_on');
        });

        Schema::create('disciplinary_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->boolean('confidential')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('disciplinary_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->text('justification');
            $table->date('suspension_starts_on')->nullable();
            $table->date('suspension_ends_on')->nullable();
            $table->string('letter_subject');
            $table->longText('letter_body');
            $table->foreignId('template_id')->nullable()->constrained('disciplinary_letter_templates')->nullOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('response_submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disciplinary_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('disciplinary_action_id')->constrained()->restrictOnDelete();
            $table->text('grounds');
            $table->foreignId('raised_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('raised_at');
            $table->string('outcome', 20)->nullable();
            $table->text('outcome_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disciplinary_case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->text('reason')->nullable();
            $table->boolean('confidential')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['disciplinary_case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_case_events');
        Schema::dropIfExists('disciplinary_appeals');
        Schema::dropIfExists('disciplinary_actions');
        Schema::dropIfExists('disciplinary_evidence');
        Schema::dropIfExists('disciplinary_notes');
        Schema::dropIfExists('disciplinary_cases');
        Schema::dropIfExists('disciplinary_letter_templates');
    }
};
