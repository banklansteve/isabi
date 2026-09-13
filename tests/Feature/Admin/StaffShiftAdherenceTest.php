<?php

namespace Tests\Feature\Admin;

use App\Enums\StaffStatus;
use App\Models\ActivityLog;
use App\Models\StaffIdleEvent;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Staff\StaffPresence;
use App\Support\Staff\StaffShiftAdherence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffShiftAdherenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_staff_index_includes_adherence_for_selected_date(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 10:00:00', 'Africa/Lagos'));

        $super = User::factory()->superAdmin()->create();
        $ops = $this->opsStaff([
            'shift_days' => [5],
            'shift_starts_at' => '08:00',
            'shift_ends_at' => '18:00',
            'shift_breaks' => [['start' => '12:00', 'end' => '13:00', 'label' => 'Lunch']],
            'last_login_at' => now()->setTime(8, 10),
            'staff_status' => StaffStatus::Active,
            'last_seen_at' => now(),
        ]);

        ActivityLog::query()->create([
            'user_id' => $ops->id,
            'action' => 'auth.admin_login',
            'summary' => 'Signed in to admin.',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->setTime(8, 10),
        ]);

        $response = $this->actingAs($super)
            ->get(route('admin.staff.index', ['adherence_date' => '2026-08-28']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('adherence_date', '2026-08-28')
                ->has('staff', 2));

        $row = collect($response->original->getData()['page']['props']['staff'])
            ->firstWhere('id', $ops->id);

        $this->assertSame('2026-08-28', $row['adherence']['date']);
        $this->assertSame('late', $row['adherence']['summary']['status']);
        $this->assertSame('12:00', $row['shift']['breaks'][0]['start']);
    }

    public function test_idle_over_seven_minutes_is_logged_when_staff_returns(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 10:20:00', 'Africa/Lagos'));

        $ops = $this->opsStaff([
            'shift_days' => [5],
            'shift_starts_at' => '08:00',
            'shift_ends_at' => '18:00',
            'staff_status' => StaffStatus::Active,
            'last_seen_at' => now()->subMinutes(10),
        ]);

        app(StaffPresence::class)->touch($ops);

        $this->assertDatabaseHas('staff_idle_events', [
            'user_id' => $ops->id,
            'work_date' => '2026-08-28',
        ]);

        $event = StaffIdleEvent::query()->where('user_id', $ops->id)->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->ended_at);
        $this->assertGreaterThanOrEqual(180, (int) $event->duration_seconds);
    }

    public function test_reconcile_idle_opens_event_for_current_away_staff(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 10:20:00', 'Africa/Lagos'));

        $ops = $this->opsStaff([
            'shift_days' => [5],
            'shift_starts_at' => '08:00',
            'shift_ends_at' => '18:00',
            'staff_status' => StaffStatus::Active,
            'last_seen_at' => now()->subMinutes(10),
        ]);

        app(StaffPresence::class)->reconcileIdle($ops);

        $this->assertDatabaseHas('staff_idle_events', [
            'user_id' => $ops->id,
            'work_date' => '2026-08-28',
            'ended_at' => null,
        ]);
    }

    public function test_idle_is_not_logged_during_scheduled_break(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 12:30:00', 'Africa/Lagos'));

        $ops = $this->opsStaff([
            'shift_days' => [5],
            'shift_starts_at' => '08:00',
            'shift_ends_at' => '18:00',
            'shift_breaks' => [['start' => '12:00', 'end' => '13:00', 'label' => 'Lunch']],
            'staff_status' => StaffStatus::Active,
            'last_seen_at' => now()->subMinutes(20),
        ]);

        app(StaffPresence::class)->reconcileIdle($ops);

        $this->assertDatabaseMissing('staff_idle_events', [
            'user_id' => $ops->id,
        ]);
    }

    public function test_super_admin_can_save_shift_breaks(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = $this->opsStaff();

        $this->actingAs($super)
            ->postJson(route('admin.staff.shift', $ops), [
                'shift_days' => [1, 2, 3, 4, 5],
                'shift_starts_at' => '08:00',
                'shift_ends_at' => '18:00',
                'shift_breaks' => [
                    ['start' => '12:00', 'end' => '13:00', 'label' => 'Lunch'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Shift saved');

        $ops->refresh();
        $this->assertSame('12:00', $ops->shift_breaks[0]['start']);
        $this->assertSame('13:00', $ops->shift_breaks[0]['end']);
    }

    public function test_adherence_payload_includes_idle_events_for_date(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 15:00:00', 'Africa/Lagos'));
        $ops = $this->opsStaff([
            'shift_days' => [5],
            'shift_starts_at' => '08:00',
            'shift_ends_at' => '18:00',
            'staff_status' => StaffStatus::Active,
        ]);

        StaffIdleEvent::query()->create([
            'user_id' => $ops->id,
            'work_date' => '2026-08-28',
            'started_at' => now()->setTime(10, 0),
            'ended_at' => now()->setTime(10, 15),
            'duration_seconds' => 900,
        ]);

        $payload = app(StaffShiftAdherence::class)->forDate($ops, '2026-08-28');

        $this->assertSame(1, $payload['summary']['idle_events_count']);
        $this->assertSame('15m', $payload['summary']['idle_total_label']);
        $this->assertNotEmpty($payload['idle_events']);
    }

    public function test_adherence_shows_signed_in_when_session_open_from_previous_day(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 10:00:00', 'Africa/Lagos'));

        $ops = $this->opsStaff([
            'shift_days' => [5],
            'shift_starts_at' => '08:00',
            'shift_ends_at' => '18:00',
            'staff_status' => StaffStatus::Active,
            'last_login_at' => Carbon::parse('2026-08-26 08:00:00', 'Africa/Lagos'),
            'last_logout_at' => null,
            'last_seen_at' => now(),
        ]);

        $payload = app(StaffShiftAdherence::class)->forDate($ops, '2026-08-28');

        $this->assertNotSame('missed', $payload['summary']['status']);
        $this->assertNotSame('Not signed in', $payload['summary']['status_label']);
        $this->assertNotSame('—', $payload['summary']['signed_in_at']);
    }

    public function test_super_admin_can_upload_account_avatar(): void
    {
        $this->mock(\App\Services\CloudinaryMediaService::class, function ($mock) {
            $mock->shouldReceive('uploadProfilePhoto')->once()->andReturn([
                'public_id' => 'Kraftrack/profiles/1/avatar',
                'url' => 'https://res.cloudinary.com/demo/image/upload/avatar.jpg',
            ]);
        });

        $super = User::factory()->superAdmin()->create(['avatar_url' => null]);

        $this->actingAs($super)
            ->post(route('admin.account.avatar'), [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('toast.title', 'Photo updated');

        $this->assertSame('https://res.cloudinary.com/demo/image/upload/avatar.jpg', $super->fresh()->avatar_url);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function opsStaff(array $overrides = []): User
    {
        $role = StaffRole::query()->firstOrCreate(
            ['slug' => 'customer_support'],
            [
                'name' => 'Customer support',
                'permissions' => ['admin.support.manage'],
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
        );

        $user = User::factory()->operationsAdmin()->create($overrides);
        $user->staffRoles()->syncWithoutDetaching([
            $role->id => [
                'assigned_by_user_id' => $user->id,
                'assigned_at' => now(),
            ],
        ]);

        return $user->fresh(['staffRoles']);
    }
}
