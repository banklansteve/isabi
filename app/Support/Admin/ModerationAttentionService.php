<?php

namespace App\Support\Admin;

use App\Models\Review;
use App\Models\StaffCaseReferral;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModerationAttentionService
{
    public function __construct(
        private readonly StaffCaseReferralService $referrals,
    ) {}

    /**
     * Flagged content + moderation referrals for the Super Admin priority inbox.
     *
     * @return list<array<string, mixed>>
     */
    public function superAdminItems(User $user): array
    {
        if (! $user->isSuperAdmin()) {
            return [];
        }

        $referredKeys = $this->activeModerationSubjectKeys();

        return [
            ...$this->flaggedJobItems($referredKeys),
            ...$this->flaggedReviewItems($referredKeys),
            ...$this->moderationReferralItems(),
        ];
    }

    /**
     * @param  list<string>  $skipKeys
     * @return list<array<string, mixed>>
     */
    private function flaggedJobItems(array $skipKeys): array
    {
        if (! Schema::hasTable('work_logs') || ! Schema::hasColumn('work_logs', 'flagged_at')) {
            return [];
        }

        return WorkLog::query()
            ->with(['user:id,name,email,business_name'])
            ->whereNotNull('flagged_at')
            ->whereNull('removed_at')
            ->latest('flagged_at')
            ->limit(20)
            ->get()
            ->reject(fn (WorkLog $log) => in_array(
                StaffCaseReferral::SUBJECT_JOB.':'.$log->getKey(),
                $skipKeys,
                true,
            ))
            ->map(function (WorkLog $log) {
                $artisan = $log->user?->displayBusinessName() ?? $log->user?->name ?? 'Unknown artisan';
                $reason = Str::limit((string) ($log->flag_reason ?: 'Flagged for moderation'), 72);

                return [
                    'key' => 'flagged:job:'.$log->uid,
                    'signature' => 'flagged:job:'.$log->uid.':'.($log->flagged_at?->timestamp ?? 0),
                    'urgency' => 105,
                    'sort_at' => $log->flagged_at?->timestamp ?? 0,
                    'title' => 'Flagged job log',
                    'subtitle' => trim($artisan.' · '.$reason),
                    'href' => route('admin.jobs.index', ['job' => $log->uid, 'tab' => 'flagged']),
                    'icon' => 'ti ti-flag',
                    'tone' => 'high',
                    'queue' => 'Moderation',
                    'group' => 'moderation',
                    'priority' => 'high',
                    'count' => 1,
                    'unread' => true,
                    'note' => $log->flag_reason,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $skipKeys
     * @return list<array<string, mixed>>
     */
    private function flaggedReviewItems(array $skipKeys): array
    {
        if (! Schema::hasTable('reviews') || ! Schema::hasColumn('reviews', 'flagged_at')) {
            return [];
        }

        return Review::query()
            ->with(['artisan:id,name,email,business_name'])
            ->whereNotNull('flagged_at')
            ->whereNull('removed_at')
            ->latest('flagged_at')
            ->limit(20)
            ->get()
            ->reject(fn (Review $review) => in_array(
                StaffCaseReferral::SUBJECT_REVIEW.':'.$review->getKey(),
                $skipKeys,
                true,
            ))
            ->map(function (Review $review) {
                $artisan = $review->artisan?->displayBusinessName() ?? $review->artisan?->name ?? 'Unknown artisan';
                $reason = Str::limit((string) ($review->flag_reason ?: 'Flagged for moderation'), 72);

                return [
                    'key' => 'flagged:review:'.$review->uid,
                    'signature' => 'flagged:review:'.$review->uid.':'.($review->flagged_at?->timestamp ?? 0),
                    'urgency' => 104,
                    'sort_at' => $review->flagged_at?->timestamp ?? 0,
                    'title' => 'Flagged review',
                    'subtitle' => trim($review->rating.'★ · '.$artisan.' · '.$reason),
                    'href' => route('admin.reviews.index', ['review' => $review->uid, 'tab' => 'flagged']),
                    'icon' => 'ti ti-star',
                    'tone' => 'high',
                    'queue' => 'Moderation',
                    'group' => 'moderation',
                    'priority' => 'high',
                    'count' => 1,
                    'unread' => true,
                    'note' => $review->flag_reason,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function moderationReferralItems(): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        return StaffCaseReferral::query()
            ->active()
            ->where('queue', StaffCaseReferral::QUEUE_MODERATION)
            ->with(['referredBy:id,name,email'])
            ->latest('referred_at')
            ->limit(25)
            ->get()
            ->map(function (StaffCaseReferral $referral) {
                $row = $this->referrals->present($referral);
                if ($row === null) {
                    return null;
                }

                return [
                    'key' => 'moderation-referral:'.$referral->id,
                    'signature' => 'moderation-referral:'.$referral->id.':'.($referral->referred_at?->timestamp ?? 0),
                    'referral_id' => $referral->id,
                    'urgency' => 103,
                    'sort_at' => $referral->referred_at?->timestamp ?? 0,
                    'title' => 'Referred: '.$row['title'],
                    'subtitle' => $row['subtitle'],
                    'href' => $row['href'],
                    'icon' => $row['icon'],
                    'tone' => 'medium',
                    'queue' => 'Moderation',
                    'group' => 'moderation',
                    'priority' => 'medium',
                    'count' => 1,
                    'unread' => true,
                    'note' => $referral->note,
                    'referrer' => $row['referrer'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function activeModerationSubjectKeys(): array
    {
        if (! Schema::hasTable('staff_case_referrals')) {
            return [];
        }

        return StaffCaseReferral::query()
            ->active()
            ->where('queue', StaffCaseReferral::QUEUE_MODERATION)
            ->get(['subject_type', 'subject_id'])
            ->map(fn (StaffCaseReferral $referral) => $referral->subject_type.':'.$referral->subject_id)
            ->values()
            ->all();
    }
}
