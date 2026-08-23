<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlagContentRequest;
use App\Models\Review;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewAdminController extends Controller
{
    public function index(DashboardMetrics $metrics): Response
    {
        $reviews = Review::query()
            ->with([
                'artisan:id,name,email,business_name,slug,trade,state,whatsapp,avatar_url',
                'workLog:id,uid,user_id,description,client_name,worked_on,job_category,review_requested_at',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->limit(2500)
            ->get()
            ->map(fn (Review $review) => $this->payload($review))
            ->values();

        $insights = $metrics->reviewInsights();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'completion' => $insights['completion'],
            'ratings' => $insights['ratings'],
            'flagged_trend' => $insights['flagged'],
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
            "{$request->user()->name} flagged a review on {$review->artisan?->email}.",
            $review,
            $old,
            ['reason' => $data['reason']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Review flagged',
            'message' => 'This review is in the moderation queue.',
        ], ['flagged' => true, 'flag_reason' => $review->flag_reason]);
    }

    public function unflag(Request $request, Review $review): JsonResponse|RedirectResponse
    {
        $old = ['flagged_at' => $review->flagged_at?->toIso8601String()];
        $review->forceFill(['flagged_at' => null, 'flag_reason' => null])->save();

        AdminAudit::record('reviews.unflagged', "{$request->user()->name} cleared a review flag.", $review, $old, ['flagged_at' => null]);

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Flag cleared',
            'message' => 'This review is no longer flagged.',
        ], ['flagged' => false]);
    }

    public function hide(FlagContentRequest $request, Review $review): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');
        $review->forceFill(['hidden_at' => now(), 'flag_reason' => $review->flag_reason ?: $reason])->save();

        AdminAudit::record(
            'reviews.hidden',
            "{$request->user()->name} hid a review: {$reason}",
            $review,
            ['hidden_at' => null],
            ['hidden_at' => now()->toIso8601String(), 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Review hidden',
            'message' => 'It will no longer show on the public page.',
        ], ['hidden' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Review $review): array
    {
        $submitted = $review->submitted_at ?? $review->created_at;

        return [
            'id' => $review->id,
            'uid' => $review->uid,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'client' => $review->client_display_name,
            'would_recommend' => $review->would_recommend,
            'referred_by' => $review->referred_by,
            'photo_url' => $review->photoThumbUrl(900),
            'submitted_at' => $submitted?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            'submitted_iso' => $submitted?->toIso8601String(),
            'flagged' => $review->flagged_at !== null,
            'flag_reason' => $review->flag_reason,
            'hidden' => $review->hidden_at !== null,
            'user' => $review->artisan ? [
                'id' => $review->artisan->id,
                'name' => $review->artisan->displayBusinessName(),
                'email' => $review->artisan->email,
                'trade' => $review->artisan->trade,
                'state' => $review->artisan->state,
                'whatsapp' => $review->artisan->whatsapp,
                'avatar_url' => $review->artisan->avatar_url,
            ] : null,
            'job' => $review->workLog ? [
                'id' => $review->workLog->id,
                'uid' => $review->workLog->uid,
                'description' => $review->workLog->description,
                'client_name' => $review->workLog->client_name,
                'category' => $review->workLog->job_category,
                'worked_on' => $review->workLog->worked_on?->format('j M Y'),
            ] : null,
        ];
    }
}
