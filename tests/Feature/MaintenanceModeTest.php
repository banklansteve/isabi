<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_see_maintenance_page_when_enabled(): void
    {
        $sa = User::factory()->superAdmin()->create();
        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), [
                'backup_confirmed' => true,
                'note' => 'Test snapshot',
            ])
            ->assertRedirect();

        $this->assertTrue(app(MaintenanceMode::class)->enabled());

        auth()->logout();

        $this->get(route('home'))
            ->assertRedirect(route('maintenance.show'));

        $this->get(route('maintenance.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Errors/Maintenance'));
    }

    public function test_artisans_are_blocked_but_session_survives_toggle(): void
    {
        $artisan = User::factory()->regularUser()->create([
            'email_verified_at' => now(),
        ]);
        $sa = User::factory()->superAdmin()->create();

        $this->actingAs($artisan)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertRedirect();

        $this->actingAs($artisan)
            ->get(route('dashboard'))
            ->assertRedirect(route('maintenance.show'));

        $this->actingAs($artisan)
            ->get(route('maintenance.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Errors/Maintenance'));

        $this->assertAuthenticatedAs($artisan);

        $this->actingAs($sa)
            ->post(route('admin.maintenance.disable'))
            ->assertRedirect();

        // After going live, refreshing the holding page returns them to dashboard.
        $this->actingAs($artisan)
            ->withSession(['maintenance.intended' => route('dashboard')])
            ->get(route('maintenance.show'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($artisan)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($artisan);
    }

    public function test_staff_can_access_admin_and_public_routes_during_maintenance(): void
    {
        $sa = User::factory()->superAdmin()->create();
        $ops = User::factory()->operationsAdmin()->create();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertRedirect();

        $this->actingAs($sa)
            ->get(route('admin.settings.index', ['tab' => 'maintenance']))
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($ops)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_enable_requires_backup_confirmation(): void
    {
        $sa = User::factory()->superAdmin()->create();

        $this->actingAs($sa)
            ->from(route('admin.settings.index', ['tab' => 'maintenance']))
            ->post(route('admin.maintenance.enable'), [
                'backup_confirmed' => false,
            ])
            ->assertSessionHasErrors('backup_confirmed');

        $this->assertFalse(app(MaintenanceMode::class)->enabled());
    }

    public function test_ops_cannot_toggle_maintenance(): void
    {
        $ops = User::factory()->operationsAdmin()->create();

        $this->actingAs($ops)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertForbidden();
    }

    public function test_bypass_token_grants_guest_access(): void
    {
        $sa = User::factory()->superAdmin()->create();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertRedirect();

        auth()->logout();

        $secret = app(MaintenanceMode::class)->secret();
        $this->assertNotEmpty($secret);

        $this->get(route('maintenance.bypass', $secret))
            ->assertRedirect(route('home'));

        $this->withCookie(MaintenanceMode::COOKIE, $secret)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_invalid_bypass_token_does_not_unlock(): void
    {
        $sa = User::factory()->superAdmin()->create();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertRedirect();

        auth()->logout();

        $this->get(route('maintenance.bypass', 'notarealtokennotarealtokennotareal12'))
            ->assertRedirect(route('home'));

        $this->followingRedirects()
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Errors/Maintenance'));
    }

    public function test_regenerate_invalidates_old_bypass_cookie(): void
    {
        $sa = User::factory()->superAdmin()->create();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertRedirect();

        $oldSecret = app(MaintenanceMode::class)->secret();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.regenerate'))
            ->assertRedirect();

        $newSecret = app(MaintenanceMode::class)->secret();
        $this->assertNotSame($oldSecret, $newSecret);

        auth()->logout();

        $this->withCookie(MaintenanceMode::COOKIE, $oldSecret)
            ->get(route('home'))
            ->assertRedirect(route('maintenance.show'));

        $this->withCookie(MaintenanceMode::COOKIE, $newSecret)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_refresh_after_going_live_returns_to_intended_page(): void
    {
        $sa = User::factory()->superAdmin()->create();

        $this->actingAs($sa)
            ->post(route('admin.maintenance.enable'), ['backup_confirmed' => true])
            ->assertRedirect();

        auth()->logout();

        $this->get(route('faq'))
            ->assertRedirect(route('maintenance.show'));

        $this->assertSame(route('faq'), session('maintenance.intended'));

        $this->actingAs($sa)
            ->post(route('admin.maintenance.disable'))
            ->assertRedirect();

        auth()->logout();

        $this->withSession(['maintenance.intended' => route('faq')])
            ->get(route('maintenance.show'))
            ->assertRedirect(route('faq'));
    }
}
