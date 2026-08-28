<?php

namespace Tests\Feature\Admin;

use App\Models\DisciplinaryAction;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryNote;
use App\Models\HrProfile;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Hr\DisciplinaryLetter;
use App\Support\Hr\HrDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DisciplinaryCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        HrDefaults::ensureAll();
        DisciplinaryLetter::ensureTemplates();
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function staffMember(): User
    {
        return User::factory()->operationsAdmin()->create();
    }

    private function withPermissions(array $permissions): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'case_role_'.uniqid(),
            'name' => 'Case role',
            'description' => 'Test role.',
            'icon' => 'ti ti-clipboard-text',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 920,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    /**
     * @return array<string, mixed>
     */
    private function openPayload(User $staff, User $owner, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $staff->id,
            'owner_id' => $owner->id,
            'category' => 'attendance',
            'severity' => 'moderate',
            'incident_on' => '2026-08-10',
            'description' => 'Did not attend two scheduled shifts and did not notify the team.',
            'reason' => 'Incident reported by the operations lead.',
        ], $overrides);
    }

    public function test_general_hr_access_cannot_view_cases(): void
    {
        $viewer = $this->withPermissions(['hr.view', 'hr.manage']);

        $this->actingAs($viewer)
            ->get(route('admin.hr.discipline.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_open_and_advance_a_case(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $admin))
            ->assertRedirect();

        $case = DisciplinaryCase::query()->first();
        $this->assertNotNull($case);
        $this->assertSame(DisciplinaryCase::STATUS_REPORTED, $case->status);
        $this->assertNotEmpty($case->reference);

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.transition', $case), [
                'status' => DisciplinaryCase::STATUS_INVESTIGATING,
                'reason' => 'Interviews scheduled with the reporting lead.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.notes.store', $case), [
                'body' => 'Spoke with the reporting lead. Dates confirmed.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $case->notes()->count());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'discipline.case_opened']);
    }

    public function test_opening_a_case_from_the_hr_profile_returns_to_that_file(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.store'), [
                ...$this->openPayload($staff, $admin),
                'return' => 'profile',
            ])
            ->assertRedirect();

        $case = DisciplinaryCase::query()->first();
        $this->assertNotNull($case);

        $this->actingAs($admin)
            ->get(route('admin.hr.staff.show', ['user' => $staff, 'tab' => 'discipline', 'case' => $case->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Hr/Profile')
                ->where('discipline.cases.0.id', $case->id)
                ->where('can.discipline_manage', true));
    }

    public function test_confidential_notes_are_hidden_from_non_super_handlers(): void
    {
        $admin = $this->superAdmin();
        $handler = $this->withPermissions(['hr.discipline.view', 'hr.discipline.manage']);
        $staff = $this->staffMember();

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $handler));

        $case = DisciplinaryCase::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.notes.store', $case), [
                'body' => 'Counsel advice reserved to Super Admin.',
                'confidential' => true,
            ])
            ->assertRedirect();

        $this->actingAs($handler)
            ->getJson(route('admin.hr.discipline.show', $case))
            ->assertOk()
            ->assertJsonPath('record.notes', [])
            ->assertJsonPath('can.view_confidential', false);

        $this->actingAs($admin)
            ->getJson(route('admin.hr.discipline.show', $case))
            ->assertOk()
            ->assertJsonPath('record.notes.0.body', 'Counsel advice reserved to Super Admin.')
            ->assertJsonPath('can.view_confidential', true);

        $this->actingAs($admin)
            ->get(route('admin.hr.discipline.show', $case))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Hr/Discipline/Index')
                ->where('opened_id', $case->id));

        $this->actingAs($admin)
            ->get(route('admin.hr.discipline.index', ['case' => $case->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Hr/Discipline/Index')
                ->where('opened_id', $case->id));
    }

    public function test_formal_action_requires_justification_and_creates_notice(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $admin));
        $case = DisciplinaryCase::query()->first();

        $this->actingAs($admin)->post(route('admin.hr.discipline.transition', $case), [
            'status' => DisciplinaryCase::STATUS_INVESTIGATING,
            'reason' => 'Review started.',
        ]);
        $this->actingAs($admin)->post(route('admin.hr.discipline.transition', $case), [
            'status' => DisciplinaryCase::STATUS_DECISION_PENDING,
            'reason' => 'Facts established.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.actions.store', $case), [
                'type' => DisciplinaryAction::TYPE_WRITTEN,
                'letter_body' => 'Notice body',
            ])
            ->assertSessionHasErrors('justification');

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.actions.store', $case), [
                'type' => DisciplinaryAction::TYPE_WRITTEN,
                'justification' => 'Repeated unnotified absences after a documented conversation.',
                'letter_body' => 'This letter confirms a written warning for {{staff_name}}.',
            ])
            ->assertRedirect();

        $case->refresh();
        $this->assertSame(DisciplinaryCase::STATUS_ACTION_ISSUED, $case->status);
        $action = $case->latestAction();
        $this->assertNotNull($action);
        $this->assertStringContainsString($staff->name, $action->letter_body);
    }

    public function test_staff_can_acknowledge_own_notice_but_cannot_open_the_case(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        $other = $this->staffMember();

        $this->actingAs($admin)->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $admin));
        $case = DisciplinaryCase::query()->first();
        $this->advanceToDecision($admin, $case);
        $this->actingAs($admin)->post(route('admin.hr.discipline.actions.store', $case), [
            'type' => DisciplinaryAction::TYPE_WRITTEN,
            'justification' => 'Recorded after review.',
            'letter_body' => 'Notice for {{staff_name}}.',
        ]);

        $action = $case->fresh()->latestAction();

        $this->actingAs($staff)
            ->get(route('admin.hr.discipline.show', $case))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('admin.notices.show', $action))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('admin.notices.show', $action))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('admin.notices.acknowledge', $action), ['acknowledged' => '1'])
            ->assertRedirect();

        $this->assertNotNull($action->fresh()->acknowledged_at);

        $this->actingAs($staff)
            ->post(route('admin.notices.respond', $action), ['response' => 'I accept the facts as recorded.'])
            ->assertRedirect();

        $this->assertSame('I accept the facts as recorded.', $action->fresh()->response_body);
    }

    public function test_appeal_is_reviewed_by_super_admin_and_history_is_kept(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $admin));
        $case = DisciplinaryCase::query()->first();
        $this->advanceToDecision($admin, $case);
        $this->actingAs($admin)->post(route('admin.hr.discipline.actions.store', $case), [
            'type' => DisciplinaryAction::TYPE_WRITTEN,
            'justification' => 'Recorded after review.',
            'letter_body' => 'Notice.',
        ]);

        $action = $case->fresh()->latestAction();
        $this->actingAs($staff)->post(route('admin.notices.acknowledge', $action), ['acknowledged' => '1']);
        $this->actingAs($staff)
            ->post(route('admin.notices.appeal', $action), ['grounds' => 'The dates used in the notice are incomplete.'])
            ->assertRedirect();

        $this->assertSame(DisciplinaryCase::STATUS_APPEALED, $case->fresh()->status);

        $appeal = $case->appeals()->first();
        $handler = $this->withPermissions(['hr.discipline.view', 'hr.discipline.manage']);

        $this->actingAs($handler)
            ->post(route('admin.hr.discipline.appeals.review', $appeal), [
                'outcome' => 'upheld',
                'reason' => 'The record is complete.',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.hr.discipline.appeals.review', $appeal), [
                'outcome' => 'upheld',
                'reason' => 'The original dates were checked against the roster.',
            ])
            ->assertRedirect();

        $this->assertSame('upheld', $appeal->fresh()->outcome);
        $this->assertSame(DisciplinaryCase::STATUS_ACTION_ISSUED, $case->fresh()->status);
        $this->assertSame(1, $case->actions()->count());
    }

    public function test_exit_preserves_cases(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        HrProfile::create(['user_id' => $staff->id, 'employment_status' => 'active', 'start_date' => '2026-01-01']);

        $this->actingAs($admin)->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $admin));
        $case = DisciplinaryCase::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.hr.staff.exit', $staff), [
                'exit_date' => '2026-08-01',
                'exit_reason' => 'Resigned',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disciplinary_cases', [
            'id' => $case->id,
            'user_id' => $staff->id,
            'description' => 'Did not attend two scheduled shifts and did not notify the team.',
        ]);
    }

    public function test_aggregate_export_does_not_name_staff(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)->post(route('admin.hr.discipline.store'), $this->openPayload($staff, $admin));

        $response = $this->actingAs($admin)->get(route('admin.hr.discipline.reports.export'));
        $response->assertOk();
        $this->assertStringNotContainsString($staff->name, $response->streamedContent());
        $this->assertStringNotContainsString($staff->email, $response->streamedContent());
    }

    public function test_notes_have_no_update_or_delete_route(): void
    {
        $this->assertFalse(collect(Route::getRoutes())->contains(
            fn ($route) => str_contains($route->uri(), 'discipline') && str_contains($route->uri(), 'notes') && in_array('PUT', $route->methods(), true)
        ));

        DisciplinaryNote::query();
    }

    private function advanceToDecision(User $admin, DisciplinaryCase $case): void
    {
        $this->actingAs($admin)->post(route('admin.hr.discipline.transition', $case), [
            'status' => DisciplinaryCase::STATUS_INVESTIGATING,
            'reason' => 'Investigation opened.',
        ]);
        $this->actingAs($admin)->post(route('admin.hr.discipline.transition', $case), [
            'status' => DisciplinaryCase::STATUS_DECISION_PENDING,
            'reason' => 'Ready for a decision.',
        ]);
    }
}
