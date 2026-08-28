<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\AllocateLeaveRequest;
use App\Http\Requests\Admin\Hr\DecideLeaveRequest;
use App\Http\Requests\Admin\Hr\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Hr\HrPresenter;
use App\Support\Hr\LeaveManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HrLeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $pending = LeaveRequest::query()
            ->with(['user', 'leaveType', 'decider'])
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->orderBy('start_date')
            ->get()
            ->map(fn (LeaveRequest $req) => $this->rowWithBalance($req))
            ->values();

        $recent = LeaveRequest::query()
            ->with(['user', 'leaveType', 'decider'])
            ->whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_CANCELLED])
            ->orderByDesc('decided_at')
            ->limit(30)
            ->get()
            ->map(fn (LeaveRequest $req) => HrPresenter::leaveRequestRow($req))
            ->values();

        $upcoming = LeaveRequest::query()
            ->with(['user', 'leaveType', 'decider'])
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get()
            ->map(fn (LeaveRequest $req) => HrPresenter::leaveRequestRow($req))
            ->values();

        return Inertia::render('Admin/Hr/Leave', [
            'pending' => $pending,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'staff' => User::query()
                ->staff()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name ?: $user->email,
                ])
                ->values(),
            'types' => LeaveType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'allowance_days']),
            'year' => LeaveManager::currentYear(),
            'can' => [
                'manage' => $request->user()->canDo('hr.manage'),
                'leave' => $request->user()->canDo('hr.leave.manage'),
            ],
        ]);
    }

    public function calendar(Request $request): Response
    {
        $month = (string) $request->string('month');
        $anchor = $month !== '' ? Carbon::parse($month.'-01') : Carbon::now()->startOfMonth();
        $rangeStart = $anchor->copy()->startOfMonth();
        $rangeEnd = $anchor->copy()->endOfMonth();

        $events = LeaveRequest::query()
            ->with(['user', 'leaveType'])
            ->whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_PENDING])
            ->where('start_date', '<=', $rangeEnd->toDateString())
            ->where('end_date', '>=', $rangeStart->toDateString())
            ->orderBy('start_date')
            ->get()
            ->map(fn (LeaveRequest $req) => [
                'id' => $req->id,
                'user_id' => $req->user_id,
                'staff_name' => $req->user?->name ?: $req->user?->email,
                'leave_type' => $req->leaveType?->name,
                'color' => $req->leaveType?->color ?? 'base',
                'status' => $req->status,
                'start_date' => $req->start_date->toDateString(),
                'end_date' => $req->end_date->toDateString(),
                'days' => $req->days,
            ])
            ->values();

        return Inertia::render('Admin/Hr/Calendar', [
            'events' => $events,
            'month' => $anchor->format('Y-m'),
            'month_label' => $anchor->format('F Y'),
            'prev_month' => $anchor->copy()->subMonth()->format('Y-m'),
            'next_month' => $anchor->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function store(StoreLeaveRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();
        $start = $data['start_date'];
        $end = $data['end_date'];

        if (LeaveManager::hasOverlap($user, $start, $end)) {
            return back()->withErrors([
                'start_date' => 'This overlaps an existing pending or approved leave request for this staff member.',
            ]);
        }

        $days = LeaveManager::days($start, $end);
        $type = LeaveType::query()->findOrFail($data['leave_type_id']);
        $approveNow = (bool) ($data['approve_now'] ?? false) && $request->user()->canDo('hr.leave.manage');
        $override = (bool) ($data['override_negative'] ?? false);

        if ($approveNow) {
            $remaining = LeaveManager::remainingAfter($user, $type, $days);

            if ($remaining < 0 && ! $override) {
                return back()->withErrors([
                    'leave' => "Booking this would put {$user->name} at {$remaining} days on {$type->name}. Use the override to book anyway.",
                ]);
            }
        }

        LeaveRequest::query()->create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'note' => $data['note'] ?? null,
            'status' => $approveNow ? LeaveRequest::STATUS_APPROVED : LeaveRequest::STATUS_PENDING,
            'decided_by' => $approveNow ? $request->user()->id : null,
            'decided_at' => $approveNow ? now() : null,
            'override_negative' => $approveNow && $override && LeaveManager::remainingAfter($user, $type, $days) < 0,
        ]);

        ActivityLogger::log(
            action: $approveNow ? 'hr.leave_booked' : 'hr.leave_requested',
            summary: $approveNow
                ? "{$request->user()->name} booked {$days} days of {$type->name} for {$user->name}."
                : "{$request->user()->name} logged a {$days}-day leave request for {$user->name}.",
            properties: ['staff_id' => $user->id, 'days' => $days, 'approved' => $approveNow],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => $approveNow ? 'Leave booked.' : 'Leave request submitted for approval.',
        ]);
    }

    public function approve(DecideLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->isPending(), 422, 'This request is no longer pending.');

        $user = $leaveRequest->user;
        $override = (bool) $request->validated('override_negative', false);

        // Guard against a conflicting request approved in the meantime.
        $conflict = LeaveRequest::query()
            ->where('user_id', $leaveRequest->user_id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('id', '!=', $leaveRequest->id)
            ->overlapping($leaveRequest->start_date->toDateString(), $leaveRequest->end_date->toDateString())
            ->exists();

        if ($conflict) {
            return back()->withErrors([
                'leave' => 'Another approved leave already overlaps these dates.',
            ]);
        }

        $remaining = LeaveManager::remainingAfter(
            $user,
            $leaveRequest->leaveType,
            $leaveRequest->days,
        );

        if ($remaining < 0 && ! $override) {
            return back()->withErrors([
                'leave' => "Approving this would put {$user->name} at {$remaining} days on {$leaveRequest->leaveType->name}. Use the override to approve anyway.",
            ]);
        }

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_comment' => $request->validated('comment'),
            'override_negative' => $remaining < 0 && $override,
        ]);

        ActivityLogger::log(
            action: 'hr.leave_approved',
            summary: "{$request->user()->name} approved leave for {$user->name}.".($remaining < 0 && $override ? ' (negative-balance override)' : ''),
            properties: [
                'staff_id' => $user->id,
                'leave_request_id' => $leaveRequest->id,
                'override_negative' => $remaining < 0 && $override,
                'remaining_after' => $remaining,
            ],
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave approved.']);
    }

    public function reject(DecideLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->isPending(), 422, 'This request is no longer pending.');

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_REJECTED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_comment' => $request->validated('comment'),
        ]);

        ActivityLogger::log(
            action: 'hr.leave_rejected',
            summary: "{$request->user()->name} rejected leave for {$leaveRequest->user->name}.",
            properties: ['staff_id' => $leaveRequest->user_id, 'leave_request_id' => $leaveRequest->id],
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave rejected.']);
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.leave.manage'), 403);
        abort_if(
            in_array($leaveRequest->status, [LeaveRequest::STATUS_CANCELLED, LeaveRequest::STATUS_REJECTED], true),
            422,
            'This request cannot be cancelled.',
        );

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_CANCELLED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        // Cancelling an approved request automatically restores the balance,
        // since used days are computed only from approved requests.

        ActivityLogger::log(
            action: 'hr.leave_cancelled',
            summary: "{$request->user()->name} cancelled leave for {$leaveRequest->user->name}.",
            properties: ['staff_id' => $leaveRequest->user_id, 'leave_request_id' => $leaveRequest->id],
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave cancelled and balance restored.']);
    }

    public function allocate(AllocateLeaveRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();
        $year = (int) ($data['year'] ?? LeaveManager::currentYear());
        $type = LeaveType::query()->findOrFail($data['leave_type_id']);

        if (array_key_exists('remaining_days', $data) && $data['remaining_days'] !== null && $data['remaining_days'] !== '') {
            $used = LeaveManager::usedDays($user, $type, $year);
            $allowance = $used + (int) $data['remaining_days'];
        } else {
            $allowance = (int) $data['allowance_days'];
        }

        $user->leaveAllocations()->updateOrCreate(
            ['leave_type_id' => $type->id, 'year' => $year],
            [
                'allowance_days' => $allowance,
                'reason' => $data['reason'],
                'updated_by' => $request->user()->id,
            ],
        );

        ActivityLogger::log(
            action: 'hr.leave_allocation_updated',
            summary: "{$request->user()->name} set {$user->name}'s {$type->name} allowance to {$allowance} days for {$year}.",
            properties: [
                'staff_id' => $user->id,
                'leave_type_id' => $type->id,
                'allowance_days' => $allowance,
                'year' => $year,
                'reason' => $data['reason'],
            ],
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave balance updated.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowWithBalance(LeaveRequest $request): array
    {
        $row = HrPresenter::leaveRequestRow($request);
        $row['remaining_after'] = LeaveManager::remainingAfter(
            $request->user,
            $request->leaveType,
            $request->days,
        );
        $row['would_go_negative'] = $row['remaining_after'] < 0;

        return $row;
    }
}
