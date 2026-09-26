<?php

namespace Tests\Feature;

use App\Enums\ActorKind;
use App\Enums\RetentionTier;
use App\Jobs\WriteActivityLogJob;
use App\Jobs\WriteAdminAuditLogJob;
use App\Jobs\WriteAnalyticsEventJob;
use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Admin\AdminAudit;
use App\Support\AnalyticsEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuditLoggingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logger_dispatches_async_job_with_actor_kind_and_retention(): void
    {
        Queue::fake();

        $user = User::factory()->regularUser()->create();

        $this->actingAs($user);

        ActivityLogger::log(
            action: 'profile.updated',
            summary: 'Profile updated',
            user: $user,
        );

        Queue::assertPushed(WriteActivityLogJob::class, function (WriteActivityLogJob $job) use ($user) {
            return $job->payload['user_id'] === $user->id
                && $job->payload['actor_kind'] === ActorKind::Customer->value
                && $job->payload['action'] === 'profile.updated'
                && $job->payload['retention_tier'] === RetentionTier::StandardPublic->value;
        });
    }

    public function test_financial_activity_uses_financial_retention_tier(): void
    {
        Queue::fake();

        $user = User::factory()->regularUser()->create();

        ActivityLogger::log(
            action: 'tokens.purchased',
            summary: 'Bought tokens',
            user: $user,
        );

        Queue::assertPushed(WriteActivityLogJob::class, function (WriteActivityLogJob $job) {
            return $job->payload['action'] === 'tokens.purchased'
                && $job->payload['retention_tier'] === RetentionTier::Financial->value;
        });
    }

    public function test_admin_audit_dispatches_async_job_as_staff(): void
    {
        Queue::fake();

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin);

        AdminAudit::record(
            'settings.updated',
            "{$admin->name} updated settings.",
        );

        Queue::assertPushed(WriteAdminAuditLogJob::class, function (WriteAdminAuditLogJob $job) use ($admin) {
            return $job->payload['actor_id'] === $admin->id
                && $job->payload['actor_kind'] === ActorKind::Staff->value
                && $job->payload['retention_tier'] === RetentionTier::Staff->value
                && $job->payload['action'] === 'settings.updated';
        });
    }

    public function test_analytics_logger_writes_to_separate_table_via_job(): void
    {
        Queue::fake();

        $user = User::factory()->regularUser()->create();

        AnalyticsEventLogger::log(
            action: 'auth.login',
            summary: 'Signed in',
            user: $user,
        );

        Queue::assertPushed(WriteAnalyticsEventJob::class, function (WriteAnalyticsEventJob $job) use ($user) {
            return $job->payload['action'] === 'auth.login'
                && $job->payload['user_id'] === $user->id
                && $job->payload['actor_kind'] === ActorKind::Customer->value;
        });

        Queue::assertNotPushed(WriteActivityLogJob::class);
        Queue::assertNotPushed(WriteAdminAuditLogJob::class);
    }

    public function test_sync_queue_persists_activity_and_analytics_rows(): void
    {
        $user = User::factory()->regularUser()->create();
        $admin = User::factory()->superAdmin()->create();

        ActivityLogger::log('profile.slug_changed', 'Slug changed', $user);
        AnalyticsEventLogger::log('page.help', 'Opened help', $user);
        AdminAudit::record('staff.disabled', 'Disabled staff', actor: $admin);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'profile.slug_changed',
            'actor_kind' => ActorKind::Customer->value,
            'retention_tier' => RetentionTier::StandardPublic->value,
        ]);

        $this->assertDatabaseHas('analytics_events', [
            'action' => 'page.help',
            'actor_kind' => ActorKind::Customer->value,
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'staff.disabled',
            'actor_kind' => ActorKind::Staff->value,
            'retention_tier' => RetentionTier::Staff->value,
        ]);

        $this->assertSame(1, ActivityLog::query()->count());
        $this->assertSame(1, AnalyticsEvent::query()->count());
        $this->assertSame(1, AdminAuditLog::query()->count());
    }
}
