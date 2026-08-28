<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\PatrolCase;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function withPermissions(array $permissions): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'job_role_'.uniqid(),
            'name' => 'Job test role',
            'description' => 'Test role.',
            'icon' => 'ti ti-briefcase',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 930,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    private function artisanUser(): User
    {
        return User::factory()->regularUser()->create([
            'business_name' => 'Demo Artisan',
            'slug' => 'demo-artisan-'.uniqid(),
            'whatsapp' => '08031234567',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function logJob(User $artisan, array $overrides = []): WorkLog
    {
        return WorkLog::withoutEvents(function () use ($artisan, $overrides) {
            $log = new WorkLog;
            $log->forceFill(array_merge([
                'user_id' => $artisan->id,
                'uid' => (string) Str::uuid(),
                'description' => 'Rewired a 3-bedroom bungalow and labelled the consumer unit.',
                'worked_on' => now()->toDateString(),
                'client_name' => 'Ada',
                'service_state' => 'Lagos',
                'service_lga' => 'Ikeja',
            ], $overrides))->save();

            return $log->fresh() ?? $log;
        });
    }

    public function test_guest_cannot_open_job_logs(): void
    {
        $job = $this->logJob($this->artisanUser());

        $this->get(route('admin.jobs.index'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.jobs.show', $job->uid))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($this->artisanUser())
            ->get(route('admin.jobs.index'))
            ->assertForbidden();
    }

    public function test_ops_without_permission_cannot_open_job_logs(): void
    {
        $ops = $this->withPermissions(['hr.view', 'hr.manage']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($ops)
            ->get(route('admin.jobs.index'))
            ->assertForbidden();

        $this->actingAs($ops)
            ->getJson(route('admin.jobs.show', $job->uid))
            ->assertForbidden();

        $this->actingAs($ops)
            ->postJson(route('admin.jobs.flag', $job->uid), ['reason' => 'Looks off to me.'])
            ->assertForbidden();
    }

    public function test_json_show_returns_the_job(): void
    {
        $super = $this->superAdmin();
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->getJson(route('admin.jobs.show', $job->uid))
            ->assertOk()
            ->assertJsonPath('record.uid', $job->uid)
            ->assertJsonPath('record.visibility', 'public')
            ->assertJsonPath('can.manage', true);
    }

    public function test_deep_link_opens_the_jobs_index_with_the_drawer(): void
    {
        $super = $this->superAdmin();
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->get(route('admin.jobs.show', $job->uid))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Jobs/Index')
                ->where('opened_uid', $job->uid));
    }

    public function test_super_can_flag_and_unflag_a_job(): void
    {
        $super = $this->superAdmin();
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->postJson(route('admin.jobs.flag', $job->uid), ['reason' => 'Copy-paste description across many jobs.'])
            ->assertOk()
            ->assertJsonPath('record.flagged', true);

        $this->assertNotNull($job->fresh()->flagged_at);
        $this->assertSame('Copy-paste description across many jobs.', $job->fresh()->flag_reason);

        $this->actingAs($super)
            ->postJson(route('admin.jobs.unflag', $job->uid), ['reason' => 'Checked with the artisan — legitimate.'])
            ->assertOk()
            ->assertJsonPath('record.flagged', false);

        $this->assertNull($job->fresh()->flagged_at);
        $this->assertNull($job->fresh()->flag_reason);
    }

    public function test_unflag_requires_a_reason(): void
    {
        $super = $this->superAdmin();
        $job = $this->logJob($this->artisanUser(), [
            'flagged_at' => now(),
            'flag_reason' => 'Earlier flag',
        ]);

        $this->actingAs($super)
            ->from(route('admin.jobs.index'))
            ->post(route('admin.jobs.unflag', $job->uid), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_super_can_hide_and_unhide_a_job(): void
    {
        $super = $this->superAdmin();
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->postJson(route('admin.jobs.hide', $job->uid), ['reason' => 'Not a real completed job.'])
            ->assertOk()
            ->assertJsonPath('record.hidden', true)
            ->assertJsonPath('record.visibility', 'hidden');

        $fresh = $job->fresh();
        $this->assertNotNull($fresh->hidden_at);
        $this->assertSame('Not a real completed job.', $fresh->hidden_reason);
        $this->assertNull($fresh->removed_at);

        $this->actingAs($super)
            ->postJson(route('admin.jobs.unhide', $job->uid), ['reason' => 'False alarm — restore it.'])
            ->assertOk()
            ->assertJsonPath('record.hidden', false)
            ->assertJsonPath('record.visibility', 'public');

        $this->assertNull($job->fresh()->hidden_at);
    }

    public function test_super_can_refer_a_job_to_operations_staff(): void
    {
        $super = $this->superAdmin();
        $ops = User::factory()->operationsAdmin()->create(['name' => 'Ops Lead']);
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->postJson(route('admin.jobs.refer', $job->uid), [
                'assignee_id' => $ops->id,
                'note' => 'Please look at the photos and client contact.',
            ])
            ->assertOk()
            ->assertJsonPath('record.referred', true)
            ->assertJsonPath('record.referral.assignee_id', $ops->id);

        $fresh = $job->fresh();
        $this->assertSame($ops->id, $fresh->referred_to_user_id);
        $this->assertSame($super->id, $fresh->referred_by_user_id);
        $this->assertSame('Please look at the photos and client contact.', $fresh->referred_note);
        $this->assertNotNull($fresh->referred_at);
    }

    public function test_refer_rejects_an_artisan_assignee(): void
    {
        $super = $this->superAdmin();
        $artisan = $this->artisanUser();
        $job = $this->logJob($artisan);

        $this->actingAs($super)
            ->from(route('admin.jobs.index'))
            ->post(route('admin.jobs.refer', $job->uid), [
                'assignee_id' => $artisan->id,
                'note' => 'Please look at this file.',
            ])
            ->assertSessionHasErrors('assignee_id');
    }

    public function test_message_endpoints_do_not_five_hundred(): void
    {
        Mail::fake();

        $super = $this->superAdmin();
        $job = $this->logJob($this->artisanUser());

        $this->actingAs($super)
            ->postJson(route('admin.jobs.message', $job->uid), [
                'channel' => 'email',
                'subject' => 'About your recent job',
                'body' => 'Hi {{first_name}}, please add a photo to this job.',
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->actingAs($super)
            ->postJson(route('admin.jobs.message', $job->uid), [
                'channel' => 'in_app',
                'subject' => 'About your recent job',
                'body' => 'Hi {{first_name}}, please add a photo to this job.',
            ])
            ->assertOk();

        $this->assertTrue(
            AnnouncementDelivery::query()->where('channel', Announcement::CHANNEL_IN_APP)->exists()
        );

        $this->actingAs($super)
            ->postJson(route('admin.jobs.message', $job->uid), [
                'channel' => 'whatsapp',
                'body' => 'Hi, this is Isabi about the job you logged.',
            ])
            ->assertOk()
            ->assertJsonStructure(['whatsapp_url']);
    }

    public function test_json_show_includes_an_existing_patrol_case(): void
    {
        $super = $this->superAdmin();
        $artisan = $this->artisanUser();
        $job = $this->logJob($artisan);

        PatrolCase::query()->create([
            'kind' => PatrolCase::KIND_JOB,
            'work_log_id' => $job->id,
            'user_id' => $artisan->id,
            'status' => PatrolCase::STATUS_NEW,
            'severity' => PatrolCase::SEVERITY_MEDIUM,
            'flagged_at' => now(),
        ]);

        $this->actingAs($super)
            ->getJson(route('admin.jobs.show', $job->uid))
            ->assertOk()
            ->assertJsonPath('record.patrol.status', PatrolCase::STATUS_NEW);
    }
}
