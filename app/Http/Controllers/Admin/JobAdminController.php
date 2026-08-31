<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageWorkLogArtisanRequest;
use App\Http\Requests\Admin\ModerateWorkLogRequest;
use App\Http\Requests\Admin\ReferWorkLogRequest;
use App\Http\Requests\Admin\UpdateAdminWorkLogRequest;
use App\Models\Announcement;
use App\Models\StaffCaseReferral;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\AnnouncementService;
use App\Support\Admin\ApprovalService;
use App\Support\Admin\JobAdminPresenter;
use App\Support\Admin\StaffCaseReferralService;
use App\Support\ReviewInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JobAdminController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Jobs/Index', $this->indexProps($request));
    }

    public function show(Request $request, WorkLog $workLog): Response|JsonResponse
    {
        abort_unless($request->user()?->canDo('admin.content.manage'), 403);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(JobAdminPresenter::panel($workLog, $request->user()));
        }

        return Inertia::render('Admin/Jobs/Index', [
            ...$this->indexProps($request, $workLog->uid),
        ]);
    }

    public function flag(ModerateWorkLogRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');
        $old = ['flagged_at' => $workLog->flagged_at?->toIso8601String(), 'reason' => $workLog->flag_reason];

        $workLog->forceFill([
            'flagged_at' => now(),
            'flag_reason' => $reason,
        ])->save();

        AdminAudit::record(
            'jobs.flagged',
            "{$request->user()->name} flagged job {$workLog->uid}: {$reason}",
            $workLog,
            $old,
            ['flagged_at' => $workLog->flagged_at?->toIso8601String(), 'reason' => $reason],
        );

        app(StaffCaseReferralService::class)->notifySuperAdminsOfFlag(
            $request->user(),
            StaffCaseReferral::SUBJECT_JOB,
            $workLog,
            $reason,
        );

        return $this->mutated($request, $workLog, [
            'type' => 'success',
            'title' => 'Job flagged',
            'message' => 'This job is in the moderation queue.',
        ]);
    }

    public function unflag(ModerateWorkLogRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');
        $old = ['flagged_at' => $workLog->flagged_at?->toIso8601String(), 'reason' => $workLog->flag_reason];

        $workLog->forceFill([
            'flagged_at' => null,
            'flag_reason' => null,
        ])->save();

        AdminAudit::record(
            'jobs.unflagged',
            "{$request->user()->name} cleared the flag on job {$workLog->uid}: {$reason}",
            $workLog,
            $old,
            ['flagged_at' => null, 'reason' => $reason],
        );

        return $this->mutated($request, $workLog, [
            'type' => 'success',
            'title' => 'Flag cleared',
            'message' => 'This job is no longer in the flagged queue.',
        ]);
    }

    public function update(UpdateAdminWorkLogRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->canDo('admin.content.manage'), 403);

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

        return $this->mutated($request, $workLog, [
            'type' => 'success',
            'title' => 'Job updated',
            'message' => 'The work log was saved.',
        ]);
    }

    public function hide(ModerateWorkLogRequest $request, WorkLog $workLog, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        abort_if($workLog->removed_at, 422, 'This job was removed through Patrol. Reopen it there.');

        $reason = $request->validated('reason');
        $old = ['hidden_at' => $workLog->hidden_at?->toIso8601String(), 'hidden_reason' => $workLog->hidden_reason];

        $result = $approvals->run(
            $request->user(),
            'jobs.hide',
            $workLog,
            $reason,
            [
                'work_log_id' => $workLog->id,
                'work_log_uid' => $workLog->uid,
                'reason' => $reason,
                'old' => $old,
                'new' => ['hidden_at' => now()->toIso8601String(), 'reason' => $reason],
            ],
            function () use ($workLog, $reason, $old, $request) {
                $workLog->forceFill([
                    'hidden_at' => now(),
                    'hidden_reason' => $reason,
                ])->save();

                AdminAudit::record(
                    'jobs.hidden',
                    "{$request->user()->name} hid job {$workLog->uid}: {$reason}",
                    $workLog,
                    $old,
                    ['hidden_at' => $workLog->hidden_at?->toIso8601String(), 'reason' => $reason],
                );
            },
        );

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve hiding this job before it takes effect.',
            ]);
        }

        return $this->mutated($request, $workLog, [
            'type' => 'success',
            'title' => 'Job hidden',
            'message' => 'It will no longer show on the public page.',
        ]);
    }

    public function remove(ModerateWorkLogRequest $request, WorkLog $workLog, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        abort_if($workLog->removed_at, 422, 'This job is already removed.');

        $reason = $request->validated('reason');
        $old = [
            'hidden_at' => $workLog->hidden_at?->toIso8601String(),
            'removed_at' => $workLog->removed_at?->toIso8601String(),
            'hidden_reason' => $workLog->hidden_reason,
        ];

        $result = $approvals->run(
            $request->user(),
            'jobs.remove',
            $workLog,
            $reason,
            [
                'work_log_id' => $workLog->id,
                'work_log_uid' => $workLog->uid,
                'reason' => $reason,
                'old' => $old,
                'new' => ['removed_at' => now()->toIso8601String(), 'reason' => $reason],
            ],
            function () use ($workLog, $reason, $old, $request) {
                $workLog->forceFill([
                    'hidden_at' => $workLog->hidden_at ?? now(),
                    'hidden_reason' => $workLog->hidden_reason ?: $reason,
                    'removed_at' => now(),
                ])->save();

                AdminAudit::record(
                    'jobs.removed',
                    "{$request->user()->name} soft-removed job {$workLog->uid}: {$reason}",
                    $workLog,
                    $old,
                    ['removed_at' => $workLog->removed_at?->toIso8601String(), 'reason' => $reason],
                );
            },
        );

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve removing this job before it takes effect.',
            ]);
        }

        return $this->mutated($request, $workLog, [
            'type' => 'success',
            'title' => 'Job removed',
            'message' => 'It is soft-removed from the public page.',
        ]);
    }

    public function unhide(ModerateWorkLogRequest $request, WorkLog $workLog): JsonResponse|RedirectResponse
    {
        abort_if($workLog->removed_at, 422, 'This job was removed through Patrol. Reopen it there.');

        $reason = $request->validated('reason');
        $old = ['hidden_at' => $workLog->hidden_at?->toIso8601String(), 'hidden_reason' => $workLog->hidden_reason];

        $workLog->forceFill([
            'hidden_at' => null,
            'hidden_reason' => null,
        ])->save();

        AdminAudit::record(
            'jobs.unhidden',
            "{$request->user()->name} restored job {$workLog->uid}: {$reason}",
            $workLog,
            $old,
            ['hidden_at' => null, 'reason' => $reason],
        );

        return $this->mutated($request, $workLog, [
            'type' => 'success',
            'title' => 'Job restored',
            'message' => 'It can show on the public page again.',
        ]);
    }

    public function refer(
        ReferWorkLogRequest $request,
        WorkLog $workLog,
        StaffCaseReferralService $referrals,
    ): JsonResponse|RedirectResponse {
        $data = $request->validated();
        $assignee = User::query()->findOrFail((int) $data['assignee_id']);

        $referrals->refer(
            $request->user(),
            StaffCaseReferral::SUBJECT_JOB,
            $workLog,
            $assignee,
            $data['note'],
            StaffCaseReferral::QUEUE_GENERAL,
        );

        return $this->mutated($request, $workLog->fresh(), [
            'type' => 'success',
            'title' => 'Referred to operations',
            'message' => 'The assignment is on this job file.',
        ]);
    }

    public function message(
        MessageWorkLogArtisanRequest $request,
        WorkLog $workLog,
        AnnouncementService $announcements,
    ): JsonResponse|RedirectResponse {
        $artisan = $workLog->user;
        abort_unless($artisan?->isRegularUser(), 422, 'This job has no artisan to message.');

        $data = $request->validated();
        $channel = $data['channel'];
        $subject = $data['subject'] !== '' ? $data['subject'] : 'A note from Isabi';
        $body = $announcements->interpolate($data['body'], $artisan);

        if ($channel === Announcement::CHANNEL_WHATSAPP) {
            $phone = ReviewInvite::normalizeWhatsapp($artisan->whatsapp);
            if (blank($phone)) {
                throw ValidationException::withMessages([
                    'channel' => 'This artisan has no WhatsApp number on file.',
                ]);
            }

            $url = 'https://wa.me/'.$phone.'?text='.rawurlencode($body);

            AdminAudit::record(
                'jobs.artisan_messaged',
                "{$request->user()->name} opened WhatsApp to {$artisan->email} about job {$workLog->uid}.",
                $workLog,
                null,
                ['channel' => $channel, 'body' => $body],
            );

            return AdminResponse::mutation($request, [
                'type' => 'success',
                'title' => 'WhatsApp ready',
                'message' => 'Continue in WhatsApp with the prefilled message.',
            ], [
                'whatsapp_url' => $url,
                'job' => JobAdminPresenter::listRow($workLog->fresh(['user'])),
            ]);
        }

        $announcement = Announcement::query()->create([
            'audience' => Announcement::AUDIENCE_USERS,
            'title' => $subject,
            'subject' => $subject,
            'body' => $data['body'],
            'channels' => [$channel],
            'segment' => ['user_id' => $artisan->id],
            'status' => Announcement::STATUS_DRAFT,
            'created_by_user_id' => $request->user()->id,
        ]);

        $announcements->queue($announcement);

        AdminAudit::record(
            'jobs.artisan_messaged',
            "{$request->user()->name} messaged {$artisan->email} ({$channel}) about job {$workLog->uid}.",
            $workLog,
            null,
            ['channel' => $channel, 'announcement_id' => $announcement->id],
        );

        $label = $channel === Announcement::CHANNEL_EMAIL ? 'Email queued' : 'In-app message sent';

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => $label,
            'message' => $channel === Announcement::CHANNEL_EMAIL
                ? 'The email is on its way to this artisan.'
                : 'They will see it in their Isabi inbox.',
        ], [
            'job' => JobAdminPresenter::listRow($workLog->fresh(['user'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(Request $request, ?string $openedUid = null): array
    {
        $this->ensureUids();

        $jobs = WorkLog::query()
            ->with(['user:id,name,email,business_name,slug,trade'])
            ->latest('id')
            ->limit(2500)
            ->get()
            ->map(fn (WorkLog $log) => JobAdminPresenter::listRow($log))
            ->values();

        $openedUid = $openedUid ?: trim((string) $request->query('job', ''));

        return [
            'jobs' => $jobs,
            'opened_uid' => $openedUid !== '' ? $openedUid : null,
            'can' => JobAdminPresenter::abilities($request->user()),
        ];
    }

    private function ensureUids(): void
    {
        WorkLog::query()
            ->whereNull('uid')
            ->orderBy('id')
            ->each(function (WorkLog $log): void {
                $log->forceFill(['uid' => (string) Str::uuid()])->saveQuietly();
            });
    }

    /**
     * @param  array<string, mixed>  $toast
     */
    private function mutated(Request $request, WorkLog $workLog, array $toast): JsonResponse|RedirectResponse
    {
        $panel = JobAdminPresenter::panel($workLog->fresh(), $request->user());

        return AdminResponse::mutation($request, $toast, [
            'job' => JobAdminPresenter::listRow($workLog->fresh(['user'])),
            'record' => $panel['record'],
        ]);
    }
}
