<?php

namespace Tests\Feature;

use App\Enums\ActorKind;
use App\Models\AnalyticsDailyMetric;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsRetentionCohort;
use App\Models\AnalyticsUserActivityDay;
use App\Models\User;
use App\Support\Analytics\AnalyticsAggregator;
use App\Support\Analytics\ProductAnalyticsReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAnalyticsAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_aggregator_rolls_logins_into_daily_summary_and_activity_days(): void
    {
        $user = User::factory()->regularUser()->create(['created_at' => now()->subMonths(2)]);

        AnalyticsEvent::query()->create([
            'user_id' => $user->id,
            'actor_kind' => ActorKind::Customer->value,
            'action' => 'auth.login',
            'summary' => 'Signed in',
            'created_at' => now()->subHours(2),
        ]);

        AnalyticsEvent::query()->create([
            'user_id' => $user->id,
            'actor_kind' => ActorKind::Customer->value,
            'action' => 'page.credits',
            'summary' => 'Viewed credits',
            'created_at' => now()->subHour(),
        ]);

        $metric = app(AnalyticsAggregator::class)->aggregateDay(now());

        $this->assertSame(1, $metric->dau);
        $this->assertSame(1, $metric->page_credits);
        $this->assertSame(1, $metric->page_credits_users);
        $this->assertTrue(
            AnalyticsUserActivityDay::query()
                ->where('user_id', $user->id)
                ->whereDate('activity_date', now()->toDateString())
                ->exists()
        );

        app(AnalyticsAggregator::class)->recomputeRetentionCohorts(3);

        $this->assertGreaterThan(
            0,
            AnalyticsRetentionCohort::query()->where('signed_up', '>', 0)->count()
        );
    }

    public function test_product_reports_read_summaries_not_raw_events(): void
    {
        AnalyticsDailyMetric::query()->create([
            'metric_date' => now()->toDateString(),
            'dau' => 12,
            'wau' => 40,
            'mau' => 90,
            'page_my_page' => 5,
            'page_work_log' => 8,
            'page_credits' => 2,
            'page_help' => 1,
            'page_help_chat' => 0,
            'page_referrals' => 3,
            'page_credits_users' => 2,
            'purchase_users' => 1,
            'referral_signups' => 4,
            'export_users' => 3,
            'review_messages_users' => 1,
            'login_freq_0' => 10,
            'login_freq_1_3' => 5,
            'login_freq_4_10' => 2,
            'login_freq_11_plus' => 1,
            'active_users_30d' => 8,
        ]);

        $payload = app(ProductAnalyticsReports::class)->forAnalyticsPage();

        $this->assertTrue($payload['has_summary_data']);
        $this->assertSame('12', $payload['active_kpi']['dau']['value']);
        $this->assertSame(4, count($payload['login_frequency']));
        $this->assertSame('help_chat', $payload['page_popularity']['lowest_key']);
        $this->assertSame(50, $payload['credits_conversion']['rate']);
    }

    public function test_analytics_admin_page_uses_summary_payload(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'last_seen_at' => now(),
        ]);

        AnalyticsDailyMetric::query()->create([
            'metric_date' => now()->toDateString(),
            'dau' => 3,
            'wau' => 3,
            'mau' => 3,
            'page_my_page' => 1,
            'page_work_log' => 1,
            'page_credits' => 1,
            'page_help' => 1,
            'page_help_chat' => 1,
            'page_referrals' => 1,
            'active_users_30d' => 3,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['tab' => 'engagement']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Analytics/Index')
                ->where('has_summary_data', true)
                ->where('active_kpi.dau.value', '3')
                ->has('login_frequency', 4)
                ->has('page_popularity.items')
                ->has('live_presence.online_now')
                ->where('live_presence.online_now.super_admins', 1));
    }

    public function test_live_presence_counts_roles_separately(): void
    {
        User::factory()->superAdmin()->create(['last_seen_at' => now()]);
        User::factory()->operationsAdmin()->create(['last_seen_at' => now()]);
        User::factory()->regularUser()->create(['last_seen_at' => now()]);
        User::factory()->regularUser()->create(['last_seen_at' => now()->subHours(5)]);
        User::factory()->regularUser()->create(['last_seen_at' => now()->subDays(2)]);

        $snapshot = app(\App\Support\Analytics\LivePresenceReport::class)->snapshot();

        $this->assertSame(1, $snapshot['online_now']['super_admins']);
        $this->assertSame(1, $snapshot['online_now']['operations']);
        $this->assertSame(1, $snapshot['online_now']['users']);
        $this->assertSame(1, $snapshot['online_today']['super_admins']);
        $this->assertSame(1, $snapshot['online_today']['operations']);
        $this->assertSame(2, $snapshot['online_today']['users']);
    }

    public function test_skills_catalog_filters_to_category_only(): void
    {
        $software = \App\Support\SkillsCatalog::suggestionsForCategory('Software, Web & E-commerce Development');
        $electrical = \App\Support\SkillsCatalog::suggestionsForCategory('Electrical, Power & Solar');

        $this->assertNotEmpty($software);
        $this->assertContains('Web & apps', $software);
        $this->assertNotContains('DB / fuse box work', $software);
        $this->assertContains('DB / fuse box work', $electrical);
        $this->assertNotContains('Web & apps', $electrical);
    }

    public function test_financials_conversion_tab_includes_credits_conversion(): void
    {
        $admin = User::factory()->superAdmin()->create();

        AnalyticsDailyMetric::query()->create([
            'metric_date' => now()->toDateString(),
            'page_credits_users' => 10,
            'purchase_users' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.financials.index', ['tab' => 'conversion']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Financials/Index')
                ->where('credits_conversion.rate', 20)
                ->where('credits_conversion.viewers', 10)
                ->where('credits_conversion.purchasers', 2));
    }

    public function test_prune_removes_raw_events_outside_retention_window(): void
    {
        AnalyticsEvent::query()->create([
            'user_id' => null,
            'actor_kind' => ActorKind::System->value,
            'action' => 'page.help',
            'summary' => 'Old',
            'created_at' => now()->subDays(80),
        ]);

        AnalyticsEvent::query()->create([
            'user_id' => null,
            'actor_kind' => ActorKind::System->value,
            'action' => 'page.help',
            'summary' => 'Recent',
            'created_at' => now()->subDays(5),
        ]);

        $deleted = app(AnalyticsAggregator::class)->pruneRawEvents(45);

        $this->assertSame(1, $deleted);
        $this->assertSame(1, AnalyticsEvent::query()->count());
        $this->assertSame('Recent', AnalyticsEvent::query()->value('summary'));
    }
}
