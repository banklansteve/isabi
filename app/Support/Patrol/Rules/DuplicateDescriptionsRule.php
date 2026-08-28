<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;
use App\Support\Patrol\PatrolText;

class DuplicateDescriptionsRule implements PatrolRule
{
    public function key(): string
    {
        return 'duplicate_descriptions';
    }

    public function evaluate(WorkLog $log): ?array
    {
        $config = config('patrol.rules.duplicate_descriptions');
        $sameMin = max(2, (int) ($config['same_artisan_min'] ?? 3));
        $sameDays = max(1, (int) ($config['same_artisan_days'] ?? 30));
        $crossMin = max(2, (int) ($config['cross_artisan_min'] ?? 3));
        $similarity = max(80, (int) ($config['similarity_percent'] ?? 92));
        $needle = PatrolText::normalizeDescription($log->description);

        if ($needle === '') {
            return null;
        }

        $sameArtisan = WorkLog::query()
            ->where('user_id', $log->user_id)
            ->where('created_at', '>=', now()->subDays($sameDays))
            ->get(['id', 'description', 'user_id']);

        $sameMatches = $sameArtisan
            ->filter(fn (WorkLog $item) => PatrolText::similarEnough(
                $needle,
                PatrolText::normalizeDescription($item->description),
                $similarity,
            ));

        if ($sameMatches->count() >= $sameMin) {
            return [
                'trigger' => sprintf(
                    'same artisan reused a near-identical description on %d jobs in %d days',
                    $sameMatches->count(),
                    $sameDays,
                ),
                'scope' => 'same_artisan',
                'match_count' => $sameMatches->count(),
                'sample' => $needle,
                'work_log_ids' => $sameMatches->pluck('id')->all(),
            ];
        }

        $others = WorkLog::query()
            ->where('user_id', '!=', $log->user_id)
            ->where('created_at', '>=', now()->subDays($sameDays))
            ->latest('id')
            ->limit(400)
            ->get(['id', 'description', 'user_id']);

        $crossMatches = $others
            ->filter(fn (WorkLog $item) => PatrolText::similarEnough(
                $needle,
                PatrolText::normalizeDescription($item->description),
                $similarity,
            ));

        $artisanCount = $crossMatches->pluck('user_id')->unique()->count() + 1;

        if ($artisanCount >= $crossMin) {
            return [
                'trigger' => sprintf(
                    'templated description used across %d artisans',
                    $artisanCount,
                ),
                'scope' => 'cross_artisan',
                'artisan_count' => $artisanCount,
                'sample' => $needle,
                'work_log_ids' => $crossMatches->pluck('id')->prepend($log->id)->unique()->values()->all(),
            ];
        }

        return null;
    }
}
