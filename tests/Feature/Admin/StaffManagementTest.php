<?php

namespace Tests\Feature\Admin;

use App\Enums\StaffStatus;
use App\Mail\AnnouncementMail;
use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\AnnouncementTemplate;
use App\Models\StaffRole;
use App\Models\User;
use App\Notifications\StaffResetPasswordNotification;
use Database\Seeders\AnnouncementTemplateSeeder;
use Database\Seeders\StaffRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function super(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function ops(array $permissions = ['admin.access']): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'ops_test_'.uniqid(),
            'name' => 'Ops test',
            'description' => 'Test role.',
            'icon' => 'ti ti-user',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $user = User::factory()->operationsAdmin()->create([
            'email' => 'ops-'.uniqid().'@isabi.dev',
        ]);
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    public function test_super_can_disable_staff_and_they_cannot_log_in(): void
    {
        $admin = $this->super();
        $staff = $this->ops();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.disable', $staff), [
                'reason' => 'Access review after a support incident.',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Access disabled');

        $staff->refresh();
        $this->assertSame(StaffStatus::Suspended, $staff->staff_status);
        $this->assertNotNull($staff->suspended_at);
        $this->assertGreaterThan(0, $staff->session_epoch);

        $this->post(route('logout'));

        $this->post(route('admin.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_super_can_reinstate_disabled_staff(): void
    {
        $admin = $this->super();
        $staff = $this->ops();
        $staff->forceFill([
            'staff_status' => StaffStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => 'Paused while we check a ticket.',
        ])->save();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.reinstate', $staff), [
                'reason' => 'Review complete, restore access.',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Access restored');

        $this->assertSame(StaffStatus::Active, $staff->fresh()->staff_status);
    }

    public function test_super_can_force_logout_without_disabling(): void
    {
        $admin = $this->super();
        $staff = $this->ops();
        $before = $staff->remember_token;

        $this->actingAs($admin)
            ->postJson(route('admin.staff.logout', $staff), [
                'reason' => 'Sign out leftover office sessions.',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Sessions signed out');

        $staff->refresh();
        $this->assertSame(StaffStatus::Active, $staff->staff_status);
        $this->assertNotSame($before, $staff->remember_token);
        $this->assertGreaterThan(0, $staff->session_epoch);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'staff.force_logout',
            'subject_id' => $staff->id,
        ]);
    }

    public function test_super_can_trigger_password_reset(): void
    {
        Notification::fake();
        $admin = $this->super();
        $staff = $this->ops();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.password-reset', $staff), [
                'reason' => 'They forgot the office password.',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Reset email sent');

        Notification::assertSentTo($staff, StaffResetPasswordNotification::class);
    }

    public function test_super_can_resend_and_revoke_invite(): void
    {
        Mail::fake();
        $this->seed(StaffRoleSeeder::class);
        $admin = $this->super();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Ngozi Adeyemi',
            'email' => 'ngozi-invite@isabi.dev',
        ]);

        $invited = User::query()->where('email', 'ngozi-invite@isabi.dev')->first();
        $this->assertNotNull($invited);
        $invited->latestStaffInvitation?->forceFill(['last_sent_at' => now()->subMinutes(5)])->save();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.resend', $invited))
            ->assertOk()
            ->assertJsonPath('toast.title', 'Invite resent');

        $this->actingAs($admin)
            ->postJson(route('admin.staff.revoke', $invited), [
                'reason' => 'Wrong email, sending a new invite.',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Invite revoked');

        $this->assertNull(User::query()->where('email', 'ngozi-invite@isabi.dev')->first());
    }

    public function test_super_can_assign_roles(): void
    {
        $this->seed(StaffRoleSeeder::class);
        $admin = $this->super();
        $staff = $this->ops();
        $support = StaffRole::query()->where('slug', 'customer_support')->first();

        $this->actingAs($admin)
            ->putJson(route('admin.staff.roles.sync', $staff), [
                'role_ids' => [$support->id],
                'is_super' => false,
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Roles updated');

        $this->assertTrue($staff->fresh()->staffRoles->contains('id', $support->id));
    }

    public function test_typed_destroy_is_blocked_without_confirmation(): void
    {
        $admin = $this->super();
        $staff = $this->ops();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.destroy', $staff), [
                'reason' => 'No longer on the operations team.',
                'confirmation' => 'not-the-name',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'deleted_at' => null]);
    }

    public function test_typed_destroy_accepts_email_and_keeps_the_row_soft_deleted(): void
    {
        $admin = $this->super();
        $staff = $this->ops();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.destroy', $staff), [
                'reason' => 'Left the operations team.',
                'confirmation' => $staff->email,
            ])
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_cannot_destroy_self_or_last_super_admin(): void
    {
        $admin = $this->super();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.destroy', $admin), [
                'reason' => 'Trying to remove myself.',
                'confirmation' => $admin->name,
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.disable', $admin), [
                'reason' => 'Trying to disable myself.',
            ])
            ->assertUnprocessable();
    }

    public function test_ops_is_forbidden_from_staff_mutations(): void
    {
        $staff = $this->ops(['admin.access', 'admin.staff.manage']);
        $target = $this->ops();

        $this->actingAs($staff)
            ->postJson(route('admin.staff.disable', $target), [
                'reason' => 'Ops should not reach this endpoint.',
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->postJson(route('admin.staff.bulk-message'), [
                'ids' => [$target->id],
                'subject' => 'Hello',
                'body' => 'A short operations note.',
                'channels' => ['in_app'],
                'reason' => 'Checking the bulk path.',
            ])
            ->assertForbidden();
    }

    public function test_bulk_message_is_queued_to_selected_staff(): void
    {
        Mail::fake();
        $admin = $this->super();
        $one = $this->ops();
        $two = $this->ops();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.bulk-message'), [
                'ids' => [$one->id, $two->id],
                'subject' => 'Ops note',
                'body' => 'Hi {{first_name}}, please check the queue today.',
                'channels' => ['in_app', 'email'],
                'reason' => 'Weekly briefing for the floor.',
            ])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('toast.title', 'Message sent');

        Mail::assertSent(AnnouncementMail::class, 2);
    }

    public function test_drawer_json_includes_feed_and_login_history(): void
    {
        $admin = $this->super();
        $staff = $this->ops();

        ActivityLog::query()->create([
            'user_id' => $staff->id,
            'action' => 'auth.admin_login',
            'summary' => 'Signed in to admin.',
            'ip_address' => '203.0.113.10',
            'created_at' => now(),
        ]);

        AdminAuditLog::query()->create([
            'actor_id' => $admin->id,
            'action' => 'staff.role_assigned',
            'summary' => "{$admin->name} assigned a role to {$staff->email}.",
            'subject_type' => $staff->getMorphClass(),
            'subject_id' => $staff->id,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.staff.show', $staff))
            ->assertOk()
            ->assertJsonPath('staff.id', $staff->id)
            ->assertJsonPath('staff.email', $staff->email)
            ->assertJsonStructure([
                'staff' => ['id', 'email', 'roles', 'last_login'],
                'activity' => ['logins', 'actor_log', 'feed', 'admin_actions'],
                'templates',
                'links',
            ])
            ->assertJsonCount(1, 'activity.logins')
            ->assertJsonCount(1, 'activity.feed');
    }

    public function test_super_can_send_a_template_notice_to_one_staff_member(): void
    {
        Mail::fake();
        $this->seed(AnnouncementTemplateSeeder::class);
        $admin = $this->super();
        $staff = $this->ops();
        $template = AnnouncementTemplate::query()->where('slug', 'staff-leave-approved')->first();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.announce', $staff), [
                'template_id' => $template->id,
                'subject' => $template->subject,
                'body' => $template->body,
                'channels' => ['in_app', 'email'],
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Notice sent');

        Mail::assertSent(AnnouncementMail::class, 1);
    }

    public function test_super_can_set_an_ops_shift_schedule(): void
    {
        $admin = $this->super();
        $staff = $this->ops();

        $this->actingAs($admin)
            ->postJson(route('admin.staff.shift', $staff), [
                'shift_days' => [1, 2, 3, 4, 5],
                'shift_starts_at' => '09:00',
                'shift_ends_at' => '17:00',
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Shift saved')
            ->assertJsonPath('staff.shift.start', '09:00')
            ->assertJsonPath('staff.shift.end', '17:00')
            ->assertJsonPath('staff.attendance.expected_in', '09:00');

        $staff->refresh();
        $this->assertSame([1, 2, 3, 4, 5], $staff->shift_days);
        $this->assertSame('09:00', $staff->shift_starts_at);
        $this->assertSame('17:00', $staff->shift_ends_at);
    }
}
