<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('open');
            $table->date('occurred_on');
            $table->string('summary');
            $table->text('details')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('follow_up_on')->nullable();
            $table->text('outcome')->nullable();
            $table->foreignId('staff_document_id')->nullable()->constrained('staff_documents')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('occurred_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_records');
    }
};
