<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;

class NoPhotoPatternRule implements PatrolRule
{
    public function key(): string
    {
        return 'no_photo_pattern';
    }

    public function evaluate(WorkLog $log): ?array
    {
        $config = config('patrol.rules.no_photo_pattern');
        $limit = max(3, (int) ($config['recent_limit'] ?? 12));
        $minRecent = max(3, (int) ($config['min_recent'] ?? 6));
        $percentThreshold = max(1, (int) ($config['no_photo_percent'] ?? 70));

        $recent = WorkLog::query()
            ->where('user_id', $log->user_id)
            ->withCount('media')
            ->latest('id')
            ->limit($limit)
            ->get(['id']);

        if ($recent->count() < $minRecent) {
            return null;
        }

        $withoutPhoto = $recent->where('media_count', 0)->count();
        $percent = (int) round(($withoutPhoto / $recent->count()) * 100);

        if ($percent < $percentThreshold) {
            return null;
        }

        $thisLogHasPhoto = $log->relationLoaded('media')
            ? $log->media->isNotEmpty()
            : $log->media()->exists();

        if ($thisLogHasPhoto && $percent < 90) {
            return null;
        }

        return [
            'trigger' => sprintf(
                '%d of the last %d jobs have no photo (%d%%, threshold %d%%)',
                $withoutPhoto,
                $recent->count(),
                $percent,
                $percentThreshold,
            ),
            'without_photo' => $withoutPhoto,
            'recent_count' => $recent->count(),
            'percent' => $percent,
            'threshold_percent' => $percentThreshold,
            'this_log_has_photo' => $thisLogHasPhoto,
        ];
    }
}
