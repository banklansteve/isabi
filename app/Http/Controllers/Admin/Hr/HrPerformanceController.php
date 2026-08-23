<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\StorePerformanceNoteRequest;
use App\Models\PerformanceNote;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HrPerformanceController extends Controller
{
    public function store(StorePerformanceNoteRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();

        $user->performanceNotes()->create([
            'author_id' => $request->user()->id,
            'rating' => $data['rating'] ?? null,
            'body' => $data['body'],
            'noted_on' => $data['noted_on'],
        ]);

        ActivityLogger::log(
            action: 'hr.performance_note_added',
            summary: "{$request->user()->name} added a performance note for {$user->name}.",
            properties: ['staff_id' => $user->id],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'performance'])
            ->with('toast', ['type' => 'success', 'message' => 'Performance note added.']);
    }

    public function destroy(Request $request, PerformanceNote $performanceNote): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.manage'), 403);

        $user = $performanceNote->user;
        $performanceNote->delete();

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'performance'])
            ->with('toast', ['type' => 'success', 'message' => 'Performance note removed.']);
    }
}
