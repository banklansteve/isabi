<?php

namespace Tests\Feature\Admin;

use App\Enums\StaffStatus;
use App\Enums\UserRole;
use App\Mail\StaffInvitationMail;
use App\Models\StaffRole;
use App\Models\User;
use Database\Seeders\StaffRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaffAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_guests_are_sent_to_admin_login_from_admin_urls(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_artisans_cannot_access_admin_urls(): void
    {
        $artisan = User::factory()->create();

        $this->actingAs($artisan)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($artisan)->get(route('admin.staff.index'))->assertForbidden();
    }

    public function test_artisans_cannot_sign_in_at_the_admin_portal(): void
    {
        $artisan = User::factory()->create();

        $this->post(route('admin.login'), [
            'email' => $artisan->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_cannot_sign_in_at_the_artisan_portal(): void
    {
        $staff = User::factory()->operationsAdmin()->create();

        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_can_sign_in_at_the_admin_portal(): void
    {
        $staff = User::factory()->operationsAdmin()->create();

        $this->post(route('admin.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_staff_can_open_artisan_dashboard(): void
    {
        $staff = User::factory()->operationsAdmin()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_super_admin_can_invite_operations_staff(): void
    {
        Mail::fake();
        $this->seed(StaffRoleSeeder::class);

        $admin = User::factory()->superAdmin()->create();
        $support = StaffRole::query()->where('slug', 'customer_support')->first();

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), [
                'name' => 'Ngozi Adeyemi',
                'email' => 'ngozi@isabi.dev',
                'suggested_role_id' => $support->id,
            ])
            ->assertRedirect();

        $staff = User::query()->where('email', 'ngozi@isabi.dev')->first();

        $this->assertNotNull($staff);
        $this->assertTrue($staff->isOperationsAdmin());
        $this->assertSame(StaffStatus::Invited, $staff->staff_status);
        $this->assertFalse($staff->hasSetPassword());
        $this->assertFalse($staff->staffRoles->contains('slug', 'customer_support'));

        Mail::assertSent(StaffInvitationMail::class, function (StaffInvitationMail $mail) {
            return $mail->hasTo('ngozi@isabi.dev')
                && str_contains($mail->acceptUrl, '/admin/invite/accept/')
                && $mail->expiresHours === 48;
        });
    }

    public function test_operations_staff_cannot_invite_staff(): void
    {
        $ops = User::factory()->operationsAdmin()->create();

        $this->actingAs($ops)
            ->post(route('admin.staff.store'), [
                'email' => 'ngozi@isabi.dev',
            ])
            ->assertForbidden();
    }

    public function test_invited_staff_accept_token_then_are_signed_in(): void
    {
        Mail::fake();
        $this->seed(StaffRoleSeeder::class);

        $admin = User::factory()->superAdmin()->create();
        $support = StaffRole::query()->where('slug', 'customer_support')->first();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Chidi Okoro',
            'email' => 'chidi@isabi.dev',
            'suggested_role_id' => $support->id,
        ]);

        $token = null;
        Mail::assertSent(StaffInvitationMail::class, function (StaffInvitationMail $mail) use (&$token) {
            preg_match('#/invite/accept/([^/?]+)#', $mail->acceptUrl, $matches);
            $token = $matches[1] ?? null;

            return true;
        });

        $this->assertNotEmpty($token);

        $this->post(route('logout'));
        $this->assertGuest();

        $this->get(route('admin.invite.accept', $token))->assertOk();

        $this->post(route('admin.invite.accept', $token), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('admin.dashboard'));

        $staff = User::query()->where('email', 'chidi@isabi.dev')->first();

        $this->assertAuthenticatedAs($staff);
        $this->assertTrue($staff->hasSetPassword());
        $this->assertNotNull($staff->email_verified_at);
        $this->assertSame(StaffStatus::Active, $staff->staff_status);
        $this->assertTrue($staff->staffRoles->contains('slug', 'customer_support'));
    }

    public function test_invalid_invite_token_shows_expired_page(): void
    {
        $this->get(route('admin.invite.accept', 'not-a-real-token'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Auth/InviteExpired'));
    }

    public function test_super_admin_can_create_a_new_duty(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Fraud review',
                'description' => 'Look into suspicious billing and fake reviews.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('staff_roles', [
            'slug' => 'fraud-review',
            'name' => 'Fraud review',
            'is_system' => 0,
        ]);
    }

    public function test_super_admin_can_update_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'app.name' => 'Isabi Ops',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('app_settings', [
            'key' => 'app.name',
            'value' => 'Isabi Ops',
        ]);
    }

    public function test_create_super_admin_helper_makes_an_active_account(): void
    {
        $user = User::createSuperAdmin('ada@isabi.dev', 'secret-pass', 'Ada', 'Okoye');

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasSetPassword());
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(UserRole::SuperAdmin, $user->role);
    }
}
