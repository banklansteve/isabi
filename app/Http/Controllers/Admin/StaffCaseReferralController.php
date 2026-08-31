<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EscalateStaffCaseRequest;
use App\Http\Requests\Admin\ReferStaffCaseRequest;
use App\Models\StaffCaseReferral;
use App\Models\User;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\JobAdminPresenter;
use App\Support\Admin\StaffCaseReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffCaseReferralController extends Controller
{
    public function __construct(
        private readonly StaffCaseReferralService $referrals,
    ) {}

    public function index(Request $request): Response
    {
        $queue = strtolower(trim((string) $request->query('queue', StaffCaseReferral::QUEUE_MODERATION)));
        if ($queue === '') {
            $queue = StaffCaseReferral::QUEUE_MODERATION;
        }

        if ($queue !== 'all' && $queue !== 'jobs' && ! in_array($queue, StaffCaseReferral::QUEUES, true)) {
            $queue = StaffCaseReferral::QUEUE_MODERATION;
        }

        // "jobs" is a subject filter, not a queue column.
        $page = $queue === 'jobs'
            ? $this->jobsFilterPage($request->user())
            : $this->referrals->assignedPage($request->user(), $queue);

        return Inertia::render('Admin/Ops/Assigned', $page);
    }

    public function store(ReferStaffCaseRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        [$type, $subject] = $this->referrals->resolveSubject($data['subject_type'], $data['subject_uid']);

        $assignee = User::query()->findOrFail((int) $data['assignee_id']);
        $referral = $this->referrals->refer(
            $request->user(),
            $type,
            $subject,
            $assignee,
            $data['note'],
            $data['queue'] ?? null,
        );

        $payload = [
            'referral' => $this->referrals->present($referral),
            'block' => $this->referrals->referralBlockFor($type, (int) $subject->getKey()),
        ];

        if ($type === StaffCaseReferral::SUBJECT_JOB) {
            $panel = JobAdminPresenter::panel($subject->fresh(), $request->user());
            $payload['record'] = $panel['record'];
            $payload['job'] = JobAdminPresenter::listRow($subject->fresh(['user']));
        }

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Referred',
            'message' => 'Assigned to '.$assignee->name.'.',
        ], $payload);
    }

    public function returnCase(Request $request, StaffCaseReferral $referral): JsonResponse|RedirectResponse
    {
        abort_unless(
            (int) $referral->assignee_user_id === (int) $request->user()->id || $request->user()->isSuperAdmin(),
            403,
        );

        $note = trim((string) $request->input('note', ''));
        $returned = $this->referrals->returnToReferrer(
            $request->user(),
            $referral,
            $note !== '' ? $note : null,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Returned',
            'message' => 'Sent back to the original referrer.',
        ], [
            'referral' => $this->referrals->present($returned),
        ]);
    }

    public function escalate(EscalateStaffCaseRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        [$type, $subject] = $this->referrals->resolveSubject($data['subject_type'], $data['subject_uid']);

        $referral = $this->referrals->escalateToSuper(
            $request->user(),
            $type,
            $subject,
            $data['note'],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Escalated',
            'message' => 'Super Admin has been notified.',
        ], [
            'escalation' => $this->referrals->present($referral),
            'block' => $this->referrals->escalationBlockFor($type, (int) $subject->getKey()),
        ]);
    }

    public function acknowledge(Request $request, StaffCaseReferral $referral): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $referral = $this->referrals->acknowledgeSuperEscalation($request->user(), $referral);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Acknowledged',
            'message' => 'Marked as in review.',
        ], [
            'escalation' => $this->referrals->present($referral),
        ]);
    }

    public function completeEscalation(Request $request, StaffCaseReferral $referral): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $referral = $this->referrals->completeSuperEscalation($request->user(), $referral);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Resolved',
            'message' => 'Escalation closed for the requester.',
        ], [
            'escalation' => $this->referrals->present($referral),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jobsFilterPage(User $user): array
    {
        $page = $this->referrals->assignedPage($user, 'all');
        $page['queue'] = 'jobs';
        $page['items'] = array_values(array_filter(
            $page['items'],
            fn (array $item) => ($item['subject_type'] ?? '') === StaffCaseReferral::SUBJECT_JOB,
        ));

        return $page;
    }
}
