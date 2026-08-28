<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewApprovalRequest;
use App\Models\AdminApproval;
use App\Models\OpsMessageTemplate;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\ApprovalService;
use App\Support\Admin\OpsTemplatedMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly OpsTemplatedMessageService $messages,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canDo('admin.approvals.manage'), 403);

        $pending = $this->approvals->pending()->map(fn (AdminApproval $approval) => $this->row($approval));

        $recent = AdminApproval::query()
            ->with(['requester:id,name,email', 'reviewer:id,name'])
            ->whereIn('status', [AdminApproval::STATUS_APPROVED, AdminApproval::STATUS_REJECTED])
            ->latest('reviewed_at')
            ->limit(40)
            ->get()
            ->map(fn (AdminApproval $approval) => $this->row($approval));

        return Inertia::render('Admin/Approvals/Index', [
            'pending' => $pending->values()->all(),
            'recent' => $recent->values()->all(),
            'opened_uid' => null,
        ]);
    }

    public function show(Request $request, AdminApproval $approval): Response
    {
        abort_unless($request->user()?->canDo('admin.approvals.manage'), 403);

        $approval->loadMissing(['requester:id,name,email', 'reviewer:id,name']);

        $pending = $this->approvals->pending()->map(fn (AdminApproval $row) => $this->row($row));

        $recent = AdminApproval::query()
            ->with(['requester:id,name,email', 'reviewer:id,name'])
            ->whereIn('status', [AdminApproval::STATUS_APPROVED, AdminApproval::STATUS_REJECTED])
            ->latest('reviewed_at')
            ->limit(40)
            ->get()
            ->map(fn (AdminApproval $row) => $this->row($row));

        return Inertia::render('Admin/Approvals/Index', [
            'pending' => $pending->values()->all(),
            'recent' => $recent->values()->all(),
            'opened_uid' => $approval->uid,
            'opened' => $this->row($approval),
        ]);
    }

    public function approve(ReviewApprovalRequest $request, AdminApproval $approval): JsonResponse|RedirectResponse
    {
        $note = (string) ($request->validated('note') ?? '');

        $this->approvals->approve($approval, $request->user(), $note, function () use ($approval, $request) {
            $this->execute($approval, $request->user());
        });

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Approved',
            'message' => 'The action has been applied.',
        ]);
    }

    public function reject(ReviewApprovalRequest $request, AdminApproval $approval): JsonResponse|RedirectResponse
    {
        $this->approvals->reject(
            $approval,
            $request->user(),
            (string) ($request->validated('note') ?? 'Rejected'),
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Rejected',
            'message' => 'The requester has been notified.',
        ]);
    }

    private function execute(AdminApproval $approval, User $reviewer): void
    {
        $payload = $approval->payload ?? [];

        match ($approval->action) {
            'users.suspend' => $this->suspendUser($approval, $payload),
            'users.reinstate' => $this->reinstateUser($approval, $payload),
            'jobs.hide' => $this->hideJob($approval, $payload),
            'jobs.remove' => $this->removeJob($approval, $payload),
            'reviews.hide' => $this->hideReview($approval, $payload),
            'reviews.remove' => $this->removeReview($approval, $payload),
            'messages.send' => $this->sendMessage($approval, $payload, $reviewer),
            default => abort(422, 'Unknown approval action.'),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function suspendUser(AdminApproval $approval, array $payload): void
    {
        $user = User::query()->findOrFail((int) ($payload['user_id'] ?? $approval->subject_id));
        abort_unless($user->isRegularUser(), 422);

        $reason = (string) ($payload['reason'] ?? $approval->reason);
        $old = ['suspended_at' => $user->suspended_at?->toIso8601String()];

        $user->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();

        $this->forgetSessions($user);

        AdminAudit::record(
            'users.suspended',
            "Approved suspension of {$user->email}: {$reason}",
            $user,
            $old,
            ['suspended_at' => $user->suspended_at?->toIso8601String(), 'reason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reinstateUser(AdminApproval $approval, array $payload): void
    {
        $user = User::query()->findOrFail((int) ($payload['user_id'] ?? $approval->subject_id));
        $reason = (string) ($payload['reason'] ?? $approval->reason);
        $old = ['suspended_at' => $user->suspended_at?->toIso8601String()];

        $user->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        AdminAudit::record(
            'users.reinstated',
            "Approved reinstatement of {$user->email}: {$reason}",
            $user,
            $old,
            ['suspended_at' => null, 'reason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hideJob(AdminApproval $approval, array $payload): void
    {
        $job = WorkLog::query()->findOrFail((int) ($payload['work_log_id'] ?? $approval->subject_id));
        $reason = (string) ($payload['reason'] ?? $approval->reason);
        $old = ['hidden_at' => $job->hidden_at?->toIso8601String()];

        $job->forceFill([
            'hidden_at' => now(),
            'hidden_reason' => $reason,
        ])->save();

        AdminAudit::record(
            'jobs.hidden',
            "Approved hide of job {$job->uid}: {$reason}",
            $job,
            $old,
            ['hidden_at' => $job->hidden_at?->toIso8601String(), 'reason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function removeJob(AdminApproval $approval, array $payload): void
    {
        $job = WorkLog::query()->findOrFail((int) ($payload['work_log_id'] ?? $approval->subject_id));
        $reason = (string) ($payload['reason'] ?? $approval->reason);
        $old = ['removed_at' => $job->removed_at?->toIso8601String()];

        $job->forceFill([
            'removed_at' => now(),
            'hidden_at' => $job->hidden_at ?? now(),
            'hidden_reason' => $reason,
        ])->save();

        AdminAudit::record(
            'jobs.removed',
            "Approved soft-remove of job {$job->uid}: {$reason}",
            $job,
            $old,
            ['removed_at' => $job->removed_at?->toIso8601String(), 'reason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hideReview(AdminApproval $approval, array $payload): void
    {
        $review = Review::query()->findOrFail((int) ($payload['review_id'] ?? $approval->subject_id));
        $reason = (string) ($payload['reason'] ?? $approval->reason);
        $old = ['hidden_at' => $review->hidden_at?->toIso8601String()];

        $review->forceFill([
            'hidden_at' => now(),
            'flag_reason' => $review->flag_reason ?: $reason,
            'hidden_reason' => $reason,
        ])->save();

        AdminAudit::record(
            'reviews.hidden',
            "Approved hide of review {$review->uid}: {$reason}",
            $review,
            $old,
            ['hidden_at' => $review->hidden_at?->toIso8601String(), 'reason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function removeReview(AdminApproval $approval, array $payload): void
    {
        $review = Review::query()->findOrFail((int) ($payload['review_id'] ?? $approval->subject_id));
        $reason = (string) ($payload['reason'] ?? $approval->reason);
        $old = ['removed_at' => $review->removed_at?->toIso8601String()];

        $review->forceFill([
            'removed_at' => now(),
            'hidden_at' => $review->hidden_at ?? now(),
            'flag_reason' => $review->flag_reason ?: $reason,
            'hidden_reason' => $reason,
        ])->save();

        AdminAudit::record(
            'reviews.removed',
            "Approved soft-remove of review {$review->uid}: {$reason}",
            $review,
            $old,
            ['removed_at' => $review->removed_at?->toIso8601String(), 'reason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendMessage(AdminApproval $approval, array $payload, User $reviewer): void
    {
        $artisan = User::query()->findOrFail((int) $payload['user_id']);
        $template = OpsMessageTemplate::query()->findOrFail((int) $payload['template_id']);

        $this->messages->dispatch(
            $reviewer,
            $artisan,
            $template,
            (string) $payload['subject'],
            (string) $payload['body'],
            (array) $payload['channels'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function row(AdminApproval $approval): array
    {
        return [
            'uid' => $approval->uid,
            'action' => $approval->action,
            'action_label' => str_replace('.', ' → ', $approval->action),
            'reason' => $approval->reason,
            'status' => $approval->status,
            'requester' => $approval->requester?->only(['id', 'name', 'email']),
            'reviewer' => $approval->reviewer?->only(['id', 'name']),
            'review_note' => $approval->review_note,
            'when' => $approval->created_at?->timezone(config('app.display_timezone'))->diffForHumans(),
            'reviewed_when' => $approval->reviewed_at?->timezone(config('app.display_timezone'))->diffForHumans(),
            'payload' => $approval->payload,
        ];
    }

    private function forgetSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
