<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EscalateStaffCaseRequest;
use App\Http\Requests\Admin\ReferStaffCaseRequest;
use App\Http\Requests\Admin\ReassignStaffCaseRequest;
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
        $isSuper = (bool) $request->user()?->isSuperAdmin();
        $defaultQueue = $isSuper ? 'all' : StaffCaseReferral::QUEUE_MODERATION;
        $queue = strtolower(trim((string) $request->query('queue', $defaultQueue)));
        if ($queue === '') {
            $queue = $defaultQueue;
        }

        $allowed = array_merge(StaffCaseReferral::QUEUES, ['all', 'jobs']);
        if (! in_array($queue, $allowed, true)) {
            $queue = $defaultQueue;
        }

        $scope = 'mine';
        $viewingStaff = null;
        $desk = strtolower(trim((string) $request->query('desk', 'assigned')));
        if (! in_array($desk, ['assigned', 'referred'], true)) {
            $desk = 'assigned';
        }

        if ($desk === 'assigned' && $isSuper && $request->filled('staff')) {
            $viewingStaff = User::query()
                ->staff()
                ->whereKey((int) $request->query('staff'))
                ->first();
            $scope = 'staff';
        } elseif ($desk === 'assigned' && $isSuper) {
            $scope = strtolower(trim((string) $request->query('scope', 'mine')));
            if (! in_array($scope, ['mine', 'all'], true)) {
                $scope = 'mine';
            }
        }

        $page = $this->referrals->assignedPage($request->user(), $queue, $viewingStaff, $scope, $desk);

        if ($request->user()?->isStaff() && ! $request->user()->isRestrictedStaff() && $request->filled('referral_update')) {
            app(\App\Support\Admin\OpsAttentionFeed::class)->markReferrerUpdatesOpened(
                $request->user(),
                null,
                null,
                $request->integer('referral_update') ?: null,
            );
        }

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

    public function takeOver(Request $request, StaffCaseReferral $referral): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $note = trim((string) $request->input('note', ''));
        $owned = $this->referrals->takeOver(
            $request->user(),
            $referral,
            $note !== '' ? $note : null,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Taken over',
            'message' => 'This case is now on your desk. Finish the work, then mark it resolved.',
        ], [
            'referral' => $this->referrals->present($owned),
        ]);
    }

    public function reassign(ReassignStaffCaseRequest $request, StaffCaseReferral $referral): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $assignee = User::query()->findOrFail((int) $data['assignee_id']);

        $owned = $this->referrals->reassign(
            $request->user(),
            $referral,
            $assignee,
            $data['note'],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Reassigned',
            'message' => 'Now with '.$assignee->name.'.',
        ], [
            'referral' => $this->referrals->present($owned),
        ]);
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

    public function complete(Request $request, StaffCaseReferral $referral): JsonResponse|RedirectResponse
    {
        $referral = $this->referrals->complete($request->user(), $referral);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Resolved',
            'message' => 'Case closed.',
        ], [
            'referral' => $this->referrals->present($referral),
        ]);
    }
}
