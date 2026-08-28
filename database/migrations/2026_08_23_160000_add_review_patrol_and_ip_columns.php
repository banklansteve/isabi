<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patrol_cases')) {
            Schema::table('patrol_cases', function (Blueprint $table) {
                if (! Schema::hasColumn('patrol_cases', 'kind')) {
                    $table->string('kind', 16)->default('job')->after('id');
                }
                if (! Schema::hasColumn('patrol_cases', 'review_id')) {
                    $table->foreignId('review_id')->nullable()->unique()->after('work_log_id')->constrained()->restrictOnDelete();
                }
            });

            $this->makeWorkLogIdNullable();
        }

        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'submitted_ip')) {
                $table->string('submitted_ip', 45)->nullable()->after('submitter_ip_hash');
            }
            if (! Schema::hasColumn('reviews', 'hidden_reason')) {
                $table->string('hidden_reason', 64)->nullable()->after('hidden_at');
            }
            if (! Schema::hasColumn('reviews', 'removed_at')) {
                $table->timestamp('removed_at')->nullable()->after('hidden_reason');
            }
        });

        Schema::table('work_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('work_logs', 'created_ip')) {
                $table->string('created_ip', 45)->nullable()->after('removed_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_ip')) {
                $table->dropColumn('last_login_ip');
            }
        });

        Schema::table('work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('work_logs', 'created_ip')) {
                $table->dropColumn('created_ip');
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'removed_at')) {
                $table->dropColumn('removed_at');
            }
            if (Schema::hasColumn('reviews', 'hidden_reason')) {
                $table->dropColumn('hidden_reason');
            }
            if (Schema::hasColumn('reviews', 'submitted_ip')) {
                $table->dropColumn('submitted_ip');
            }
        });
    }

    private function makeWorkLogIdNullable(): void
    {
        if (! Schema::hasColumn('patrol_cases', 'work_log_id')) {
            return;
        }

        $column = collect(Schema::getColumns('patrol_cases'))
            ->firstWhere('name', 'work_log_id');

        if (($column['nullable'] ?? false) === true) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE patrol_cases MODIFY work_log_id BIGINT UNSIGNED NULL');

            return;
        }

        Schema::table('patrol_cases', function (Blueprint $table) {
            $table->unsignedBigInteger('work_log_id')->nullable()->change();
        });
    }
};
