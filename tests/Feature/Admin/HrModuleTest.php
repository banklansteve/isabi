<?php

namespace Tests\Feature\Admin;

use App\Models\ChecklistTemplate;
use App\Models\CompensationRecord;
use App\Models\HrProfile;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\StaffDocument;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Hr\HrDefaults;
use App\Support\Hr\LeaveManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        HrDefaults::ensureAll();
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function staffMember(): User
    {
        return User::factory()->operationsAdmin()->create();
    }

    private function hrViewer(): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'hr_viewer_'.uniqid(),
            'name' => 'HR Viewer',
            'description' => 'Read-only HR.',
            'icon' => 'ti ti-eye',
            'permissions' => ['hr.view'],
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 910,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    private function peopleOps(): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'people_ops_'.uniqid(),
            'name' => 'People Ops',
            'description' => 'HR access without compensation.',
            'icon' => 'ti ti-id-badge-2',
            'permissions' => ['hr.view', 'hr.manage', 'hr.leave.manage'],
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 900,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }

    public function test_guests_cannot_open_hr(): void
    {
        $this->get(route('admin.hr.index'))->assertRedirect(route('admin.login'));
    }

    public function test_operations_staff_cannot_open_hr(): void
    {
        $agent = $this->staffMember();

        $this->actingAs($agent)->get(route('admin.hr.index'))->assertForbidden();
    }

    public function test_super_admin_sees_the_directory(): void
    {
        $admin = $this->superAdmin();
        $this->staffMember();

        $this->actingAs($admin)
            ->get(route('admin.hr.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Hr/Directory')->has('members'));
    }

    public function test_super_admin_can_create_an_hr_profile(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)
            ->post(route('admin.hr.staff.profile', $staff), [
                'position' => 'Support Lead',
                'department' => 'Support',
                'start_date' => '2026-01-06',
            ])
            ->assertRedirect(route('admin.hr.staff.show', $staff));

        $this->assertDatabaseHas('hr_profiles', [
            'user_id' => $staff->id,
            'position' => 'Support Lead',
            'employment_status' => HrProfile::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('checklist_instances', [
            'user_id' => $staff->id,
            'kind' => ChecklistTemplate::KIND_ONBOARDING,
        ]);
    }

    public function test_exiting_preserves_historical_records(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        HrProfile::create(['user_id' => $staff->id, 'employment_status' => 'active', 'start_date' => '2026-01-01']);

        // Historical payslip that must survive an exit.
        Payslip::create([
            'user_id' => $staff->id,
            'period_label' => 'Jan 2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'base_pay' => 100, 'allowances_total' => 0, 'deductions_total' => 0,
            'gross_pay' => 100, 'net_pay' => 100, 'status' => 'issued',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hr.staff.exit', $staff), [
                'exit_date' => '2026-08-01',
                'exit_reason' => 'Resigned',
            ])
            ->assertRedirect(route('admin.hr.staff.show', $staff));

        $this->assertDatabaseHas('hr_profiles', [
            'user_id' => $staff->id,
            'employment_status' => HrProfile::STATUS_EXITED,
        ]);
        $this->assertDatabaseHas('payslips', ['user_id' => $staff->id, 'period_label' => 'Jan 2026']);
        $this->assertDatabaseHas('checklist_instances', [
            'user_id' => $staff->id,
            'kind' => ChecklistTemplate::KIND_OFFBOARDING,
        ]);
    }

    public function test_overlapping_leave_is_rejected(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        $type = LeaveType::first();

        LeaveRequest::create([
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-14',
            'days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hr.leave.store', $staff), [
                'leave_type_id' => $type->id,
                'start_date' => '2026-09-12',
                'end_date' => '2026-09-16',
            ])
            ->assertSessionHasErrors('start_date');

        $this->assertSame(1, LeaveRequest::where('user_id', $staff->id)->count());
    }

    public function test_approving_beyond_balance_requires_override(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        $type = LeaveType::where('key', 'annual')->first();
        $type->update(['allowance_days' => 3]);

        $request = LeaveRequest::create([
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'days' => 10,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        // Without override it is blocked.
        $this->actingAs($admin)
            ->post(route('admin.hr.leave.approve', $request))
            ->assertSessionHasErrors('leave');
        $this->assertSame(LeaveRequest::STATUS_PENDING, $request->fresh()->status);

        // With override it is approved and flagged.
        $this->actingAs($admin)
            ->post(route('admin.hr.leave.approve', $request), ['override_negative' => true]);
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertTrue($request->fresh()->override_negative);
    }

    public function test_cancelling_approved_leave_restores_balance(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        $type = LeaveType::where('key', 'annual')->first();

        $request = LeaveRequest::create([
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $used = LeaveManager::usedDays($staff, $type, 2026);
        $this->assertSame(5, $used);

        $this->actingAs($admin)->post(route('admin.hr.leave.cancel', $request));

        $this->assertSame(0, LeaveManager::usedDays($staff->fresh(), $type, 2026));
    }

    public function test_payroll_is_hidden_from_profile_without_payroll_permission(): void
    {
        $peopleOps = $this->peopleOps();
        $staff = $this->staffMember();
        HrProfile::create(['user_id' => $staff->id, 'employment_status' => 'active', 'start_date' => '2026-01-01']);

        $this->actingAs($peopleOps)
            ->get(route('admin.hr.staff.show', $staff))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Hr/Profile')
                ->where('can.payroll_view', false)
                ->missing('payroll')
            );
    }

    public function test_payroll_route_is_forbidden_without_permission(): void
    {
        $peopleOps = $this->peopleOps();
        $staff = $this->staffMember();

        $this->actingAs($peopleOps)
            ->post(route('admin.hr.compensation.update', $staff), [
                'currency' => 'NGN', 'base_salary' => 100, 'pay_frequency' => 'monthly', 'reason' => 'x',
            ])
            ->assertForbidden();

        $this->actingAs($peopleOps)
            ->get(route('admin.hr.reports.export', 'payroll'))
            ->assertForbidden();
    }

    public function test_compensation_edit_requires_a_reason_and_is_logged(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)
            ->post(route('admin.hr.compensation.update', $staff), [
                'currency' => 'NGN', 'base_salary' => 250000, 'pay_frequency' => 'monthly',
            ])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->post(route('admin.hr.compensation.update', $staff), [
                'currency' => 'NGN', 'base_salary' => 250000, 'pay_frequency' => 'monthly',
                'effective_from' => '2026-01-01',
                'reason' => 'Annual review',
                'allowances' => [['label' => 'Transport', 'amount' => 20000]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('compensation_records', ['user_id' => $staff->id, 'base_salary' => 250000]);
        $this->assertDatabaseHas('compensation_allowances', ['label' => 'Transport']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'hr.compensation_updated']);
    }

    public function test_compensation_update_preserves_history(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)->post(route('admin.hr.compensation.update', $staff), [
            'currency' => 'NGN',
            'base_salary' => 200000,
            'pay_frequency' => 'monthly',
            'effective_from' => '2026-01-01',
            'reason' => 'Offer letter',
            'allowances' => [['label' => 'Transport', 'amount' => 15000]],
        ]);

        $this->actingAs($admin)->post(route('admin.hr.compensation.update', $staff), [
            'currency' => 'NGN',
            'base_salary' => 280000,
            'pay_frequency' => 'monthly',
            'effective_from' => '2026-08-01',
            'reason' => 'Promotion',
            'allowances' => [
                ['label' => 'Transport', 'amount' => 15000],
                ['label' => 'Housing', 'amount' => 40000],
            ],
        ]);

        $this->assertSame(2, CompensationRecord::where('user_id', $staff->id)->count());
        $this->assertDatabaseHas('compensation_records', [
            'user_id' => $staff->id,
            'base_salary' => 200000,
            'reason' => 'Offer letter',
        ]);
        $this->assertSame(280000.0, (float) CompensationRecord::currentFor($staff)->base_salary);
        $this->assertCount(2, CompensationRecord::currentFor($staff)->allowances);
    }

    public function test_leave_can_be_allocated_and_booked_as_approved(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        $type = LeaveType::where('key', 'annual')->first();

        $this->actingAs($admin)
            ->post(route('admin.hr.leave.allocate', $staff), [
                'leave_type_id' => $type->id,
                'allowance_days' => 12,
                'year' => 2026,
                'reason' => 'Opening balance correction',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_allocations', [
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'year' => 2026,
            'allowance_days' => 12,
        ]);
        $this->assertSame(12, LeaveManager::allowanceFor($staff, $type, 2026));

        $this->actingAs($admin)
            ->post(route('admin.hr.leave.store', $staff), [
                'leave_type_id' => $type->id,
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-07',
                'approve_now' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $staff->id,
            'days' => 3,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);
        $this->assertSame(3, LeaveManager::usedDays($staff, $type, 2026));
        $this->assertInstanceOf(LeaveAllocation::class, $staff->leaveAllocations()->first());
    }

    public function test_general_hr_cannot_open_the_case_list(): void
    {
        $viewer = $this->hrViewer();

        $this->actingAs($viewer)
            ->get(route('admin.hr.discipline.index'))
            ->assertForbidden();
    }

    public function test_payslip_generation_computes_net(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)->post(route('admin.hr.payslips.store', $staff), [
            'period_label' => 'August 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'base_pay' => 200000,
            'allowances' => [['label' => 'Transport', 'amount' => 30000]],
            'deductions' => [['label' => 'Tax', 'amount' => 25000]],
        ])->assertRedirect();

        $payslip = Payslip::where('user_id', $staff->id)->first();
        $this->assertNotNull($payslip);
        $this->assertEquals(230000, $payslip->gross_pay);
        $this->assertEquals(205000, $payslip->net_pay);
    }

    public function test_on_leave_flag_surfaces_in_access_staff_list(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        HrProfile::create(['user_id' => $staff->id, 'employment_status' => 'active', 'start_date' => '2026-01-01']);
        $type = LeaveType::first();

        LeaveRequest::create([
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 3,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff.index', ['tab' => 'team']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'staff',
                fn ($staffList) => collect($staffList)->firstWhere('id', $staff->id)['on_leave'] === true,
            ));
    }

    public function test_super_admin_can_adjust_remaining_leave_with_a_reason(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();
        $type = LeaveType::where('key', 'annual')->first();

        LeaveRequest::create([
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-02',
            'days' => 2,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hr.leave.allocate', $staff), [
                'leave_type_id' => $type->id,
                'remaining_days' => 10,
                'year' => 2026,
                'reason' => 'Carry-over from 2025',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_allocations', [
            'user_id' => $staff->id,
            'leave_type_id' => $type->id,
            'year' => 2026,
            'allowance_days' => 12,
            'reason' => 'Carry-over from 2025',
        ]);
        $balance = collect(LeaveManager::balances($staff, 2026))->firstWhere('leave_type_id', $type->id);
        $this->assertSame(12, $balance['allowance_days']);
        $this->assertSame(10, $balance['remaining_days']);
    }

    public function test_super_admin_can_update_document_expiry(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        Storage::fake('local');
        $path = Storage::disk('local')->put("hr/documents/{$staff->id}/id.pdf", 'id');

        $document = StaffDocument::query()->create([
            'user_id' => $staff->id,
            'type' => 'id',
            'title' => 'National ID',
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'id.pdf',
            'mime' => 'application/pdf',
            'size' => 12,
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.hr.documents.update', $document), [
                'title' => 'National ID',
                'type' => 'id',
                'expiry_date' => '2027-06-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('staff_documents', [
            'id' => $document->id,
            'expiry_date' => '2027-06-01',
        ]);
    }

    public function test_super_admin_can_reissue_an_issued_payslip(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $payslip = Payslip::create([
            'user_id' => $staff->id,
            'period_label' => 'July 2026',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'base_pay' => 100,
            'allowances_total' => 0,
            'deductions_total' => 0,
            'gross_pay' => 100,
            'net_pay' => 100,
            'status' => Payslip::STATUS_ISSUED,
            'issued_at' => now(),
            'generated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hr.payslips.reissue', $payslip), [
                'reason' => 'Staff requested another copy',
            ])
            ->assertRedirect();

        $this->assertSame(2, Payslip::query()->where('user_id', $staff->id)->count());
        $this->assertDatabaseHas('payslips', [
            'user_id' => $staff->id,
            'period_label' => 'July 2026',
            'status' => Payslip::STATUS_ISSUED,
            'generated_by' => $admin->id,
        ]);
    }

    public function test_hr_profile_exposes_case_history_to_super_admin(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staffMember();

        $this->actingAs($admin)
            ->get(route('admin.hr.staff.show', $staff))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Hr/Profile')
                ->has('discipline.cases')
                ->where('can.discipline_manage', true));
    }
}
