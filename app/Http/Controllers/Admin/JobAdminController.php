<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlagContentRequest;
use App\Http\Requests\Admin\UpdateAdminWorkLogRequest;
use App\Models\WorkLog;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobAdminController extends Controller
{
    public function index(): Response
    {
        $jobs = WorkLog::query()
            ->with(['user:id,name,email,business_name,slug,trade'])
            ->latest('id')
            ->limit(2500)
            ->get()
            ->map(fn (WorkLog $log) => [
                'id' => $log->id,
                'uid' => $log->uid,
                'description' => $log->description,
                'client_name' => $log->client_name,
                'category' => $log->job_category,
                'worked_on' => $log->worked_on?->format('j M Y'),
                'created_at' => $log->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'created_iso' => $log->created_at?->toIso8601String(),
                'backdated_days' => $log->worked_on && $log->created_at
                    ? $log->created_at->startOfDay()->diffInDays($log->worked_on)
                    : 0,
                'flagged' => $log->flagged_at !== null,
                'flag_reason' => $log->flag_reason,
                'hidden' => $log->hidden_at !== null,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->displayBusinessName(),
                    'email' => $log->user->email,
                    'trade' => $log->user->trade,
                ] : null,
            ])
            ->values();

        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => $jobs,
        ]);
    }

    public function flag(FlagContentRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = ['flagged_at' => $workLog->flagged_at?->toIso8601String()];

        $workLog->forceFill([
            'flagged_at' => now(),
            'flag_reason' => $data['reason'],
        ])->save();

        AdminAudit::record(
            'jobs.flagged',
            "{$request->user()->name} flagged job {$workLog->uid}.",
            $workLog,
            $old,
            ['flagged_at' => $workLog->flagged_at?->toIso8601String(), 'reason' => $data['reason']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Job flagged',
            'message' => 'This job is in the moderation queue.',
        ], ['flagged' => true]);
    }

    public function unflag(Request $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        $old = ['flagged_at' => $workLog->flagged_at?->toIso8601String(), 'reason' => $workLog->flag_reason];

        $workLog->forceFill([
            'flagged_at' => null,
            'flag_reason' => null,
        ])->save();

        AdminAudit::record('jobs.unflagged', "{$request->user()->name} cleared the flag on job {$workLog->uid}.", $workLog, $old, ['flagged_at' => null]);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Flag cleared',
            'message' => 'This job is no longer in the flagged queue.',
        ], ['flagged' => false]);
    }

    public function update(UpdateAdminWorkLogRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = $workLog->only(['description', 'client_name', 'worked_on', 'job_category']);

        $workLog->forceFill([
            'description' => $data['description'],
            'client_name' => $data['client_name'],
            'worked_on' => $data['worked_on'],
            'job_category' => $data['job_category'] ?? $workLog->job_category,
        ])->save();

        AdminAudit::record(
            'jobs.updated',
            "{$request->user()->name} edited job {$workLog->uid}: {$data['reason']}",
            $workLog,
            $old,
            [...$workLog->only(array_keys($old)), 'reason' => $data['reason']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Job updated',
            'message' => 'The work log was saved.',
        ], [
            'job' => [
                'id' => $workLog->id,
                'uid' => $workLog->uid,
                'description' => $workLog->description,
                'client_name' => $workLog->client_name,
                'category' => $workLog->job_category,
                'worked_on' => $workLog->worked_on?->toDateString(),
                'worked_on_label' => $workLog->worked_on?->format('j M Y'),
            ],
        ]);
    }

    public function hide(FlagContentRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');

        $workLog->forceFill([
            'hidden_at' => now(),
            'flag_reason' => $reason,
        ])->save();

        AdminAudit::record(
            'jobs.removed',
            "{$request->user()->name} removed job {$workLog->uid}: {$reason}",
            $workLog,
            ['hidden_at' => null],
            ['hidden_at' => now()->toIso8601String(), 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Job removed',
            'message' => 'It will no longer show on the public page.',
        ], ['hidden' => true]);
    }
}
