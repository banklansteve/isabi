<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\ChecklistInstance;
use App\Models\ChecklistInstanceItem;
use App\Models\ChecklistTemplate;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HrChecklistController extends Controller
{
    public function attach(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.manage'), 403);
        abort_unless($user->isStaff(), 404);

        $data = $request->validate([
            'checklist_template_id' => ['required', 'integer', 'exists:checklist_templates,id'],
        ]);

        $template = ChecklistTemplate::with('items')->findOrFail($data['checklist_template_id']);

        $instance = ChecklistInstance::query()->create([
            'user_id' => $user->id,
            'checklist_template_id' => $template->id,
            'kind' => $template->kind,
            'created_by' => $request->user()->id,
        ]);

        foreach ($template->items as $item) {
            $instance->items()->create([
                'label' => $item->label,
                'sort_order' => $item->sort_order,
            ]);
        }

        ActivityLogger::log(
            action: 'hr.checklist_attached',
            summary: "{$request->user()->name} started the {$template->name} checklist for {$user->name}.",
            properties: ['staff_id' => $user->id, 'kind' => $template->kind],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'overview'])
            ->with('toast', ['type' => 'success', 'message' => "{$template->name} checklist started."]);
    }

    public function toggleItem(Request $request, ChecklistInstanceItem $checklistInstanceItem): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.manage'), 403);

        $done = ! $checklistInstanceItem->is_done;
        $checklistInstanceItem->update([
            'is_done' => $done,
            'done_by' => $done ? $request->user()->id : null,
            'done_at' => $done ? now() : null,
        ]);

        $instance = $checklistInstanceItem->instance()->with('items')->first();
        $allDone = $instance->items->every(fn ($item) => $item->is_done);
        $instance->update(['completed_at' => $allDone ? ($instance->completed_at ?? now()) : null]);

        $user = $instance->user;

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'overview'])
            ->with('toast', ['type' => 'success', 'message' => 'Checklist updated.']);
    }
}
