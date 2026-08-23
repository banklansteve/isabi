<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('employment_status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->text('exit_reason')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('personal_phone', 40)->nullable();
            $table->string('home_address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 40)->nullable();
            $table->string('emergency_contact_relationship', 80)->nullable();
            $table->timestamps();

            $table->index('employment_status');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_profiles');
    }
};
