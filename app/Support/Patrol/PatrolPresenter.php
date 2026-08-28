<?php

namespace App\Support\Patrol;

use App\Models\PatrolCase;
use App\Models\PatrolCaseAction;
use App\Models\PatrolCaseNote;
use App\Models\PatrolCaseRule;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Str;

class PatrolPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function caseRow(PatrolCase $case): array
    {
        $log = $case->workLog;
        $review = $case->review;
        $artisan = $case->artisan;
        $isReview = $case->isReview();

        return [
            'id' => $case->id,
            'kind' => $isReview ? PatrolCase::KIND_REVIEW : PatrolCase::KIND_JOB,
            'work_log_id' => $case->work_log_id,
            'review_id' => $case->review_id,
            'artisan' => $artisan ? [
                'id' => $artisan->id,
                'name' => $artisan->displayBusinessName(),
                'email' => $artisan->email,
                'trade' => $artisan->trade,
                'initials' => self::initials($artisan),
            ] : null,
            'job_summary' => Str::limit((string) $log?->description, 90),
            'review_excerpt' => $isReview
                ? (Str::limit(trim((string) $review?->comment), 90) ?: 'No comment')
                : null,
            'rating' => $isReview ? (float) $review?->rating : null,
            'worked_on_label' => $log?->worked_on?->format('j M Y'),
            'rules' => $case->rules
                ->map(fn (PatrolCaseRule $rule) => [
                    'key' => $rule->rule_key,
                    'label' => $rule->label(),
                    'severity' => $rule->severity,
                ])
                ->values(),
            'severity' => $case->severity,
            'severity_label' => $case->severityLabel(),
            'status' => $case->status,
            'status_label' => $case->statusLabel(),
            'visibility' => $isReview ? self::reviewVisibility($review) : self::visibility($log),
            'flagged_at' => $case->flagged_at?->toIso8601String(),
            'flagged_label' => $case->flagged_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            'pending_approval' => $case->status === PatrolCase::STATUS_PENDING_APPROVAL,
            'recommended_outcome' => $case->recommended_outcome,
            'recommended_outcome_label' => $case->recommended_outcome
                ? ($case->recommendationOptions()[$case->recommended_outcome] ?? Str::headline($case->recommended_outcome))
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function caseDetail(PatrolCase $case, User $actor): array
    {
        $log = $case->workLog;
        $artisan = $case->artisan;

        return [
            ...self::caseRow($case),
            'job' => $log ? self::jobCard($log) : null,
            'review' => $case->isReview() && $case->review ? self::reviewCard($case->review) : null,
            'matched_rules' => $case->rules
                ->sortByDesc(fn (PatrolCaseRule $rule) => PatrolSeverity::rank($rule->severity))
                ->map(fn (PatrolCaseRule $rule) => [
                    'key' => $rule->rule_key,
                    'label' => $rule->label(),
                    'severity' => $rule->severity,
                    'severity_label' => config('patrol.severities.'.$rule->severity, $rule->severity),
                    'trigger' => $rule->evidence['trigger'] ?? $rule->label(),
                    'evidence' => $rule->evidence ?? [],
                    'detected_label' => $rule->detected_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                ])
                ->values(),
            'context' => self::context($case),
            'notes' => $case->notes
                ->sortBy('created_at')
                ->map(fn (PatrolCaseNote $note) => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'author_name' => $note->author?->name ?: 'Staff',
                    'created_label' => $note->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                ])
                ->values(),
            'audit' => $case->actions
                ->sortBy('created_at')
                ->map(fn (PatrolCaseAction $action) => [
                    'id' => $action->id,
                    'action' => $action->action,
                    'action_label' => self::actionLabel($action->action),
                    'reason' => $action->reason,
                    'actor_name' => $action->actor?->name ?: 'System',
                    'created_label' => $action->created_at?->timezone(config('app.display_timezone'))->format('j M Y · H:i'),
                ])
                ->values(),
            'user_url' => $artisan && $actor->canDo('admin.users.view')
                ? route('admin.users.show', $artisan)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        return [
            'statuses' => self::optionList(config('patrol.statuses', [])),
            'severities' => self::optionList(config('patrol.severities', [])),
            'rules' => self::ruleOptions('patrol.rules'),
            'review_rules' => self::ruleOptions('patrol.review_rules'),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private static function ruleOptions(string $key): array
    {
        return collect(config($key, []))
            ->map(fn (array $rule, string $ruleKey) => [
                'value' => $ruleKey,
                'label' => $rule['label'] ?? $ruleKey,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function jobCard(WorkLog $log): array
    {
        $media = $log->relationLoaded('media') ? $log->media : $log->media()->get();

        return [
            'uid' => $log->uid,
            'description' => $log->description,
            'worked_on_label' => $log->worked_on?->format('j M Y'),
            'created_label' => $log->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            'client_name' => $log->client_name,
            'has_photo' => $media->isNotEmpty(),
            'photo_url' => $media->first()?->thumbUrl(600),
            'public_url' => $log->publicUrl(),
            'hidden' => $log->hidden_at !== null,
            'removed' => $log->removed_at !== null,
            'hidden_reason' => $log->hidden_reason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private static function reviewCard(Review $review): array
    {
        return [
            'uid' => $review->uid,
            'rating' => (float) $review->rating,
            'would_recommend' => (bool) $review->would_recommend,
            'comment' => $review->comment,
            'client_display_name' => $review->client_display_name,
            'referred_by' => $review->referred_by,
            'has_photo' => filled($review->photo_url ?: $review->photo_path),
            'photo_url' => $review->photoThumbUrl(700),
            'submitted_label' => $review->submitted_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            'hidden' => $review->hidden_at !== null,
            'removed' => $review->removed_at !== null,
            'hidden_reason' => $review->hidden_reason,
        ];
    }

    private static function context(PatrolCase $case): array
    {
        $artisan = $case->artisan;
        $logs = $artisan
            ? $artisan->workLogs()->orderByDesc('id')->get(['id', 'created_at', 'worked_on'])
            : collect();
        $reviews = $artisan
            ? $artisan->reviews()->orderByDesc('id')->get(['id', 'created_at', 'submitted_at'])
            : collect();

        $prior = PatrolCase::query()
            ->where('user_id', $case->user_id)
            ->where('id', '!=', $case->id)
            ->count();

        $openOthers = PatrolCase::query()
            ->open()
            ->where('user_id', $case->user_id)
            ->where('id', '!=', $case->id)
            ->with(['workLog:id,uid,description', 'review:id,comment,rating', 'rules'])
            ->latest('flagged_at')
            ->limit(8)
            ->get()
            ->map(fn (PatrolCase $item) => [
                'id' => $item->id,
                'kind' => $item->isReview() ? PatrolCase::KIND_REVIEW : PatrolCase::KIND_JOB,
                'summary' => $item->isReview()
                    ? (Str::limit(trim((string) $item->review?->comment), 64) ?: 'Review')
                    : Str::limit((string) $item->workLog?->description, 64),
                'severity' => $item->severity,
                'status_label' => $item->statusLabel(),
            ])
            ->values();

        return [
            'account_age_days' => $artisan?->created_at
                ? max(0, (int) $artisan->created_at->diffInDays(now()))
                : null,
            'job_count' => $logs->count(),
            'jobs_last_7_days' => $logs->where('created_at', '>=', now()->subDays(7))->count(),
            'jobs_last_30_days' => $logs->where('created_at', '>=', now()->subDays(30))->count(),
            'review_count' => $reviews->count(),
            'reviews_last_7_days' => $reviews->where('created_at', '>=', now()->subDays(7))->count(),
            'prior_cases' => $prior,
            'open_cases' => $openOthers,
        ];
    }

    /**
     * @param  array<string, string>  $items
     * @return list<array{value: string, label: string}>
     */
    private static function optionList(array $items): array
    {
        return collect($items)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private static function visibility(?WorkLog $log): string
    {
        if (! $log) {
            return 'hidden';
        }

        if ($log->removed_at) {
            return 'removed';
        }

        return $log->hidden_at ? 'hidden' : 'visible';
    }

    private static function reviewVisibility(?Review $review): string
    {
        if (! $review) {
            return 'hidden';
        }

        if ($review->removed_at) {
            return 'removed';
        }

        return $review->hidden_at ? 'hidden' : 'visible';
    }

    private static function actionLabel(string $action): string
    {
        return match ($action) {
            'detected' => 'Flagged by detector',
            'rules_appended' => 'Additional rules matched',
            'rule_detached' => 'Stale rule detached',
            'note_added' => 'Note added',
            'status_changed' => 'Status changed',
            'recommended_dismiss' => 'Recommended dismiss',
            'recommended_remove' => 'Recommended removal',
            'recommended_hide' => 'Recommended hide',
            'recommended_suspend' => 'Recommended suspension',
            'recommendation_rejected' => 'Recommendation rejected',
            'dismissed' => 'Dismissed',
            'removed' => 'Entry removed',
            'hidden' => 'Review hidden',
            'handoff_suspend' => 'Handed off to Users',
            default => Str::headline($action),
        };
    }

    private static function initials(User $user): string
    {
        $name = $user->displayBusinessName();
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr($name !== '' ? $name : 'I', 0, 1));
    }
}
