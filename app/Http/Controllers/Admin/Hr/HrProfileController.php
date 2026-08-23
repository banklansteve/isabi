<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\ExitStaffRequest;
use App\Http\Requests\Admin\Hr\UpdateHrProfileRequest;
use App\Models\ChecklistTemplate;
use App\Models\CompensationRecord;
use App\Models\DisciplinaryRecord;
use App\Models\HrProfile;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PerformanceNote;
use App\Models\StaffDocument;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Hr\HrDefaults;
use App\Support\Hr\HrPresenter;
use App\Support\Hr\LeaveManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrProfileController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        abort_unless($user->isStaff(), 404);

        $actor = $request->user();
        $canPayroll = $actor->canDo('hr.payroll.view');

        $user->load([
            'hrProfile',
            'performanceNotes.author',
            'staffDocuments.uploader',
            'checklistInstances.items.doneBy',
            'checklistInstances.template',
            'disciplinaryRecords.issuedBy',
            'disciplinaryRecords.document',
            'disciplinaryRecords.user',
        ]);

        $leaveRequests = $user->leaveRequests()
            ->with(['leaveType', 'decider'])
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (LeaveRequest $req) => HrPresenter::leaveRequestRow($req))
            ->values();

        $props = [
            'profile' => HrPresenter::profile($user),
            'leave' => [
                'balances' => LeaveManager::balances($user),
                'requests' => $leaveRequests,
                'types' => LeaveType::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'color', 'allowance_days']),
                'year' => LeaveManager::currentYear(),
            ],
            'performance' => [
                'notes' => $user->performanceNotes
                    ->sortByDesc('noted_on')
                    ->map(fn (PerformanceNote $note) => HrPresenter::performanceNoteRow($note))
                    ->values(),
                'ratings' => PerformanceNote::ratings(),
            ],
            'documents' => $user->staffDocuments
                ->sortByDesc('created_at')
                ->map(fn ($doc) => HrPresenter::documentRow($doc))
                ->values(),
            'documentTypes' => StaffDocument::typeOptions(),
            'checklists' => $user->checklistInstances
                ->map(fn ($instance) => HrPresenter::checklistInstance($instance))
                ->values(),
            'checklistTemplates' => ChecklistTemplate::query()
                ->where('is_active', true)
                ->get(['id', 'kind', 'name']),
            'discipline' => [
                'records' => $user->disciplinaryRecords
                    ->sortByDesc('occurred_on')
                    ->map(fn (DisciplinaryRecord $record) => HrPresenter::disciplineRow($record))
                    ->values(),
                'types' => DisciplinaryRecord::typeOptions(),
                'statuses' => DisciplinaryRecord::statusOptions(),
            ],
            'can' => [
                'manage' => $actor->canDo('hr.manage'),
                'leave' => $actor->canDo('hr.leave.manage'),
                'payroll_view' => $canPayroll,
                'payroll_manage' => $actor->canDo('hr.payroll.manage'),
            ],
        ];

        if ($canPayroll) {
            $current = CompensationRecord::currentFor($user);
            $history = $user->compensationRecords()
                ->with(['allowances', 'updater'])
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get()
                ->map(fn (CompensationRecord $record) => HrPresenter::compensationRow($record))
                ->values();

            $props['payroll'] = [
                'compensation' => $current ? HrPresenter::compensationRow($current) : null,
                'history' => $history,
                'payslips' => $user->payslips()
                    ->with('items')
                    ->orderByDesc('period_start')
                    ->get()
                    ->map(fn ($slip) => HrPresenter::payslipRow($slip))
                    ->values(),
            ];
        }

        return Inertia::render('Admin/Hr/Profile', $props);
    }

    public function store(UpdateHrProfileRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();
        $existing = $user->hrProfile;

        $profile = HrProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'position' => $data['position'] ?? null,
                'department' => $data['department'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'personal_email' => $data['personal_email'] ?? null,
                'personal_phone' => $data['personal_phone'] ?? null,
                'home_address' => $data['home_address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'employment_status' => $existing?->employment_status ?? HrProfile::STATUS_ACTIVE,
            ],
        );

        if ($existing === null) {
            HrDefaults::attachKind($user, ChecklistTemplate::KIND_ONBOARDING, $request->user()->id);
        }

        ActivityLogger::log(
            action: 'hr.profile_updated',
            summary: "{$request->user()->name} updated the HR profile for {$user->name}.",
            properties: ['staff_id' => $user->id, 'created' => $existing === null],
        );

        return redirect()
            ->route('admin.hr.staff.show', $user)
            ->with('toast', [
                'type' => 'success',
                'message' => $existing ? 'HR profile updated.' : 'HR profile created.',
            ]);
    }

    public function exit(ExitStaffRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);
        abort_if($user->isSuperAdmin(), 403, 'The Super Admin seat cannot be exited here.');

        $profile = HrProfile::query()->firstOrNew(['user_id' => $user->id]);
        $profile->fill([
            'employment_status' => HrProfile::STATUS_EXITED,
            'exit_date' => $request->validated('exit_date'),
            'exit_reason' => $request->validated('exit_reason'),
        ]);
        $profile->user_id = $user->id;
        $profile->save();

        HrDefaults::attachKind($user, ChecklistTemplate::KIND_OFFBOARDING, $request->user()->id);

        // Historical records (leave, payslips, performance notes, documents) are
        // intentionally preserved for compliance — nothing is deleted here.

        ActivityLogger::log(
            action: 'hr.staff_exited',
            summary: "{$request->user()->name} marked {$user->name} as exited.",
            properties: [
                'staff_id' => $user->id,
                'exit_date' => $request->validated('exit_date'),
                'reason' => $request->validated('exit_reason'),
            ],
        );

        return redirect()
            ->route('admin.hr.staff.show', $user)
            ->with('toast', [
                'type' => 'success',
                'message' => "{$user->name} is marked as exited. Remember to revoke their platform access in Access & Roles — the two systems are not synced.",
                'duration' => 9000,
            ]);
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);
        abort_unless($request->user()->canDo('hr.manage'), 403);

        $profile = $user->hrProfile;
        abort_unless($profile, 404);

        $profile->update([
            'employment_status' => HrProfile::STATUS_ACTIVE,
            'exit_date' => null,
            'exit_reason' => null,
        ]);

        HrDefaults::attachKind($user, ChecklistTemplate::KIND_ONBOARDING, $request->user()->id);

        ActivityLogger::log(
            action: 'hr.staff_reactivated',
            summary: "{$request->user()->name} reactivated {$user->name}.",
            properties: ['staff_id' => $user->id],
        );

        return redirect()
            ->route('admin.hr.staff.show', $user)
            ->with('toast', ['type' => 'success', 'message' => "{$user->name} is active again."]);
    }
}
