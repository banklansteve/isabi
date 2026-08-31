<?php

namespace App\Support\Admin;

use App\Models\Review;
use App\Models\User;
use App\Models\StaffCaseReferral;
use Illuminate\Support\Str;

class ReviewAdminPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function listRow(Review $review): array
    {
        $submitted = $review->submitted_at ?? $review->created_at;
        $artisan = $review->relationLoaded('artisan') ? $review->artisan : null;

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
            'removed' => $review->removed_at !== null,
            'referred' => $review->referred_at !== null,
            'user' => $artisan ? [
                'id' => $artisan->id,
                'name' => $artisan->displayBusinessName(),
                'email' => $artisan->email,
                'trade' => $artisan->trade,
                'state' => $artisan->state,
                'suspended' => $artisan->suspended_at !== null,
            ] : null,
            'job' => $review->relationLoaded('workLog') && $review->workLog ? [
                'id' => $review->workLog->id,
                'uid' => $review->workLog->uid,
                'description' => $review->workLog->description,
                'client_name' => $review->workLog->client_name,
                'worked_on' => $review->workLog->worked_on?->format('j M Y'),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function panel(Review $review, User $actor): array
    {
        $review->loadMissing([
            'artisan:id,name,email,first_name,business_name,slug,trade,whatsapp,state,suspended_at',
            'workLog:id,uid,user_id,description,client_name,worked_on,job_category,review_requested_at',
            'assignedTo:id,name,email',
            'referredByStaff:id,name,email',
        ]);

        $referrals = app(StaffCaseReferralService::class);
        $block = $referrals->referralBlockFor(StaffCaseReferral::SUBJECT_REVIEW, $review->id);

        if (! $block && $review->referred_at) {
            $block = [
                'assignee_id' => $review->assigned_to_user_id,
                'assignee_name' => $review->assignedTo?->name ?: $review->assignedTo?->email,
                'note' => $review->referred_note,
                'queue' => StaffCaseReferral::QUEUE_MODERATION,
                'referred_at' => $review->referred_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y · g:ia'),
                'referred_by' => $review->referredByStaff?->name ?: $review->referredByStaff?->email,
            ];
        }

        return [
            'record' => self::record($review, $actor, $block),
            'can' => self::abilities($actor),
            'staff' => JobAdminPresenter::staffOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $referral
     * @return array<string, mixed>
     */
    public static function record(Review $review, User $actor, ?array $referral = null): array
    {
        $referrals = app(StaffCaseReferralService::class);
        $referral ??= $referrals->referralBlockFor(StaffCaseReferral::SUBJECT_REVIEW, $review->id);

        if (! $referral && $review->referred_at) {
            $review->loadMissing(['assignedTo:id,name,email', 'referredByStaff:id,name,email']);
            $referral = [
                'assignee_id' => $review->assigned_to_user_id,
                'assignee_name' => $review->assignedTo?->name ?: $review->assignedTo?->email,
                'note' => $review->referred_note,
                'referred_at' => $review->referred_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y · g:ia'),
                'referred_by' => $review->referredByStaff?->name ?: $review->referredByStaff?->email,
            ];
        }

        $visibility = 'public';
        if ($review->removed_at) {
            $visibility = 'removed';
        } elseif ($review->hidden_at) {
            $visibility = 'hidden';
        } elseif ($review->flagged_at) {
            $visibility = 'flagged';
        }

        $row = self::listRow($review);
        $artisan = $review->artisan;

        return [
            ...$row,
            'visibility' => $visibility,
            'visibility_label' => match ($visibility) {
                'removed' => 'Removed from public page',
                'hidden' => 'Hidden from public page',
                'flagged' => 'Flagged for moderation',
                default => 'Public',
            },
            'hidden_reason' => $review->hidden_reason,
            'flagged_at_label' => self::stamp($review->flagged_at),
            'hidden_at_label' => self::stamp($review->hidden_at),
            'removed_at_label' => self::stamp($review->removed_at),
            'referral' => $referral,
            'referred' => $review->referred_at !== null || $referral !== null,
            'artisan' => $artisan ? [
                'id' => $artisan->id,
                'name' => $artisan->displayBusinessName(),
                'email' => $artisan->email,
                'trade' => $artisan->trade,
                'state' => $artisan->state,
                'first_name' => $artisan->first_name,
                'suspended' => $artisan->suspended_at !== null,
                'user_url' => $actor->canDo('admin.users.view')
                    ? route('admin.users.show', $artisan)
                    : null,
                'public_url' => $artisan->publicUrl(),
            ] : null,
            'job' => $review->workLog ? [
                'id' => $review->workLog->id,
                'uid' => $review->workLog->uid,
                'description' => $review->workLog->description,
                'client_name' => $review->workLog->client_name,
                'category' => $review->workLog->job_category,
                'worked_on' => $review->workLog->worked_on?->format('j M Y'),
                'url' => $actor->canDo('admin.content.manage')
                    ? route('admin.jobs.show', $review->workLog)
                    : null,
            ] : null,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function abilities(User $actor): array
    {
        $manage = $actor->canDo('admin.content.manage');

        return [
            'manage' => $manage,
            'flag' => $manage,
            'hide' => $manage,
            'remove' => $manage,
            'refer' => app(StaffCaseReferralService::class)->canRefer($actor, StaffCaseReferral::SUBJECT_REVIEW),
            'view_users' => $actor->canDo('admin.users.view'),
            'suspend_user' => $actor->canDo('admin.users.manage'),
            'message' => $manage || $actor->canDo('admin.messaging.manage'),
        ];
    }

    private static function stamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->timezone(config('app.display_timezone'))->format('j M Y · g:ia');
    }
}
