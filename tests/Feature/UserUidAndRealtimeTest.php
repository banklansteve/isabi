<?php

namespace Tests\Feature;

use App\Events\SupportCustomerUpdated;
use App\Events\SupportStaffInboxUpdated;
use App\Events\UserNotificationUpdated;
use App\Models\Announcement;
use App\Models\StaffRole;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Admin\AnnouncementService;
use App\Support\Identity\UserUid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserUidAndRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_new_users_receive_a_unique_uppercase_uid(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertTrue(UserUid::isValid($first->uid));
        $this->assertTrue(UserUid::isValid($second->uid));
        $this->assertNotSame($first->uid, $second->uid);
        $this->assertFalse(UserUid::isStaffUid($first->uid));
        $this->assertFalse(UserUid::isStaffUid($second->uid));
    }

    public function test_operations_staff_uids_use_the_op_prefix(): void
    {
        $staff = User::factory()->operationsAdmin()->create();
        $super = User::factory()->superAdmin()->create();
        $artisan = User::factory()->regularUser()->create();

        $this->assertTrue(str_starts_with($staff->uid, UserUid::PREFIX_OPERATIONS));
        $this->assertTrue(str_starts_with($super->uid, UserUid::PREFIX_SUPER_ADMIN));
        $this->assertFalse(UserUid::isStaffUid($artisan->uid));
        $this->assertTrue(UserUid::matches($staff->uid, $staff->role));
        $this->assertTrue(UserUid::matches($super->uid, $super->role));
        $this->assertTrue(UserUid::matches($artisan->uid, $artisan->role));
    }

    public function test_uid_is_shared_with_the_frontend_instead_of_only_the_database_id(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.uid', $user->uid)
                ->where('auth.user.id', $user->id));
    }

    public function test_customer_messages_broadcast_to_the_user_and_staff_inbox(): void
    {
        Event::fake([SupportCustomerUpdated::class, SupportStaffInboxUpdated::class]);
        $artisan = User::factory()->regularUser()->create();

        $this->actingAs($artisan)
            ->postJson(route('help.chat.send'), ['body' => 'Need help with billing']);

        Event::assertDispatched(SupportCustomerUpdated::class, function (SupportCustomerUpdated $event) use ($artisan) {
            $payload = $event->broadcastWith();

            return $event->customer->is($artisan)
                && ! array_key_exists('notes', $payload['conversation'])
                && ! array_key_exists('tags', $payload['conversation']);
        });
        Event::assertDispatched(SupportStaffInboxUpdated::class);
    }

    public function test_internal_notes_never_broadcast_to_the_customer_channel(): void
    {
        Event::fake([SupportCustomerUpdated::class, SupportStaffInboxUpdated::class]);
        $artisan = User::factory()->regularUser()->create();
        $staff = $this->supportStaff();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'subject' => 'Help',
        ]);

        $this->actingAs($staff)
            ->postJson(route('admin.support.notes.store', $ticket), ['body' => 'SECRET_INTERNAL_NOTE'])
            ->assertOk();

        Event::assertNotDispatched(SupportCustomerUpdated::class);
        Event::assertDispatched(SupportStaffInboxUpdated::class, function (SupportStaffInboxUpdated $event) {
            $payload = $event->broadcastWith();

            return collect($payload['thread']['notes'] ?? [])->contains(fn ($note) => $note['body'] === 'SECRET_INTERNAL_NOTE');
        });
    }

    public function test_users_can_only_authorize_their_own_realtime_channel(): void
    {
        $this->useReverbAuthDriver();
        $user = User::factory()->regularUser()->create();
        $other = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-user.'.$user->uid,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-user.'.$other->uid,
            ])
            ->assertForbidden();
    }

    public function test_only_support_staff_can_authorize_the_inbox_channel(): void
    {
        $this->useReverbAuthDriver();
        $artisan = User::factory()->regularUser()->create();
        $staff = $this->supportStaff();

        $this->actingAs($artisan)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-support.inbox',
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-support.inbox',
            ])
            ->assertOk();
    }

    public function test_in_app_notifications_are_broadcast_when_sent(): void
    {
        Event::fake([UserNotificationUpdated::class]);
        $artisan = User::factory()->regularUser()->create();

        $announcement = Announcement::query()->create([
            'audience' => Announcement::AUDIENCE_USERS,
            'title' => 'Credits reminder',
            'subject' => 'Your credits',
            'body' => 'Hello {{first_name}}',
            'channels' => [Announcement::CHANNEL_IN_APP],
            'segment' => ['user_id' => $artisan->id],
            'status' => Announcement::STATUS_DRAFT,
            'created_by_user_id' => $artisan->id,
        ]);

        app(AnnouncementService::class)->queue($announcement);

        Event::assertDispatched(UserNotificationUpdated::class, function (UserNotificationUpdated $event) use ($artisan) {
            return $event->user->is($artisan)
                && $event->unreadCount >= 1
                && $event->item['title'] === 'Your credits';
        });
    }

    public function test_realtime_ping_returns_the_user_uid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('realtime.ping'))
            ->assertOk()
            ->assertJsonPath('uid', $user->uid);
    }

    private function useReverbAuthDriver(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'testkey',
            'broadcasting.connections.reverb.secret' => 'testsecret',
            'broadcasting.connections.reverb.app_id' => '1001',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);

        $this->app->make(\Illuminate\Broadcasting\BroadcastManager::class)->purge();
        require base_path('routes/channels.php');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function supportStaff(array $permissions = ['admin.support.manage']): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'customer_support_rt',
            'name' => 'Customer support',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }
}
