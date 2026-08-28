<?php

namespace App\Support\Hr;

use App\Models\ChecklistInstance;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\LeaveType;
use App\Models\User;

class HrDefaults
{
    /**
     * Insert the default leave types once. Existing rows are left untouched.
     */
    public static function ensureLeaveTypes(): void
    {
        $defaults = [
            ['key' => 'annual', 'name' => 'Annual leave', 'description' => 'Paid time off for rest and holidays.', 'allowance_days' => 20, 'color' => 'base', 'sort_order' => 10],
            ['key' => 'sick', 'name' => 'Sick leave', 'description' => 'Time off for illness or recovery.', 'allowance_days' => 10, 'color' => 'coral', 'sort_order' => 20],
            ['key' => 'casual', 'name' => 'Casual / compassionate', 'description' => 'Short-notice personal or family matters.', 'allowance_days' => 5, 'color' => 'amber', 'sort_order' => 30],
        ];

        foreach ($defaults as $definition) {
            LeaveType::query()->firstOrCreate(
                ['key' => $definition['key']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'allowance_days' => $definition['allowance_days'],
                    'color' => $definition['color'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );
        }
    }

    /**
     * Insert the default onboarding / offboarding checklist templates once.
     */
    public static function ensureChecklistTemplates(): void
    {
        $templates = [
            [
                'kind' => ChecklistTemplate::KIND_ONBOARDING,
                'name' => 'Standard onboarding',
                'items' => [
                    'Set up work email and accounts',
                    'Assign an initial platform role in Access & Roles',
                    'Share the staff handbook and policies',
                    'Collect signed offer letter and ID',
                    'Add compensation record',
                    'Introduce to the team',
                ],
            ],
            [
                'kind' => ChecklistTemplate::KIND_OFFBOARDING,
                'name' => 'Standard offboarding',
                'items' => [
                    'Revoke platform access in Access & Roles',
                    'Issue final payslip',
                    'Collect company assets and devices',
                    'Disable work email and accounts',
                    'Archive documents for compliance',
                    'Conduct exit conversation',
                ],
            ],
        ];

        foreach ($templates as $definition) {
            $existing = ChecklistTemplate::query()
                ->where('kind', $definition['kind'])
                ->first();

            if ($existing) {
                continue;
            }

            $template = ChecklistTemplate::query()->create([
                'kind' => $definition['kind'],
                'name' => $definition['name'],
                'is_active' => true,
            ]);

            foreach ($definition['items'] as $index => $label) {
                ChecklistTemplateItem::query()->create([
                    'checklist_template_id' => $template->id,
                    'label' => $label,
                    'sort_order' => ($index + 1) * 10,
                ]);
            }
        }
    }

    public static function ensureAll(): void
    {
        self::ensureLeaveTypes();
        self::ensureChecklistTemplates();
        DisciplinaryLetter::ensureTemplates();
    }

    /**
     * Attach the active template for a kind if this staff member has no
     * incomplete instance of that kind already.
     */
    public static function attachKind(User $user, string $kind, ?int $actorId = null): ?ChecklistInstance
    {
        $hasIncomplete = ChecklistInstance::query()
            ->where('user_id', $user->id)
            ->where('kind', $kind)
            ->whereNull('completed_at')
            ->exists();

        if ($hasIncomplete) {
            return null;
        }

        $template = ChecklistTemplate::query()
            ->with('items')
            ->where('kind', $kind)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $instance = ChecklistInstance::query()->create([
            'user_id' => $user->id,
            'checklist_template_id' => $template->id,
            'kind' => $template->kind,
            'created_by' => $actorId,
        ]);

        foreach ($template->items as $item) {
            $instance->items()->create([
                'label' => $item->label,
                'sort_order' => $item->sort_order,
            ]);
        }

        return $instance;
    }
}
