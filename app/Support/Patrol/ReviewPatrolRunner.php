<?php

namespace App\Support\Patrol;

use App\Models\PatrolCase;
use App\Models\PatrolCaseAction;
use App\Models\PatrolCaseRule;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewPatrolRunner
{
    /**
     * @return list<PatrolCase>
     */
    public function scan(?int $reviewId = null, int $limit = 2000): array
    {
        $query = Review::query()
            ->with(['workLog', 'artisan'])
            ->latest('id');

        if ($reviewId) {
            $query->whereKey($reviewId);
        } else {
            $query->limit($limit);
        }

        $created = [];

        foreach ($query->cursor() as $review) {
            $case = $this->scanReview($review);
            if ($case) {
                $created[] = $case;
            }
        }

        return $created;
    }

    public function scanReview(Review $review): ?PatrolCase
    {
        $matches = $this->evaluate($review);
        if ($matches === []) {
            return null;
        }

        return DB::transaction(fn () => $this->persist(
            $review->fresh(['workLog', 'artisan']) ?? $review,
            $matches,
        ));
    }

    /**
     * @return list<array{key: string, severity: string, evidence: array<string, mixed>}>
     */
    public function evaluate(Review $review): array
    {
        $matches = [];

        foreach (config('patrol.review_rules', []) as $key => $config) {
            if (! ($config['enabled'] ?? true)) {
                continue;
            }

            $class = $config['class'] ?? null;
            if (! is_string($class) || ! is_a($class, ReviewRule::class, true)) {
                throw new InvalidArgumentException("Review patrol rule [{$key}] is missing a valid class.");
            }

            /** @var ReviewRule $rule */
            $rule = app($class);
            $evidence = $rule->evaluate($review);
            if (! $evidence) {
                continue;
            }

            $matches[] = [
                'key' => $key,
                'severity' => (string) ($config['severity'] ?? 'low'),
                'evidence' => $evidence,
            ];
        }

        return $matches;
    }

    /**
     * @param  list<array{key: string, severity: string, evidence: array<string, mixed>}>  $matches
     */
    private function persist(Review $review, array $matches): PatrolCase
    {
        $severity = PatrolSeverity::max(array_column($matches, 'severity'));
        $wasPublic = $review->isPubliclyVisible();

        $case = PatrolCase::query()->firstOrNew(['review_id' => $review->id]);
        $creating = ! $case->exists;
        $before = $creating ? null : $case->only(['status', 'severity', 'auto_hidden_at']);

        if (! $creating && $case->isResolved()) {
            return $case;
        }

        if ($creating) {
            $case->forceFill([
                'kind' => PatrolCase::KIND_REVIEW,
                'user_id' => $review->user_id,
                'status' => PatrolCase::STATUS_NEW,
                'severity' => $severity,
                'visibility_was_public' => $wasPublic,
                'flagged_at' => now(),
            ]);
        } else {
            $case->kind = PatrolCase::KIND_REVIEW;
            $case->severity = PatrolSeverity::max([$case->severity, $severity]);
        }

        $case->save();

        foreach ($matches as $match) {
            PatrolCaseRule::query()->updateOrCreate(
                [
                    'patrol_case_id' => $case->id,
                    'rule_key' => $match['key'],
                ],
                [
                    'severity' => $match['severity'],
                    'evidence' => $match['evidence'],
                    'detected_at' => now(),
                ],
            );
        }

        $case->severity = PatrolSeverity::max($case->rules()->pluck('severity'));
        $case->save();

        $this->maybeAutoHide($case->fresh(['review']) ?? $case);

        PatrolCaseAction::query()->create([
            'patrol_case_id' => $case->id,
            'actor_id' => null,
            'action' => $creating ? 'detected' : 'rules_appended',
            'reason' => $creating ? 'Detector opened this case.' : 'Detector attached additional matched rules.',
            'before' => $before,
            'after' => [
                'status' => $case->status,
                'severity' => $case->severity,
                'rules' => array_column($matches, 'key'),
            ],
            'created_at' => now(),
        ]);

        return $case->fresh(['rules', 'review', 'workLog']) ?? $case;
    }

    private function maybeAutoHide(PatrolCase $case): void
    {
        $review = $case->review;
        if (! $review || ! PatrolSeverity::isHigh($case->severity)) {
            return;
        }

        if ($review->removed_at || $case->auto_hidden_at) {
            return;
        }

        $beforeHidden = $review->hidden_at;

        if (! $review->hidden_at) {
            $review->forceFill([
                'hidden_at' => now(),
                'hidden_reason' => 'patrol_auto',
            ])->save();
        }

        $case->forceFill([
            'auto_hidden_at' => now(),
            'visibility_was_public' => $beforeHidden === null,
        ])->save();
    }
}
