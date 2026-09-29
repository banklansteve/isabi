<?php

namespace Tests\Feature\Admin;

use App\Models\SupportTicket;
use App\Models\TokenPurchase;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewRevenueAndContentInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_overview_includes_revenue_health_payload(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $artisan = User::factory()->regularUser()->create([
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'created_at' => now()->subMonths(2)->startOfMonth(),
        ]);

        TokenPurchase::query()->create([
            'user_id' => $artisan->id,
            'pack_key' => 'starter',
            'pack_name' => 'Starter',
            'tokens' => 10,
            'price' => 3000,
            'currency' => 'NGN',
            'status' => TokenPurchase::STATUS_COMPLETED,
            'reference' => 'tst-'.uniqid(),
            'processor' => 'paystack',
            'paid_at' => now()->subDays(3),
        ]);

        WorkLog::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Test job',
            'description' => 'Overview revenue fixture',
            'worked_on' => now()->subDays(2)->toDateString(),
            'created_at' => now()->subDays(2),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Overview')
                ->has('revenue_daily')
                ->has('revenue_by_source.layers')
                ->has('purchase_mix')
                ->has('arpu')
                ->has('arpu_kpi.value')
                ->has('revenue_cohorts')
                ->has('revenue_by_state')
                ->has('revenue_by_city'));
    }

    public function test_insights_content_page_loads_effectiveness_reports(): void
    {
        $admin = User::factory()->superAdmin()->create();

        SupportTicket::query()->create([
            'uid' => 'ST-TEST-1',
            'user_id' => User::factory()->regularUser()->create()->id,
            'subject' => 'Billing help',
            'topic_key' => 'billing',
            'status' => SupportTicket::STATUS_NEW,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.insights.content'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Insights/Content')
                ->has('help_vs_tickets')
                ->has('faq_effectiveness')
                ->has('support_health.kpis')
                ->has('support_health.volume')
                ->where('zero_result_searches.ready', false));
    }
}
