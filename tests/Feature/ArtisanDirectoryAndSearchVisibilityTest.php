<?php

namespace Tests\Feature;

use App\Models\ProfileViewMonth;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\CookieConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtisanDirectoryAndSearchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function consentCookie(string $status): array
    {
        return [
            CookieConsent::COOKIE => json_encode([
                'status' => $status,
                'v' => CookieConsent::VERSION,
                'at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR),
        ];
    }

    public function test_directory_and_trade_location_landings_render(): void
    {
        $user = User::factory()->create([
            'trade' => 'Electrician',
            'trades' => ['Electrician'],
            'state' => 'Lagos',
            'slug' => 'spark-power',
            'public_page_enabled' => true,
            'business_name' => 'Spark Power',
        ]);

        $this->createWorkLog($user);

        $this->get(route('public.directory'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Directory')
                ->has('artisans')
                ->where('locked.trade', null));

        $this->get(route('public.directory.trade', 'electrician'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Directory')
                ->where('locked.trade', 'Electrician')
                ->where('landing.heading', 'Electricians'));

        $this->get(route('public.directory.trade-state', ['electrician', 'lagos']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Directory')
                ->where('locked.trade', 'Electrician')
                ->where('locked.state', 'Lagos')
                ->where('landing.heading', 'Electricians in Lagos'));

        $this->get(route('public.directory.trade', 'not-a-real-trade'))
            ->assertNotFound();
    }

    public function test_profile_view_records_search_attribution(): void
    {
        $user = User::factory()->create([
            'slug' => 'spark-power',
            'public_page_enabled' => true,
            'business_name' => 'Spark Power',
        ]);
        $this->createWorkLog($user);

        $this->withCookies($this->consentCookie(CookieConsent::STATUS_ACCEPTED))
            ->withHeader('Referer', 'https://www.google.com/search?q=electrician')
            ->get(route('public.profile', $user->slug))
            ->assertOk();

        $month = ProfileViewMonth::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($month);
        $this->assertSame(1, (int) $month->views);
        $this->assertSame(1, (int) $month->search_views);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('searchVisibility.search_views', 1)
                ->where('searchVisibility.views', 1));
    }

    public function test_profile_view_skips_tracking_when_cookies_rejected(): void
    {
        $user = User::factory()->create([
            'slug' => 'spark-power',
            'public_page_enabled' => true,
            'business_name' => 'Spark Power',
            'public_page_views' => 0,
        ]);
        $this->createWorkLog($user);

        $this->withCookies($this->consentCookie(CookieConsent::STATUS_REJECTED))
            ->get(route('public.profile', $user->slug))
            ->assertOk();

        $this->assertNull(ProfileViewMonth::query()->where('user_id', $user->id)->first());
        $this->assertSame(0, (int) $user->fresh()->public_page_views);
    }

    private function createWorkLog(User $user): WorkLog
    {
        return WorkLog::query()->create([
            'user_id' => $user->id,
            'uid' => 'wl_'.str()->lower(str()->random(10)),
            'reference' => 'jt'.str()->lower(str()->random(8)),
            'description' => 'Wired a 3-bedroom flat',
            'worked_on' => now()->toDateString(),
            'job_category' => 'Electrical',
            'job_subcategory' => 'Electrician',
        ]);
    }
}
