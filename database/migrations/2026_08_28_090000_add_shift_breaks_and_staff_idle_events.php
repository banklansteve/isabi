<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'shift_breaks')) {
                $table->json('shift_breaks')->nullable()->after('shift_ends_at');
            }
        });

        if (! Schema::hasTable('staff_idle_events')) {
            Schema::create('staff_idle_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('work_date');
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'work_date']);
                $table->index(['work_date', 'started_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_idle_events');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'shift_breaks')) {
                $table->dropColumn('shift_breaks');
            }
        });
    }
};
