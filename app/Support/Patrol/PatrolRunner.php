<?php

namespace App\Support\Patrol;

use App\Models\PatrolCase;
use App\Models\PatrolCaseAction;
use App\Models\PatrolCaseRule;
use App\Models\WorkLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PatrolRunner
{
    /**
     * @return list<PatrolCase>
     */
    public function scan(?int $workLogId = null, int $limit = 2000): array
    {
        $query = WorkLog::query()
            ->with(['media', 'user'])
            ->latest('id');

        if ($workLogId) {
            $query->whereKey($workLogId);
        } else {
            $query->limit($limit);
        }

        $created = [];

        foreach ($query->cursor() as $log) {
            $case = $this->scanWorkLog($log);
            if ($case) {
                $created[] = $case;
            }
        }

        return $created;
    }

    public function scanWorkLog(WorkLog $log): ?PatrolCase
    {
        $matches = $this->evaluate($log);
        if ($matches === []) {
            return null;
        }

        return DB::transaction(fn () => $this->persist($log->fresh(['media', 'user']) ?? $log, $matches));
    }

    /**
     * @return list<array{key: string, severity: string, evidence: array<string, mixed>}>
     */
    public function evaluate(WorkLog $log): array
    {
        $matches = [];

        foreach (config('patrol.rules', []) as $key => $config) {
            if (! ($config['enabled'] ?? true)) {
                continue;
            }

            $class = $config['class'] ?? null;
            if (! is_string($class) || ! is_a($class, PatrolRule::class, true)) {
                throw new InvalidArgumentException("Patrol rule [{$key}] is missing a valid class.");
            }

            /** @var PatrolRule $rule */
            $rule = app($class);
            $evidence = $rule->evaluate($log);
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
    private function persist(WorkLog $log, array $matches): PatrolCase
    {
        $severity = PatrolSeverity::max(array_column($matches, 'severity'));
        $wasPublic = $log->isPubliclyVisible();

        $case = PatrolCase::query()->firstOrNew(['work_log_id' => $log->id]);
        $creating = ! $case->exists;
        $before = $creating ? null : $case->only(['status', 'severity', 'auto_hidden_at']);

        if (! $creating && $case->isResolved()) {
            return $case;
        }

        if ($creating) {
            $case->forceFill([
                'kind' => PatrolCase::KIND_JOB,
                'user_id' => $log->user_id,
                'status' => PatrolCase::STATUS_NEW,
                'severity' => $severity,
                'visibility_was_public' => $wasPublic,
                'flagged_at' => now(),
            ]);
        } else {
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

        $this->maybeAutoHide($case->fresh(['workLog']) ?? $case);

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

        return $case->fresh(['rules', 'workLog']) ?? $case;
    }

    private function maybeAutoHide(PatrolCase $case): void
    {
        $log = $case->workLog;
        if (! $log || ! PatrolSeverity::isHigh($case->severity)) {
            return;
        }

        if ($log->removed_at || $case->auto_hidden_at) {
            return;
        }

        $beforeHidden = $log->hidden_at;

        if (! $log->hidden_at) {
            $log->forceFill([
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
