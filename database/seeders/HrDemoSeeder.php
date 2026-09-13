<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use App\Models\CompensationRecord;
use App\Models\HrProfile;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\User;
use App\Support\Hr\HrDefaults;
use Illuminate\Database\Seeder;

class HrDemoSeeder extends Seeder
{
    /**
     * Populate the People / HR module with illustrative data for the demo app.
     */
    public function run(): void
    {
        HrDefaults::ensureAll();

        $plan = [
            'support@kraftrack.test' => ['position' => 'Customer Support Agent', 'department' => 'Support', 'start' => '2025-03-01'],
            'trust@kraftrack.test' => ['position' => 'Trust & Safety Officer', 'department' => 'Trust & Safety', 'start' => '2025-06-15'],
            'verify@kraftrack.test' => ['position' => 'Verification Officer', 'department' => 'Operations', 'start' => '2026-01-06'],
            'finance@kraftrack.test' => ['position' => 'Finance & Billing Officer', 'department' => 'Finance', 'start' => '2024-11-04'],
            'growth@kraftrack.test' => ['position' => 'Growth & Referral Ops', 'department' => 'Growth', 'start' => '2025-09-01'],
            'content@kraftrack.test' => ['position' => 'Content & Communications', 'department' => 'Marketing', 'start' => '2025-02-10'],
        ];

        foreach ($plan as $email => $meta) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            HrProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'position' => $meta['position'],
                    'department' => $meta['department'],
                    'employment_type' => 'Full-time',
                    'employment_status' => HrProfile::STATUS_ACTIVE,
                    'start_date' => $meta['start'],
                    'emergency_contact_name' => 'Next of kin',
                    'emergency_contact_phone' => '0803'.random_int(1000000, 9999999),
                    'emergency_contact_relationship' => 'Family',
                ],
            );
        }

        $annual = LeaveType::where('key', 'annual')->first();

        // Put the support agent on leave right now so the "on leave" flag shows.
        $support = User::where('email', 'support@kraftrack.test')->first();
        if ($support && $annual && $support->leaveRequests()->count() === 0) {
            LeaveRequest::create([
                'user_id' => $support->id,
                'leave_type_id' => $annual->id,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'days' => 5,
                'note' => 'Family trip.',
                'status' => LeaveRequest::STATUS_APPROVED,
                'decided_at' => now()->subDays(2),
            ]);

            $support->performanceNotes()->firstOrCreate(
                ['noted_on' => now()->subMonths(1)->toDateString()],
                [
                    'rating' => 'exceeding',
                    'body' => 'Q2 check-in — consistently fast WhatsApp response times and calm with tricky accounts. Keep documenting resolutions.',
                ],
            );
        }

        // A pending request for the growth teammate to populate the approvals queue.
        $growth = User::where('email', 'growth@kraftrack.test')->first();
        $sick = LeaveType::where('key', 'sick')->first();
        if ($growth && $sick && $growth->leaveRequests()->count() === 0) {
            LeaveRequest::create([
                'user_id' => $growth->id,
                'leave_type_id' => $sick->id,
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDays(1)->toDateString(),
                'days' => 2,
                'note' => 'Medical appointment.',
                'status' => LeaveRequest::STATUS_PENDING,
            ]);
        }

        // Compensation + one issued payslip for finance.
        $finance = User::where('email', 'finance@kraftrack.test')->first();
        if ($finance && ! $finance->compensationRecord) {
            $record = CompensationRecord::create([
                'user_id' => $finance->id,
                'currency' => 'NGN',
                'base_salary' => 450000,
                'pay_frequency' => 'monthly',
                'effective_from' => '2026-01-01',
            ]);
            $record->allowances()->createMany([
                ['label' => 'Transport', 'amount' => 40000, 'sort_order' => 10],
                ['label' => 'Data', 'amount' => 15000, 'sort_order' => 20],
            ]);

            $payslip = Payslip::create([
                'user_id' => $finance->id,
                'period_label' => now()->subMonth()->format('F Y'),
                'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
                'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
                'currency' => 'NGN',
                'base_pay' => 450000,
                'allowances_total' => 55000,
                'deductions_total' => 37500,
                'gross_pay' => 505000,
                'net_pay' => 467500,
                'status' => Payslip::STATUS_PAID,
                'issued_at' => now()->subMonth()->endOfMonth(),
                'paid_at' => now()->subMonth()->endOfMonth(),
            ]);
            $payslip->items()->createMany([
                ['kind' => 'allowance', 'label' => 'Transport', 'amount' => 40000, 'sort_order' => 10],
                ['kind' => 'allowance', 'label' => 'Data', 'amount' => 15000, 'sort_order' => 20],
                ['kind' => 'deduction', 'label' => 'PAYE tax', 'amount' => 37500, 'sort_order' => 10],
            ]);
        }

        // Attach an onboarding checklist to the newest hire.
        $verify = User::where('email', 'verify@kraftrack.test')->first();
        $onboarding = ChecklistTemplate::where('kind', ChecklistTemplate::KIND_ONBOARDING)->with('items')->first();
        if ($verify && $onboarding && $verify->checklistInstances()->count() === 0) {
            $instance = $verify->checklistInstances()->create([
                'checklist_template_id' => $onboarding->id,
                'kind' => $onboarding->kind,
            ]);
            foreach ($onboarding->items as $i => $item) {
                $instance->items()->create([
                    'label' => $item->label,
                    'sort_order' => $item->sort_order,
                    'is_done' => $i < 2,
                    'done_at' => $i < 2 ? now() : null,
                ]);
            }
        }
    }
}
