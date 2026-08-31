<?php

namespace Tests\Feature\Admin;

use App\Models\PatrolCase;
use App\Models\PatrolCaseRule;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Patrol\PatrolCaseService;
use App\Support\Patrol\PatrolRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PatrolCaseTest extends TestCase
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
            'slug' => 'patrol_role_'.uniqid(),
            'name' => 'Patrol test role',
            'description' => 'Test role.',
            'icon' => 'ti ti-binoculars',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 940,
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
            'business_name' => 'Rapid Wire Works',
            'created_at' => now()->subDays(40),
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
                'description' => 'Installed a new consumer unit and labelled every circuit.',
                'worked_on' => now()->toDateString(),
                'client_name' => 'Ada',
            ], $overrides))->save();

            return $log->fresh() ?? $log;
        });
    }

    public function test_hr_view_cannot_open_patrol(): void
    {
        $viewer = $this->withPermissions(['hr.view', 'hr.manage']);

        $this->actingAs($viewer)
            ->get(route('admin.patrol.jobs'))
            ->assertForbidden();
    }

    public function test_patrol_view_can_see_queue_but_not_resolve(): void
    {
        $ops = $this->withPermissions(['patrol.view', 'patrol.investigate']);

        $this->actingAs($ops)
            ->get(route('admin.patrol.jobs'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Patrol/Jobs')
                ->where('can.view', true)
                ->where('can.investigate', true)
                ->where('can.resolve', false));
    }

    public function test_ops_panel_payload_includes_investigate_actions(): void
    {
        $ops = $this->withPermissions(['patrol.view', 'patrol.investigate']);
        $artisan = $this->artisanUser();
        $log = $this->logJob($artisan, ['description' => 'ok']);
        $case = app(PatrolRunner::class)->scanWorkLog($log);
        $this->assertNotNull($case);

        $this->actingAs($ops)
            ->getJson(route('admin.patrol.show', $case))
            ->assertOk()
            ->assertJsonPath('can.investigate', true)
            ->assertJsonPath('can.resolve', false)
            ->assertJsonPath('record.status', $case->status);
    }

    public function test_multi_rule_match_creates_one_case(): void
    {
        $artisan = $this->artisanUser();
        $base = now()->subMinutes(8);

        for ($i = 0; $i < 4; $i++) {
            $this->logJob($artisan, [
                'description' => 'done',
                'created_at' => $base->copy()->addMinutes($i),
                'updated_at' => $base->copy()->addMinutes($i),
            ]);
        }

        $target = $this->logJob($artisan, [
            'description' => 'done',
            'worked_on' => now()->subDays(12)->toDateString(),
            'created_at' => $base->copy()->addMinutes(4),
            'updated_at' => $base->copy()->addMinutes(4),
        ]);

        $case = app(PatrolRunner::class)->scanWorkLog($target);

        $this->assertNotNull($case);
        $this->assertSame(1, PatrolCase::query()->where('work_log_id', $target->id)->count());
        $this->assertGreaterThanOrEqual(2, $case->rules()->count());
        $this->assertSame('high', $case->severity);
        $this->assertTrue($case->rules()->pluck('rule_key')->contains('rapid_logging'));
        $this->assertTrue($case->rules()->pluck('rule_key')->contains('generic_minimal'));
    }

    public function test_high_severity_auto_hides_and_dismiss_restores(): void
    {
        $artisan = $this->artisanUser();
        $admin = $this->superAdmin();
        $base = now()->subMinutes(6);

        for ($i = 0; $i < 5; $i++) {
            $log = $this->logJob($artisan, [
                'description' => 'Rewired the kitchen sockets and tested every point.',
                'created_at' => $base->copy()->addMinutes($i),
                'updated_at' => $base->copy()->addMinutes($i),
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());
        $this->assertNotNull($case);
        $this->assertSame('high', $case->severity);
        $this->assertNotNull($case->auto_hidden_at);
        $this->assertNotNull($log->fresh()->hidden_at);
        $this->assertSame('patrol_auto', $log->fresh()->hidden_reason);
        $this->assertSame(PatrolCase::STATUS_NEW, $case->status);

        $this->actingAs($admin)
            ->post(route('admin.patrol.dismiss', $case), [
                'reason' => 'Burst was a catch-up after a site visit.',
            ])
            ->assertRedirect();

        $case->refresh();
        $log->refresh();
        $this->assertSame(PatrolCase::STATUS_RESOLVED_DISMISSED, $case->status);
        $this->assertNull($log->hidden_at);
        $this->assertNull($log->removed_at);
    }

    public function test_super_can_remove_a_job_log(): void
    {
        $artisan = $this->artisanUser();
        $admin = $this->superAdmin();
        $base = now()->subMinutes(6);

        for ($i = 0; $i < 5; $i++) {
            $log = $this->logJob($artisan, [
                'created_at' => $base->copy()->addMinutes($i),
                'updated_at' => $base->copy()->addMinutes($i),
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());
        $this->assertNotNull($case);

        $this->actingAs($admin)
            ->post(route('admin.patrol.remove', $case), [
                'reason' => 'Fabricated catch-up after a site visit.',
            ])
            ->assertRedirect();

        $case->refresh();
        $log->refresh();
        $this->assertSame(PatrolCase::STATUS_RESOLVED_ACTIONED, $case->status);
        $this->assertNotNull($log->removed_at);
        $this->assertSame('patrol_remove', $log->hidden_reason);
        $this->assertTrue(WorkLog::query()->whereKey($log->id)->exists());
    }

    public function test_super_can_approve_or_reject_an_ops_recommendation(): void
    {
        $artisan = $this->artisanUser();
        $ops = $this->withPermissions(['patrol.view', 'patrol.investigate']);
        $admin = $this->superAdmin();
        $log = $this->logJob($artisan, ['description' => 'ok']);
        $case = app(PatrolRunner::class)->scanWorkLog($log);
        $this->assertNotNull($case);

        $this->actingAs($ops)
            ->post(route('admin.patrol.recommend', $case), [
                'outcome' => 'dismiss',
                'reason' => 'Looks like a catch-up after a site visit.',
            ])
            ->assertRedirect();

        $this->assertSame(PatrolCase::STATUS_PENDING_APPROVAL, $case->fresh()->status);

        $this->actingAs($ops)
            ->post(route('admin.patrol.approve', $case), [
                'reason' => 'Ops should not finalize.',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.patrol.reject', $case), [
                'reason' => 'Need another look at the photos.',
            ])
            ->assertRedirect();

        $this->assertSame(PatrolCase::STATUS_IN_REVIEW, $case->fresh()->status);
        $this->assertNull($case->fresh()->recommended_outcome);

        $this->actingAs($ops)
            ->post(route('admin.patrol.recommend', $case), [
                'outcome' => 'remove',
                'reason' => 'Still looks fabricated.',
            ]);

        $this->actingAs($admin)
            ->post(route('admin.patrol.approve', $case), [
                'reason' => 'Agreed — archive the entry.',
            ])
            ->assertRedirect();

        $this->assertSame(PatrolCase::STATUS_RESOLVED_ACTIONED, $case->fresh()->status);
        $this->assertNotNull($log->fresh()->removed_at);
    }

    public function test_ops_cannot_remove_or_dismiss_high_severity(): void
    {
        $artisan = $this->artisanUser();
        $ops = $this->withPermissions(['patrol.view', 'patrol.investigate']);
        $base = now()->subMinutes(6);

        for ($i = 0; $i < 5; $i++) {
            $log = $this->logJob($artisan, [
                'created_at' => $base->copy()->addMinutes($i),
                'updated_at' => $base->copy()->addMinutes($i),
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());
        $this->assertSame('high', $case->severity);

        $this->actingAs($ops)
            ->post(route('admin.patrol.remove', $case), [
                'reason' => 'Looks fake.',
            ])
            ->assertForbidden();

        $this->actingAs($ops)
            ->post(route('admin.patrol.dismiss', $case), [
                'reason' => 'I will clear this myself.',
            ])
            ->assertForbidden();

        $this->actingAs($ops)
            ->post(route('admin.patrol.handoff', $case), [
                'reason' => 'Suspend them.',
            ])
            ->assertForbidden();

        $this->assertSame(PatrolCase::STATUS_NEW, $case->fresh()->status);
        $this->assertNull($log->fresh()->removed_at);
    }

    public function test_reused_client_contact_ignores_spaced_legitimate_repeats(): void
    {
        $artisan = $this->artisanUser();
        $number = '08035551234';

        $this->logJob($artisan, [
            'client_whatsapp' => $number,
            'description' => 'Serviced the gate motor at the family house.',
            'created_at' => now()->subMonths(5),
            'updated_at' => now()->subMonths(5),
        ]);

        $later = $this->logJob($artisan, [
            'client_whatsapp' => $number,
            'description' => 'Replaced the remote and tested the gate again.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $case = app(PatrolRunner::class)->scanWorkLog($later);

        $this->assertTrue(
            $case === null || ! $case->rules()->pluck('rule_key')->contains('reused_client_contact'),
            'Spaced repeats for the same client must not fire reused_client_contact.',
        );
    }

    public function test_reused_client_contact_flags_a_short_window_cluster(): void
    {
        $artisan = $this->artisanUser();
        $number = '08035559876';

        for ($i = 0; $i < 5; $i++) {
            $log = $this->logJob($artisan, [
                'client_whatsapp' => $number,
                'description' => 'Completed a wiring check at site '.$i,
                'created_at' => now()->subDays(2)->addHours($i),
                'updated_at' => now()->subDays(2)->addHours($i),
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());

        $this->assertNotNull($case);
        $this->assertTrue($case->rules()->pluck('rule_key')->contains('reused_client_contact'));
    }

    public function test_detector_command_creates_cases_and_never_resolves(): void
    {
        $artisan = $this->artisanUser();

        for ($i = 0; $i < 5; $i++) {
            $this->logJob($artisan, [
                'description' => 'done',
                'created_at' => now()->subMinutes(10 - $i),
                'updated_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $this->assertSame(0, PatrolCase::query()->count());

        Artisan::call('patrol:scan');

        $this->assertGreaterThan(0, PatrolCase::query()->count());
        $this->assertSame(0, PatrolCase::query()->whereIn('status', [
            PatrolCase::STATUS_RESOLVED_DISMISSED,
            PatrolCase::STATUS_RESOLVED_ACTIONED,
        ])->count());
        $this->assertStringContainsString('No cases were auto-resolved', Artisan::output());
    }

    public function test_scan_does_not_reopen_a_dismissed_case(): void
    {
        $artisan = $this->artisanUser();
        $admin = $this->superAdmin();
        $base = now()->subMinutes(6);

        for ($i = 0; $i < 5; $i++) {
            $log = $this->logJob($artisan, [
                'created_at' => $base->copy()->addMinutes($i),
                'updated_at' => $base->copy()->addMinutes($i),
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());
        $this->actingAs($admin)->post(route('admin.patrol.dismiss', $case), [
            'reason' => 'Checked with the artisan. Legitimate burst.',
        ]);

        app(PatrolRunner::class)->scanWorkLog($log->fresh());

        $this->assertSame(PatrolCase::STATUS_RESOLVED_DISMISSED, $case->fresh()->status);
        $this->assertSame(1, PatrolCase::query()->where('work_log_id', $log->id)->count());
    }

    public function test_ops_can_add_a_note(): void
    {
        $artisan = $this->artisanUser();
        $ops = $this->withPermissions(['patrol.view', 'patrol.investigate']);
        $log = $this->logJob($artisan, ['description' => 'ok']);
        $case = app(PatrolRunner::class)->scanWorkLog($log);
        $this->assertNotNull($case);

        $this->actingAs($ops)
            ->post(route('admin.patrol.notes.store', $case), [
                'body' => 'Asked the artisan for site photos.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $case->notes()->count());
        $this->assertSame('Asked the artisan for site photos.', $case->notes()->first()->body);
    }

    public function test_rapid_logging_ignores_identical_created_at_bulk_seed(): void
    {
        $artisan = $this->artisanUser();
        $stamp = now()->subDays(20);

        for ($i = 0; $i < 14; $i++) {
            $log = $this->logJob($artisan, [
                'description' => 'Installed kitchen cabinets and trimmed every joint on site '.$i.'.',
                'worked_on' => now()->subDays(40 + $i)->toDateString(),
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());

        $this->assertTrue(
            $case === null || ! $case->rules()->pluck('rule_key')->contains('rapid_logging'),
            'Bulk seed rows that share one created_at must not flag rapid_logging.',
        );
    }

    public function test_rapid_logging_flags_recent_distinct_submits(): void
    {
        $artisan = $this->artisanUser();
        $base = now()->subMinutes(4);

        for ($i = 0; $i < 5; $i++) {
            $log = $this->logJob($artisan, [
                'description' => 'Rewired the kitchen sockets and tested every point on pass '.$i.'.',
                'created_at' => $base->copy()->addSeconds(30 + ($i * 12)),
                'updated_at' => $base->copy()->addSeconds(30 + ($i * 12)),
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());

        $this->assertNotNull($case);
        $this->assertTrue($case->rules()->pluck('rule_key')->contains('rapid_logging'));
        $this->assertSame('high', $case->severity);
        $trigger = $case->rules()->where('rule_key', 'rapid_logging')->first()?->evidence['trigger'] ?? '';
        $this->assertMatchesRegularExpression('/\d+ jobs logged within \d+ minutes?/', $trigger);
        $this->assertStringNotContainsString('1 minutes', $trigger);
    }

    public function test_rapid_logging_ignores_historical_import_with_spread_job_dates(): void
    {
        $artisan = $this->artisanUser();
        $stamp = now()->subHours(6);

        for ($i = 0; $i < 8; $i++) {
            $log = $this->logJob($artisan, [
                'description' => 'Fitted new wardrobe doors and aligned the hinges on job '.$i.'.',
                'worked_on' => now()->subDays(12 * ($i + 1))->toDateString(),
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ]);
        }

        $case = app(PatrolRunner::class)->scanWorkLog($log->fresh());

        $this->assertTrue(
            $case === null || ! $case->rules()->pluck('rule_key')->contains('rapid_logging'),
            'Imported jobs with spread performed dates but identical created_at must not flag.',
        );
    }

    public function test_first_scan_of_old_data_does_not_mass_flag_rapid_logging(): void
    {
        $artisan = $this->artisanUser();
        $stamp = now()->subDays(18);

        for ($i = 0; $i < 14; $i++) {
            $this->logJob($artisan, [
                'description' => 'Completed a full wardrobe install and packed the offcuts on job '.$i.'.',
                'worked_on' => now()->subDays(20 + $i)->toDateString(),
                'created_at' => $stamp->copy()->addSeconds($i),
                'updated_at' => $stamp->copy()->addSeconds($i),
            ]);
        }

        $this->assertSame(0, PatrolCase::query()->count());

        Artisan::call('patrol:scan');

        $this->assertSame(0, PatrolCase::query()->whereHas('rules', function ($query) {
            $query->where('rule_key', 'rapid_logging');
        })->count());
    }

    public function test_stale_rapid_logging_only_cases_are_dismissed_and_visibility_restored(): void
    {
        $artisan = $this->artisanUser();
        $stamp = now()->subDays(20);
        $log = $this->logJob($artisan, [
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ]);
        $case = $this->openStaleRapidCase($log);

        $result = app(PatrolCaseService::class)->reviseStaleRapidLogging();

        $case->refresh();
        $log->refresh();
        $this->assertSame(1, $result['dismissed']);
        $this->assertSame(1, $result['restored']);
        $this->assertSame(PatrolCase::STATUS_RESOLVED_DISMISSED, $case->status);
        $this->assertSame(1, $case->rules()->where('rule_key', 'rapid_logging')->count());
        $this->assertNull($log->hidden_at);
        $this->assertNull($log->hidden_reason);
        $this->assertTrue(PatrolCase::query()->whereKey($case->id)->exists());
        $this->assertSame(
            'rule revised: historical bulk create',
            $case->actions()->where('action', 'dismissed')->latest('id')->first()?->reason,
        );
    }

    public function test_stale_rapid_logging_is_detached_when_other_rules_remain(): void
    {
        $artisan = $this->artisanUser();
        $stamp = now()->subDays(20);
        $log = $this->logJob($artisan, [
            'description' => 'ok',
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ]);
        $case = $this->openStaleRapidCase($log, extraRules: [
            [
                'rule_key' => 'generic_minimal',
                'severity' => 'low',
                'evidence' => ['trigger' => 'description is only 2 characters (minimum 12)'],
            ],
        ]);

        $result = app(PatrolCaseService::class)->reviseStaleRapidLogging();

        $case->refresh();
        $log->refresh();
        $this->assertSame(1, $result['detached']);
        $this->assertSame(0, $result['dismissed']);
        $this->assertSame(PatrolCase::STATUS_NEW, $case->status);
        $this->assertFalse($case->rules()->pluck('rule_key')->contains('rapid_logging'));
        $this->assertTrue($case->rules()->pluck('rule_key')->contains('generic_minimal'));
        $this->assertSame('low', $case->severity);
        $this->assertNull($log->hidden_at);
    }

    /**
     * @param  list<array{rule_key: string, severity: string, evidence: array<string, mixed>}>  $extraRules
     */
    private function openStaleRapidCase(WorkLog $log, array $extraRules = []): PatrolCase
    {
        $log->forceFill([
            'hidden_at' => now(),
            'hidden_reason' => 'patrol_auto',
        ])->save();

        $case = PatrolCase::query()->create([
            'work_log_id' => $log->id,
            'user_id' => $log->user_id,
            'status' => PatrolCase::STATUS_NEW,
            'severity' => 'high',
            'visibility_was_public' => true,
            'auto_hidden_at' => now(),
            'flagged_at' => now(),
        ]);

        PatrolCaseRule::query()->create([
            'patrol_case_id' => $case->id,
            'rule_key' => 'rapid_logging',
            'severity' => 'high',
            'evidence' => ['trigger' => '14 jobs logged within 1 minutes'],
            'detected_at' => now(),
        ]);

        foreach ($extraRules as $rule) {
            PatrolCaseRule::query()->create([
                'patrol_case_id' => $case->id,
                'rule_key' => $rule['rule_key'],
                'severity' => $rule['severity'],
                'evidence' => $rule['evidence'],
                'detected_at' => now(),
            ]);
        }

        return $case->fresh(['rules', 'workLog']) ?? $case;
    }
}
