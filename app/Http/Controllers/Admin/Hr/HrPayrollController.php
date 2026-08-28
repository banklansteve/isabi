<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\ReissuePayslipRequest;
use App\Http\Requests\Admin\Hr\StorePayslipRequest;
use App\Http\Requests\Admin\Hr\UpdateCompensationRequest;
use App\Models\CompensationRecord;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\User;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class HrPayrollController extends Controller
{
    public function updateCompensation(UpdateCompensationRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();

        $record = CompensationRecord::query()->create([
            'user_id' => $user->id,
            'currency' => $data['currency'],
            'base_salary' => $data['base_salary'],
            'pay_frequency' => $data['pay_frequency'],
            'effective_from' => $data['effective_from'],
            'note' => $data['note'] ?? null,
            'reason' => $data['reason'],
            'updated_by' => $request->user()->id,
        ]);

        foreach (($data['allowances'] ?? []) as $index => $allowance) {
            $record->allowances()->create([
                'label' => $allowance['label'],
                'amount' => $allowance['amount'],
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        ActivityLogger::log(
            action: 'hr.compensation_updated',
            summary: "{$request->user()->name} updated compensation for {$user->name}.",
            properties: [
                'staff_id' => $user->id,
                'base_salary' => $data['base_salary'],
                'reason' => $data['reason'],
            ],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'payroll'])
            ->with('toast', ['type' => 'success', 'message' => 'Compensation updated and logged.']);
    }

    public function storePayslip(StorePayslipRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();

        $allowances = collect($data['allowances'] ?? []);
        $deductions = collect($data['deductions'] ?? []);

        $basePay = (float) $data['base_pay'];
        $allowancesTotal = (float) $allowances->sum(fn ($a) => (float) $a['amount']);
        $deductionsTotal = (float) $deductions->sum(fn ($d) => (float) $d['amount']);
        $gross = $basePay + $allowancesTotal;
        $net = $gross - $deductionsTotal;

        $record = $user->compensationRecord;

        $payslip = Payslip::query()->create([
            'user_id' => $user->id,
            'period_label' => $data['period_label'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'currency' => $record?->currency ?? 'NGN',
            'base_pay' => $basePay,
            'allowances_total' => $allowancesTotal,
            'deductions_total' => $deductionsTotal,
            'gross_pay' => $gross,
            'net_pay' => $net,
            'status' => Payslip::STATUS_DRAFT,
            'notes' => $data['notes'] ?? null,
            'generated_by' => $request->user()->id,
        ]);

        foreach ($allowances->values() as $index => $allowance) {
            $payslip->items()->create([
                'kind' => PayslipItem::KIND_ALLOWANCE,
                'label' => $allowance['label'],
                'amount' => $allowance['amount'],
                'sort_order' => ($index + 1) * 10,
            ]);
        }
        foreach ($deductions->values() as $index => $deduction) {
            $payslip->items()->create([
                'kind' => PayslipItem::KIND_DEDUCTION,
                'label' => $deduction['label'],
                'amount' => $deduction['amount'],
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        ActivityLogger::log(
            action: 'hr.payslip_generated',
            summary: "{$request->user()->name} generated a payslip for {$user->name} ({$data['period_label']}).",
            properties: ['staff_id' => $user->id, 'payslip_id' => $payslip->id, 'net_pay' => $net],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'payroll'])
            ->with('toast', ['type' => 'success', 'message' => 'Payslip generated as a draft.']);
    }

    public function updatePayslipStatus(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.payroll.manage'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:draft,issued,paid'],
        ]);

        if ($data['status'] === Payslip::STATUS_ISSUED) {
            $payslip->issued_at ??= now();
        }

        if ($data['status'] === Payslip::STATUS_PAID) {
            $payslip->issued_at ??= now();
            $payslip->paid_at = now();

            // TODO: connect to payout provider if automated disbursement is added later.
            // This module records what is owed and what has been paid; it does not
            // itself move money or call a payout API.
        }

        $payslip->status = $data['status'];
        $payslip->save();

        ActivityLogger::log(
            action: 'hr.payslip_status_changed',
            summary: "{$request->user()->name} marked a payslip for {$payslip->user->name} as {$data['status']}.",
            properties: ['staff_id' => $payslip->user_id, 'payslip_id' => $payslip->id, 'status' => $data['status']],
        );

        return back()->with('toast', ['type' => 'success', 'message' => "Payslip marked as {$data['status']}."]);
    }

    public function reissue(ReissuePayslipRequest $request, Payslip $payslip): RedirectResponse
    {
        abort_unless(in_array($payslip->status, [Payslip::STATUS_ISSUED, Payslip::STATUS_PAID], true), 422, 'Only issued payslips can be reissued.');

        $payslip->load('items');
        $user = $payslip->user;
        $reason = $request->validated('reason');

        $copy = Payslip::query()->create([
            'user_id' => $payslip->user_id,
            'period_label' => $payslip->period_label,
            'period_start' => $payslip->period_start,
            'period_end' => $payslip->period_end,
            'currency' => $payslip->currency,
            'base_pay' => $payslip->base_pay,
            'allowances_total' => $payslip->allowances_total,
            'deductions_total' => $payslip->deductions_total,
            'gross_pay' => $payslip->gross_pay,
            'net_pay' => $payslip->net_pay,
            'status' => Payslip::STATUS_ISSUED,
            'issued_at' => now(),
            'notes' => trim(($payslip->notes ? $payslip->notes."\n" : '').'Reissued. '.$reason),
            'generated_by' => $request->user()->id,
        ]);

        foreach ($payslip->items as $item) {
            $copy->items()->create([
                'kind' => $item->kind,
                'label' => $item->label,
                'amount' => $item->amount,
                'sort_order' => $item->sort_order,
            ]);
        }

        ActivityLogger::log(
            action: 'hr.payslip_reissued',
            summary: "{$request->user()->name} reissued a payslip for {$user->name} ({$payslip->period_label}).",
            properties: [
                'staff_id' => $user->id,
                'source_payslip_id' => $payslip->id,
                'payslip_id' => $copy->id,
            ],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'payroll'])
            ->with('toast', ['type' => 'success', 'message' => 'Payslip reissued. The original record stays on file.']);
    }

    public function destroyPayslip(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.payroll.manage'), 403);
        abort_unless($payslip->status === Payslip::STATUS_DRAFT, 422, 'Only draft payslips can be deleted.');

        $user = $payslip->user;
        $payslip->delete();

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'payroll'])
            ->with('toast', ['type' => 'success', 'message' => 'Draft payslip deleted.']);
    }

    public function downloadPayslip(Request $request, Payslip $payslip): Response
    {
        abort_unless($request->user()->canDo('hr.payroll.view'), 403);

        $payslip->load(['items', 'user', 'generatedBy']);

        $pdf = Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip,
            'allowances' => $payslip->items->where('kind', PayslipItem::KIND_ALLOWANCE)->values(),
            'deductions' => $payslip->items->where('kind', PayslipItem::KIND_DEDUCTION)->values(),
        ])->setPaper('a4');

        $slug = Carbon::parse($payslip->period_start)->format('Y-m');

        return $pdf->download("payslip-{$payslip->user->id}-{$slug}.pdf");
    }
}
