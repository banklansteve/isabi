<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\StoreLeaveTypeRequest;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\LeaveType;
use App\Support\ActivityLogger;
use App\Support\Hr\HrDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HrSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        HrDefaults::ensureAll();

        return Inertia::render('Admin/Hr/Settings', [
            'leaveTypes' => LeaveType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (LeaveType $type) => [
                    'id' => $type->id,
                    'key' => $type->key,
                    'name' => $type->name,
                    'description' => $type->description,
                    'allowance_days' => $type->allowance_days,
                    'color' => $type->color,
                    'is_active' => $type->is_active,
                    'seated' => $type->requests()->count(),
                ]),
            'colorTokens' => LeaveType::colorTokens(),
            'templates' => ChecklistTemplate::query()
                ->with('items')
                ->orderBy('kind')
                ->get()
                ->map(fn (ChecklistTemplate $template) => [
                    'id' => $template->id,
                    'kind' => $template->kind,
                    'name' => $template->name,
                    'is_active' => $template->is_active,
                    'items' => $template->items->map(fn ($item) => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'sort_order' => $item->sort_order,
                    ])->values(),
                ]),
            'can' => [
                'manage' => $request->user()->canDo('hr.manage'),
                'leave' => $request->user()->canDo('hr.leave.manage'),
            ],
        ]);
    }

    public function storeLeaveType(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        LeaveType::query()->create([
            'key' => $this->uniqueKey($data['name']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'allowance_days' => $data['allowance_days'],
            'color' => $data['color'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => ((int) LeaveType::query()->max('sort_order')) + 10,
        ]);

        ActivityLogger::log(
            action: 'hr.leave_type_created',
            summary: "{$request->user()->name} added the {$data['name']} leave type.",
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave type added.']);
    }

    public function updateLeaveType(StoreLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validated();

        $leaveType->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'allowance_days' => $data['allowance_days'],
            'color' => $data['color'],
            'is_active' => $data['is_active'] ?? $leaveType->is_active,
        ]);

        ActivityLogger::log(
            action: 'hr.leave_type_updated',
            summary: "{$request->user()->name} updated the {$leaveType->name} leave type.",
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave type updated.']);
    }

    public function destroyLeaveType(Request $request, LeaveType $leaveType): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.leave.manage'), 403);

        if ($leaveType->requests()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'This leave type has requests against it. Deactivate it instead.',
            ]);
        }

        $name = $leaveType->name;
        $leaveType->delete();

        ActivityLogger::log(
            action: 'hr.leave_type_deleted',
            summary: "{$request->user()->name} deleted the {$name} leave type.",
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Leave type deleted.']);
    }

    public function storeTemplateItem(Request $request, ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.manage'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
        ]);

        $checklistTemplate->items()->create([
            'label' => $data['label'],
            'sort_order' => ((int) $checklistTemplate->items()->max('sort_order')) + 10,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Checklist item added.']);
    }

    public function destroyTemplateItem(Request $request, ChecklistTemplateItem $checklistTemplateItem): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.manage'), 403);

        $checklistTemplateItem->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Checklist item removed.']);
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'leave_type';
        $key = $base;
        $i = 2;

        while (LeaveType::query()->where('key', $key)->exists()) {
            $key = $base.'_'.$i;
            $i++;
        }

        return $key;
    }
}
