<?php

use App\Models\WorkLog;
use App\Support\JobReference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('work_logs', 'reference')) {
                $table->string('reference', 16)->nullable()->unique()->after('uid');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('avatar_url');
            }
            if (! Schema::hasColumn('users', 'logo_url')) {
                $table->string('logo_url', 2048)->nullable()->after('logo_path');
            }
        });

        WorkLog::query()
            ->whereNull('reference')
            ->orderBy('id')
            ->each(function (WorkLog $log): void {
                $log->forceFill([
                    'reference' => JobReference::unique($log->id, $log->description),
                ])->saveQuietly();
            });

        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('work_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new');
            $table->string('created_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'logo_url')) {
                $table->dropColumn('logo_url');
            }
            if (Schema::hasColumn('users', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });

        Schema::table('work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('work_logs', 'reference')) {
                $table->dropColumn('reference');
            }
        });
    }
};
