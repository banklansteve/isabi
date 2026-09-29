<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecyclePatrolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dormant_queue_flags_signup_only_never_returned(): void
    {
        $admin = User::factory()->superAdmin()->create();

        User::factory()->regularUser()->create([
            'business_name' => 'Quiet Plumber',
            'trade' => 'Plumbing',
            'last_login_at' => now()->subDays(40),
            'created_at' => now()->subMonths(3),
        ]);

        // Logged a job 12 days ago — short gap after real work is not dormant.
        $recent = User::factory()->regularUser()->create([
            'business_name' => 'Active Sparky',
            'last_login_at' => now()->subDay(),
            'created_at' => now()->subMonths(2),
        ]);
        WorkLog::query()->create([
            'user_id' => $recent->id,
            'uid' => 'wl_'.str()->lower(str()->random(10)),
            'reference' => 'jt'.str()->lower(str()->random(8)),
            'description' => 'Recent job',
            'worked_on' => now()->subDays(12)->toDateString(),
        ])->forceFill([
            'created_at' => now()->subDays(12),
            'updated_at' => now()->subDays(12),
        ])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('admin.patrol.dormant', ['window' => '30']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Patrol/Lifecycle')
                ->where('queue', 'dormant')
                ->where('window', '30')
                ->has('people.data', 1)
                ->where('people.data.0.business_name', 'Quiet Plumber')
                ->where('people.data.0.risk.label', 'Never returned')
                ->has('people.data.0.profile_url'));
    }

    public function test_dormant_queue_includes_former_workers_quiet_over_30_days(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $user = User::factory()->regularUser()->create([
            'business_name' => 'Old Jobber',
            'created_at' => now()->subMonths(4),
            'last_login_at' => now()->subDays(5), // still logging in
        ]);
        $log = WorkLog::query()->create([
            'user_id' => $user->id,
            'uid' => 'wl_'.str()->lower(str()->random(10)),
            'reference' => 'jt'.str()->lower(str()->random(8)),
            'description' => 'Old job',
            'worked_on' => now()->subDays(45)->toDateString(),
        ]);
        $log->forceFill([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('admin.patrol.dormant', ['window' => '30']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('people.data', 1)
                ->where('people.data.0.business_name', 'Old Jobber')
                ->where('people.data.0.risk.label', 'Never returned'));
    }

    public function test_single_session_requires_thirty_plus_days(): void
    {
        $admin = User::factory()->superAdmin()->create();

        User::factory()->regularUser()->create([
            'business_name' => 'Ghost Signup',
            'created_at' => now()->subDays(35),
            'last_login_at' => null,
        ]);

        User::factory()->regularUser()->create([
            'business_name' => 'Too Fresh',
            'created_at' => now()->subDays(10),
            'last_login_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.patrol.single-session'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Patrol/Lifecycle')
                ->where('queue', 'single_session')
                ->has('people.data', 1)
                ->where('people.data.0.business_name', 'Ghost Signup'));
    }

    public function test_analytics_retention_no_longer_embeds_lifecycle_lists(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['tab' => 'retention']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('dormant_users')
                ->missing('single_session_users'));
    }
}
