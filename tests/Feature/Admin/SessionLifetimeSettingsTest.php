<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Auth\SessionLifetime;
use App\Support\Staff\AppSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionLifetimeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_lifetime_settings_persist(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'session.lifetime_users' => 43200,
                    'session.lifetime_operations' => 20160,
                    'session.lifetime_super_admin' => 20160,
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('app_settings', [
            'key' => 'session.lifetime_users',
            'value' => '43200',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'session.lifetime_operations',
            'value' => '20160',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'session.lifetime_super_admin',
            'value' => '20160',
        ]);

        $this->assertSame(43200, config('session.lifetime_users'));
        $this->assertSame(20160, config('session.lifetime_operations'));
        $this->assertSame(20160, config('session.lifetime_super_admin'));
    }

    public function test_session_lifetime_settings_reject_invalid_values(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.index', ['tab' => 'session']))
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'session.lifetime_users' => 5,
                    'session.lifetime_operations' => 525601,
                    'session.lifetime_super_admin' => 'forever',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'session.lifetime_users',
                'session.lifetime_operations',
                'session.lifetime_super_admin',
            ]);

        $this->assertDatabaseMissing('app_settings', [
            'key' => 'session.lifetime_users',
        ]);
    }

    public function test_authenticated_requests_use_the_matching_session_lifetime(): void
    {
        $admin = User::factory()->superAdmin()->create();

        app(AppSettingsService::class)->updateMany([
            SessionLifetime::KEY_USERS => 90,
            SessionLifetime::KEY_OPERATIONS => 180,
            SessionLifetime::KEY_SUPER_ADMIN => 240,
        ], $admin);

        $artisan = User::factory()->create();
        $this->actingAs($artisan)->get(route('dashboard'))->assertOk();
        $this->assertSame(90, config('session.lifetime'));

        $ops = User::factory()->operationsAdmin()->create();
        $this->actingAs($ops)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(180, config('session.lifetime'));

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(240, config('session.lifetime'));
    }

    public function test_login_applies_the_matching_session_lifetime(): void
    {
        $admin = User::factory()->superAdmin()->create();

        app(AppSettingsService::class)->updateMany([
            SessionLifetime::KEY_USERS => 90,
            SessionLifetime::KEY_OPERATIONS => 180,
            SessionLifetime::KEY_SUPER_ADMIN => 240,
        ], $admin);

        $artisan = User::factory()->create();
        $this->post(route('login'), [
            'email' => $artisan->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($artisan);
        $this->assertSame(90, config('session.lifetime'));

        $this->post(route('logout'));
        $this->assertGuest();

        $ops = User::factory()->operationsAdmin()->create();
        $this->post(route('admin.login'), [
            'email' => $ops->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($ops);
        $this->assertSame(180, config('session.lifetime'));

        $this->post(route('admin.logout'));
        $this->assertGuest();

        $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertSame(240, config('session.lifetime'));
    }

    public function test_settings_page_exposes_session_lifetimes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['tab' => 'session']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Index')
                ->has('settings.session', 3)
            );
    }
}
