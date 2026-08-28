<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\HrProfile;
use App\Models\Payslip;
use App\Models\User;
use App\Support\Hr\LeaveManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReportController extends Controller
{
    public function index(Request $request): Response
    {
        $canPayroll = $request->user()->canDo('hr.payroll.view');

        $props = [
            'headcount' => $this->headcount(),
            'leave' => $this->leaveUtilisation(),
            'performance' => $this->performanceCoverage(),
            'can' => ['payroll' => $canPayroll],
        ];

        if ($canPayroll) {
            $props['payroll'] = $this->payrollCost();
        }

        return Inertia::render('Admin/Hr/Reports', $props);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        $rows = match ($report) {
            'headcount' => $this->headcountRows(),
            'leave' => $this->leaveRows(),
            'performance' => $this->performanceRows(),
            'payroll' => $this->payrollRows($request),
            default => abort(404),
        };

        $filename = "hr-{$report}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function headcount(): array
    {
        $profiles = HrProfile::query()->get();
        $active = $profiles->where('employment_status', HrProfile::STATUS_ACTIVE)->count();
        $exited = $profiles->where('employment_status', HrProfile::STATUS_EXITED)->count();

        $byDepartment = $profiles
            ->where('employment_status', HrProfile::STATUS_ACTIVE)
            ->groupBy(fn ($p) => $p->department ?: 'Unassigned')
            ->map(fn ($group, $dept) => ['department' => $dept, 'count' => $group->count()])
            ->values();

        $months = collect(range(5, 0))->map(function (int $back) use ($profiles) {
            $month = Carbon::now()->startOfMonth()->subMonths($back);
            $hires = $profiles->filter(fn ($p) => $p->start_date && $p->start_date->isSameMonth($month))->count();
            $exits = $profiles->filter(fn ($p) => $p->exit_date && $p->exit_date->isSameMonth($month))->count();

            return [
                'label' => $month->format('M Y'),
                'hires' => $hires,
                'exits' => $exits,
            ];
        })->values();

        return [
            'active' => $active,
            'exited' => $exited,
            'total' => $profiles->count(),
            'by_department' => $byDepartment,
            'trend' => $months,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leaveUtilisation(): array
    {
        $year = LeaveManager::currentYear();

        $staff = User::query()
            ->whereHas('hrProfile')
            ->where('role', '!=', UserRole::User->value)
            ->orderBy('name')
            ->get();

        $rows = $staff->map(function (User $user) use ($year) {
            $balances = LeaveManager::balances($user, $year);
            $allowance = collect($balances)->sum('allowance_days');
            $used = collect($balances)->sum('used_days');

            return [
                'user_id' => $user->id,
                'name' => $user->name ?: $user->email,
                'allowance' => $allowance,
                'used' => $used,
                'remaining' => $allowance - $used,
                'utilisation' => $allowance > 0 ? (int) round(($used / $allowance) * 100) : 0,
            ];
        })->values();

        return [
            'year' => $year,
            'rows' => $rows,
            'total_allowance' => $rows->sum('allowance'),
            'total_used' => $rows->sum('used'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function performanceCoverage(): array
    {
        $since = Carbon::now()->subDays(90)->toDateString();

        $staff = User::query()
            ->whereHas('hrProfile', fn ($q) => $q->where('employment_status', HrProfile::STATUS_ACTIVE))
            ->where('role', '!=', UserRole::User->value)
            ->withCount(['performanceNotes as recent_notes_count' => fn ($q) => $q->where('noted_on', '>=', $since)])
            ->get();

        $covered = $staff->where('recent_notes_count', '>', 0)->count();

        return [
            'window_days' => 90,
            'total' => $staff->count(),
            'covered' => $covered,
            'uncovered' => $staff->count() - $covered,
            'coverage_pct' => $staff->count() > 0 ? (int) round(($covered / $staff->count()) * 100) : 0,
            'rows' => $staff->map(fn (User $user) => [
                'name' => $user->name ?: $user->email,
                'recent_notes' => $user->recent_notes_count,
                'has_recent' => $user->recent_notes_count > 0,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payrollCost(): array
    {
        $months = collect(range(5, 0))->map(function (int $back) {
            $month = Carbon::now()->startOfMonth()->subMonths($back);
            $total = Payslip::query()
                ->whereYear('period_start', $month->year)
                ->whereMonth('period_start', $month->month)
                ->sum('net_pay');

            return [
                'label' => $month->format('M Y'),
                'total' => (float) $total,
            ];
        })->values();

        return [
            'trend' => $months,
            'currency' => 'NGN',
            'total_last_6_months' => (float) $months->sum('total'),
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function headcountRows(): array
    {
        $data = $this->headcount();
        $rows = [['Department', 'Active headcount']];
        foreach ($data['by_department'] as $dept) {
            $rows[] = [$dept['department'], $dept['count']];
        }
        $rows[] = [];
        $rows[] = ['Month', 'Hires', 'Exits'];
        foreach ($data['trend'] as $month) {
            $rows[] = [$month['label'], $month['hires'], $month['exits']];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function leaveRows(): array
    {
        $data = $this->leaveUtilisation();
        $rows = [['Staff', 'Allowance (days)', 'Used (days)', 'Remaining (days)', 'Utilisation %']];
        foreach ($data['rows'] as $row) {
            $rows[] = [$row['name'], $row['allowance'], $row['used'], $row['remaining'], $row['utilisation']];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function performanceRows(): array
    {
        $data = $this->performanceCoverage();
        $rows = [['Staff', 'Notes in last 90 days', 'Has recent check-in']];
        foreach ($data['rows'] as $row) {
            $rows[] = [$row['name'], $row['recent_notes'], $row['has_recent'] ? 'Yes' : 'No'];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function payrollRows(Request $request): array
    {
        abort_unless($request->user()->canDo('hr.payroll.view'), 403);

        $data = $this->payrollCost();
        $rows = [['Month', 'Net payroll cost ('.$data['currency'].')']];
        foreach ($data['trend'] as $month) {
            $rows[] = [$month['label'], number_format($month['total'], 2, '.', '')];
        }

        return $rows;
    }
}
