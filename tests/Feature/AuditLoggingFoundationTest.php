<?php

namespace Tests\Feature;

use App\Enums\ActorKind;
use App\Enums\RetentionTier;
use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Admin\AdminAudit;
use App\Support\AnalyticsEventLogger;
use App\Support\CookieConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function acceptAnalyticsCookies(): void
    {
        request()->cookies->set(
            CookieConsent::COOKIE,
            json_encode([
                'status' => CookieConsent::STATUS_ACCEPTED,
                'v' => CookieConsent::VERSION,
                'at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR),
        );
    }

    private function rejectAnalyticsCookies(): void
    {
        request()->cookies->set(
            CookieConsent::COOKIE,
            json_encode([
                'status' => CookieConsent::STATUS_REJECTED,
                'v' => CookieConsent::VERSION,
                'at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR),
        );
    }

    public function test_activity_logger_persists_immediately_with_actor_kind_and_retention(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user);

        ActivityLogger::log(
            action: 'profile.updated',
            summary: 'Profile updated',
            user: $user,
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'actor_kind' => ActorKind::Customer->value,
            'action' => 'profile.updated',
            'retention_tier' => RetentionTier::StandardPublic->value,
        ]);
    }

    public function test_financial_activity_uses_financial_retention_tier(): void
    {
        $user = User::factory()->regularUser()->create();

        ActivityLogger::log(
            action: 'tokens.purchased',
            summary: 'Bought tokens',
            user: $user,
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'tokens.purchased',
            'retention_tier' => RetentionTier::Financial->value,
        ]);
    }

    public function test_admin_audit_persists_immediately_as_staff(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin);

        AdminAudit::record(
            'settings.updated',
            "{$admin->name} updated settings.",
        );

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $admin->id,
            'actor_kind' => ActorKind::Staff->value,
            'retention_tier' => RetentionTier::Staff->value,
            'action' => 'settings.updated',
        ]);
    }

    public function test_analytics_logger_writes_after_response(): void
    {
        $this->acceptAnalyticsCookies();

        $user = User::factory()->regularUser()->create();

        AnalyticsEventLogger::log(
            action: 'auth.login',
            summary: 'Signed in',
            user: $user,
        );

        app()->terminate();

        $this->assertDatabaseHas('analytics_events', [
            'action' => 'auth.login',
            'user_id' => $user->id,
            'actor_kind' => ActorKind::Customer->value,
        ]);
    }

    public function test_analytics_logger_skips_when_cookies_rejected_for_guests(): void
    {
        $this->rejectAnalyticsCookies();

        AnalyticsEventLogger::log(
            action: 'page.help',
            summary: 'Opened help',
            user: null,
        );

        app()->terminate();

        $this->assertSame(0, AnalyticsEvent::query()->count());
    }

    public function test_sync_writes_persist_activity_admin_and_analytics_rows(): void
    {
        $this->acceptAnalyticsCookies();
        $user = User::factory()->regularUser()->create();
        $admin = User::factory()->superAdmin()->create();

        ActivityLogger::log('profile.slug_changed', 'Slug changed', $user);
        AdminAudit::record('staff.disabled', 'Disabled staff', actor: $admin);

        // Analytics uses afterResponse — run terminating callbacks so the write lands in tests.
        AnalyticsEventLogger::log('page.help', 'Opened help', $user);
        app()->terminate();

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
