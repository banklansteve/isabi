<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('work_logs', 'hidden_reason')) {
                $table->string('hidden_reason', 64)->nullable()->after('hidden_at');
            }
            if (! Schema::hasColumn('work_logs', 'removed_at')) {
                $table->timestamp('removed_at')->nullable()->after('hidden_reason');
            }
        });

        Schema::create('patrol_cases', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16)->default('job');
            $table->foreignId('work_log_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('review_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32);
            $table->string('severity', 16);
            $table->string('recommended_outcome', 24)->nullable();
            $table->boolean('visibility_was_public')->default(true);
            $table->timestamp('auto_hidden_at')->nullable();
            $table->timestamp('flagged_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kind', 'status', 'severity', 'flagged_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('patrol_case_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_case_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key', 64);
            $table->string('severity', 16);
            $table->json('evidence');
            $table->timestamp('detected_at');

            $table->unique(['patrol_case_id', 'rule_key']);
            $table->index('rule_key');
        });

        Schema::create('patrol_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('patrol_case_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 48);
            $table->text('reason')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $this->syncPatrolRole();
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_case_actions');
        Schema::dropIfExists('patrol_case_notes');
        Schema::dropIfExists('patrol_case_rules');
        Schema::dropIfExists('patrol_cases');

        Schema::table('work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('work_logs', 'removed_at')) {
                $table->dropColumn('removed_at');
            }
            if (Schema::hasColumn('work_logs', 'hidden_reason')) {
                $table->dropColumn('hidden_reason');
            }
        });
    }

    private function syncPatrolRole(): void
    {
        if (! Schema::hasTable('staff_roles')) {
            return;
        }

        $role = DB::table('staff_roles')->where('slug', 'patrol')->first();
        if (! $role) {
            return;
        }

        $permissions = json_decode((string) $role->permissions, true);
        if (! is_array($permissions)) {
            $permissions = [];
        }

        $permissions = array_values(array_unique(array_merge(
            array_values(array_filter($permissions, fn ($key) => $key !== 'admin.patrol.manage')),
            ['admin.users.view', 'admin.content.manage', 'patrol.view', 'patrol.investigate'],
        )));

        DB::table('staff_roles')->where('id', $role->id)->update([
            'permissions' => json_encode($permissions),
            'description' => 'Review flagged job logs for fake or manipulated work histories. Cannot finalize high-severity dismissals or removals.',
            'updated_at' => now(),
        ]);
    }
};
