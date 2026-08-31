<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Patrol\ApprovePatrolCaseRequest;
use App\Http\Requests\Admin\Patrol\DismissPatrolCaseRequest;
use App\Http\Requests\Admin\Patrol\HandoffPatrolCaseRequest;
use App\Http\Requests\Admin\Patrol\HidePatrolReviewRequest;
use App\Http\Requests\Admin\Patrol\RecommendPatrolOutcomeRequest;
use App\Http\Requests\Admin\Patrol\RejectPatrolCaseRequest;
use App\Http\Requests\Admin\Patrol\RemovePatrolJobRequest;
use App\Http\Requests\Admin\Patrol\StartPatrolReviewRequest;
use App\Http\Requests\Admin\Patrol\StorePatrolNoteRequest;
use App\Models\PatrolCase;
use App\Models\User;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\OpsAttentionFeed;
use App\Support\Patrol\PatrolCaseService;
use App\Support\Admin\JobAdminPresenter;
use App\Support\Patrol\PatrolPresenter;
use App\Support\Patrol\PatrolSeverity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatrolController extends Controller
{
    public function __construct(private readonly PatrolCaseService $cases) {}

    public function jobs(Request $request): Response
    {
        abort_unless($request->user()?->canDo('patrol.view'), 403);

        return Inertia::render('Admin/Patrol/Jobs', $this->indexProps($request, 'jobs'));
    }

    public function reviews(Request $request): Response
    {
        abort_unless($request->user()?->canDo('patrol.view'), 403);

        return Inertia::render('Admin/Patrol/Reviews', $this->indexProps($request, 'reviews'));
    }

    public function show(Request $request, PatrolCase $patrolCase): Response|JsonResponse
    {
        abort_unless($request->user()?->canDo('patrol.view'), 403);

        if ($request->user()?->isOperationsAdmin()) {
            $prefix = $patrolCase->isReview() ? 'patrol:review:' : 'patrol:job:';
            app(OpsAttentionFeed::class)->markOpened(
                $request->user(),
                $prefix.$patrolCase->id,
            );
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($this->panel($patrolCase, $request->user()));
        }

        $queue = $patrolCase->isReview() ? 'reviews' : 'jobs';
        $page = $queue === 'reviews' ? 'Admin/Patrol/Reviews' : 'Admin/Patrol/Jobs';

        return Inertia::render($page, [
            ...$this->indexProps($request, $queue),
            'opened_id' => $patrolCase->id,
        ]);
    }

    public function storeNote(StorePatrolNoteRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->addNote($patrolCase, $request->user(), $request->validated('body'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Note added',
            'message' => 'The note is now part of the case record.',
        ]);
    }

    public function startReview(StartPatrolReviewRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->startReview($patrolCase, $request->user());

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'In review',
            'message' => 'This case is marked in review.',
        ]);
    }

    public function recommend(RecommendPatrolOutcomeRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $this->cases->recommend($patrolCase, $request->user(), $data['outcome'], $data['reason']);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Recommendation sent',
            'message' => 'This case is pending Super Admin approval.',
        ]);
    }

    public function dismiss(DismissPatrolCaseRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->dismiss($patrolCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Case dismissed',
            'message' => 'If this job was auto-hidden, it is public again.',
        ]);
    }

    public function remove(RemovePatrolJobRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->remove($patrolCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Job log removed',
            'message' => 'The entry is archived and no longer public.',
        ]);
    }

    public function hide(HidePatrolReviewRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->hide($patrolCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Review hidden',
            'message' => 'The review is no longer on the public profile.',
        ]);
    }

    public function approve(ApprovePatrolCaseRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $outcome = $patrolCase->recommended_outcome;
        $this->cases->approve($patrolCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Recommendation approved',
            'message' => 'The recommended outcome has been applied.',
        ], $outcome === 'suspend'
            ? ['user_url' => route('admin.users.show', $patrolCase->user_id)]
            : []);
    }

    public function reject(RejectPatrolCaseRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->reject($patrolCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Recommendation rejected',
            'message' => 'The case is back in review.',
        ]);
    }

    public function handoff(HandoffPatrolCaseRequest $request, PatrolCase $patrolCase): JsonResponse|RedirectResponse
    {
        $this->cases->handoff($patrolCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Handed off to Users',
            'message' => 'Continue on the artisan profile to warn or suspend.',
        ], [
            'user_url' => route('admin.users.show', $patrolCase->user_id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(Request $request, string $queue = 'jobs'): array
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $severity = (string) $request->query('severity', '');
        $rule = (string) $request->query('rule', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $sort = (string) $request->query('sort', 'severity');
        $tab = $queue === 'reviews' ? 'reviews' : 'jobs';
        $openedId = $request->query('case') ? (int) $request->query('case') : null;
        $openedReviewId = $request->query('review') ? (int) $request->query('review') : null;

        if ($openedReviewId && ! $openedId) {
            $openedId = PatrolCase::query()->reviews()->whereKey($openedReviewId)->value('id')
                ?? PatrolCase::query()->reviews()->where('review_id', $openedReviewId)->value('id');
            $tab = 'reviews';
        }

        $jobRows = $this->queueRows('jobs', $search, $status, $severity, $rule, $from, $to, $sort);
        $reviewRows = $this->queueRows('reviews', $search, $status, $severity, $rule, $from, $to, $sort);

        return [
            'queue' => $tab,
            'tab' => $tab,
            'cases' => $tab === 'reviews' ? $reviewRows : $jobRows,
            'job_cases' => $jobRows,
            'review_cases' => $reviewRows,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'severity' => $severity,
                'rule' => $rule,
                'from' => $from,
                'to' => $to,
                'sort' => $sort === 'flagged' ? 'flagged' : 'severity',
            ],
            'stats' => $this->queueStats($tab),
            'job_stats' => $this->queueStats('jobs'),
            'review_stats' => $this->queueStats('reviews'),
            'options' => PatrolPresenter::options(),
            'can' => $this->abilities($request->user()),
            'staff' => JobAdminPresenter::staffOptions(),
            'opened_id' => $openedId,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function queueRows(
        string $tab,
        string $search,
        string $status,
        string $severity,
        string $rule,
        string $from,
        string $to,
        string $sort,
    ): array {
        $query = PatrolCase::query()
            ->with([
                'artisan:id,name,email,business_name,trade,first_name,last_name',
                'workLog:id,uid,user_id,description,worked_on,hidden_at,removed_at,hidden_reason',
                'review:id,uid,work_log_id,rating,comment,hidden_at,removed_at,hidden_reason,submitted_at',
                'rules',
            ]);

        if ($tab === 'reviews') {
            $query->reviews();
        } else {
            $query->jobs();
        }

        if ($search !== '') {
            $query->whereHas('artisan', function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('business_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($status !== '' && array_key_exists($status, config('patrol.statuses', []))) {
            $query->where('status', $status);
        }

        if ($severity !== '' && array_key_exists($severity, config('patrol.severities', []))) {
            $query->where('severity', $severity);
        }

        $ruleConfig = $tab === 'reviews' ? 'patrol.review_rules' : 'patrol.rules';
        if ($rule !== '' && array_key_exists($rule, config($ruleConfig, []))) {
            $query->whereHas('rules', fn ($builder) => $builder->where('rule_key', $rule));
        }

        if ($from !== '') {
            $query->whereDate('flagged_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('flagged_at', '<=', $to);
        }

        if ($sort === 'flagged') {
            $query->orderByDesc('flagged_at');
        } else {
            $query->orderByRaw("case severity when 'high' then 3 when 'medium' then 2 else 1 end desc")
                ->orderByDesc('flagged_at');
        }

        return $query->limit(500)->get()->map(fn (PatrolCase $case) => PatrolPresenter::caseRow($case))->values()->all();
    }

    /**
     * @return array<string, int>
     */
    private function queueStats(string $tab): array
    {
        $query = PatrolCase::query();
        $tab === 'reviews' ? $query->reviews() : $query->jobs();

        return [
            'open' => (clone $query)->open()->count(),
            'new' => (clone $query)->where('status', PatrolCase::STATUS_NEW)->count(),
            'pending_approval' => (clone $query)->where('status', PatrolCase::STATUS_PENDING_APPROVAL)->count(),
            'in_review' => (clone $query)->where('status', PatrolCase::STATUS_IN_REVIEW)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panel(PatrolCase $case, User $actor): array
    {
        $case->load([
            'artisan',
            'workLog.media',
            'review.workLog',
            'rules',
            'notes.author',
            'actions.actor',
        ]);

        return [
            'record' => PatrolPresenter::caseDetail($case, $actor),
            'can' => $this->abilities($actor, $case),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(User $actor, ?PatrolCase $case = null): array
    {
        $investigate = $actor->canDo('patrol.investigate');
        $resolve = $actor->canDo('patrol.resolve');

        return [
            'view' => $actor->canDo('patrol.view'),
            'investigate' => $investigate,
            'resolve' => $resolve,
            'refer' => $actor->canDo('patrol.view') || $investigate,
            'dismiss_low' => $investigate && $case && PatrolSeverity::isLow($case->severity),
            'dismiss_any' => $resolve,
            'users_view' => $actor->canDo('admin.users.view'),
        ];
    }
}
