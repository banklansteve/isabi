<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrProfile;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\Hr\HrDefaults;
use App\Support\Hr\HrPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrController extends Controller
{
    public function index(Request $request): Response
    {
        HrDefaults::ensureAll();

        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status');
        $department = (string) $request->string('department');

        $today = now()->toDateString();

        $members = User::query()
            ->staff()
            ->with('hrProfile')
            ->withExists(['leaveRequests as on_approved_leave' => function ($query) use ($today) {
                $query->where('status', LeaveRequest::STATUS_APPROVED)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            }])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($department !== '', function ($query) use ($department) {
                $query->whereHas('hrProfile', fn ($q) => $q->where('department', $department));
            })
            ->orderByRaw("role = 'super_admin' desc")
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => HrPresenter::directoryRow($user));

        if ($status !== '') {
            $members = $members->where('display_status', $status)->values();
        }

        $members = $members->values();

        $departments = HrProfile::query()
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->values();

        $pendingLeave = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        return Inertia::render('Admin/Hr/Directory', [
            'members' => $members,
            'departments' => $departments,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'department' => $department,
            ],
            'stats' => [
                'total' => $members->count(),
                'active' => $members->where('display_status', 'active')->count(),
                'on_leave' => $members->where('display_status', 'on_leave')->count(),
                'exited' => $members->where('display_status', 'exited')->count(),
                'pending_leave' => $pendingLeave,
            ],
            'can' => [
                'manage' => $request->user()->canDo('hr.manage'),
                'leave' => $request->user()->canDo('hr.leave.manage'),
                'payroll' => $request->user()->canDo('hr.payroll.view'),
            ],
        ]);
    }
}
