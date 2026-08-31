<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlagContentRequest;
use App\Models\Review;
use App\Models\StaffCaseReferral;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\ApprovalService;
use App\Support\Admin\DashboardMetrics;
use App\Support\Admin\JobAdminPresenter;
use App\Support\Admin\ReviewAdminPresenter;
use App\Support\Admin\StaffCaseReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewAdminController extends Controller
{
    public function index(Request $request, DashboardMetrics $metrics): Response
    {
        return Inertia::render('Admin/Reviews/Index', $this->indexProps($request, $metrics));
    }

    public function show(Request $request, Review $review): Response|JsonResponse
    {
        abort_unless($request->user()?->canDo('admin.content.manage'), 403);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(ReviewAdminPresenter::panel($review, $request->user()));
        }

        return Inertia::render('Admin/Reviews/Index', [
            ...$this->indexProps($request, app(DashboardMetrics::class)),
            'opened_uid' => $review->uid,
        ]);
    }

    public function flag(FlagContentRequest $request, Review $review): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $old = ['flagged_at' => $review->flagged_at?->toIso8601String()];

        $review->forceFill([
            'flagged_at' => now(),
            'flag_reason' => $data['reason'],
        ])->save();

        AdminAudit::record(
            'reviews.flagged',
            "{$request->user()->name} flagged a review on {$review->artisan?->email}: {$data['reason']}",
            $review,
            $old,
            ['reason' => $data['reason']],
        );

        app(StaffCaseReferralService::class)->notifySuperAdminsOfFlag(
            $request->user(),
            StaffCaseReferral::SUBJECT_REVIEW,
            $review,
            $data['reason'],
        );

        return $this->mutated($request, $review, [
            'type' => 'success',
            'title' => 'Review flagged',
            'message' => 'This review is in the moderation queue.',
        ]);
    }

    public function unflag(Request $request, Review $review): JsonResponse|RedirectResponse
    {
        $old = ['flagged_at' => $review->flagged_at?->toIso8601String()];
        $review->forceFill(['flagged_at' => null, 'flag_reason' => null])->save();

        AdminAudit::record('reviews.unflagged', "{$request->user()->name} cleared a review flag.", $review, $old, ['flagged_at' => null]);

        return $this->mutated($request, $review, [
            'type' => 'success',
            'title' => 'Flag cleared',
            'message' => 'This review is no longer flagged.',
        ]);
    }

    public function hide(FlagContentRequest $request, Review $review, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');
        $old = ['hidden_at' => $review->hidden_at?->toIso8601String()];

        $result = $approvals->run(
            $request->user(),
            'reviews.hide',
            $review,
            $reason,
            [
                'review_id' => $review->id,
                'review_uid' => $review->uid,
                'reason' => $reason,
                'old' => $old,
                'new' => ['hidden_at' => now()->toIso8601String(), 'reason' => $reason],
            ],
            function () use ($review, $reason, $old, $request) {
                $review->forceFill([
                    'hidden_at' => now(),
                    'flag_reason' => $review->flag_reason ?: $reason,
                    'hidden_reason' => $reason,
                ])->save();

                AdminAudit::record(
                    'reviews.hidden',
                    "{$request->user()->name} hid a review: {$reason}",
                    $review,
                    $old,
                    ['hidden_at' => $review->hidden_at?->toIso8601String(), 'reason' => $reason],
                );
            },
        );

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve hiding this review before it takes effect.',
            ]);
        }

        return $this->mutated($request, $review, [
            'type' => 'success',
            'title' => 'Review hidden',
            'message' => 'It will no longer show on the public page.',
        ]);
    }

    public function remove(FlagContentRequest $request, Review $review, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');
        $old = ['removed_at' => $review->removed_at?->toIso8601String()];

        $result = $approvals->run(
            $request->user(),
            'reviews.remove',
            $review,
            $reason,
            [
                'review_id' => $review->id,
                'review_uid' => $review->uid,
                'reason' => $reason,
                'old' => $old,
                'new' => ['removed_at' => now()->toIso8601String(), 'reason' => $reason],
            ],
            function () use ($review, $reason, $old, $request) {
                $review->forceFill([
                    'removed_at' => now(),
                    'hidden_at' => $review->hidden_at ?? now(),
                    'flag_reason' => $review->flag_reason ?: $reason,
                    'hidden_reason' => $reason,
                ])->save();

                AdminAudit::record(
                    'reviews.removed',
                    "{$request->user()->name} removed a review: {$reason}",
                    $review,
                    $old,
                    ['removed_at' => $review->removed_at?->toIso8601String(), 'reason' => $reason],
                );
            },
        );

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve deleting this review before it takes effect.',
            ]);
        }

        return $this->mutated($request, $review, [
            'type' => 'success',
            'title' => 'Review deleted',
            'message' => 'It is no longer available on the public page.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(Request $request, DashboardMetrics $metrics): array
    {
        $reviews = Review::query()
            ->with([
                'artisan:id,name,email,business_name,slug,trade,state,whatsapp,avatar_url,suspended_at',
                'workLog:id,uid,user_id,description,client_name,worked_on,job_category,review_requested_at',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->limit(2500)
            ->get()
            ->map(fn (Review $review) => ReviewAdminPresenter::listRow($review))
            ->values();

        $insights = $metrics->reviewInsights();
        $openedUid = trim((string) $request->query('review', ''));

        return [
            'reviews' => $reviews,
            'completion' => $insights['completion'],
            'ratings' => $insights['ratings'],
            'flagged_trend' => $insights['flagged'],
            'opened_uid' => $openedUid !== '' ? $openedUid : null,
            'can' => ReviewAdminPresenter::abilities($request->user()),
        ];
    }

    /**
     * @param  array<string, mixed>  $toast
     */
    private function mutated(Request $request, Review $review, array $toast): JsonResponse|RedirectResponse
    {
        $fresh = $review->fresh(['artisan', 'workLog']);
        $panel = ReviewAdminPresenter::panel($fresh, $request->user());

        return AdminResponse::mutation($request, $toast, [
            'review' => ReviewAdminPresenter::listRow($fresh),
            'record' => $panel['record'],
        ]);
    }
}
