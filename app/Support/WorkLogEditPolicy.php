<?php

namespace App\Support;

use App\Models\WorkLog;
use Carbon\CarbonInterface;

/**
 * Trust-preserving edit rules for work logs.
 *
 * - Description: editable for a short window after logging, until a review is requested.
 * - Date: editable only briefly (typo / wrong day), until a review is requested.
 * - Media & optional metadata: stay editable so artisans can polish proof without rewriting history.
 */
class WorkLogEditPolicy
{
    /** Days after create during which the core description may still be corrected. */
    public const DESCRIPTION_EDIT_DAYS = 7;

    /** Hours after create during which the job date may still be corrected. */
    public const DATE_EDIT_HOURS = 48;

    public static function hasReviewRequested(WorkLog $log): bool
    {
        return $log->review_requested_at !== null;
    }

    public static function canEditDescription(WorkLog $log, ?CarbonInterface $now = null): bool
    {
        if (self::hasReviewRequested($log)) {
            return false;
        }

        $now ??= now();

        return $log->created_at !== null
            && $log->created_at->gte($now->copy()->subDays(self::DESCRIPTION_EDIT_DAYS));
    }

    public static function canEditDate(WorkLog $log, ?CarbonInterface $now = null): bool
    {
        if (self::hasReviewRequested($log)) {
            return false;
        }

        $now ??= now();

        return $log->created_at !== null
            && $log->created_at->gte($now->copy()->subHours(self::DATE_EDIT_HOURS));
    }

    public static function canEditMedia(WorkLog $log): bool
    {
        return true;
    }

    public static function canEditOptional(WorkLog $log): bool
    {
        return true;
    }

    /**
     * Whether the artisan may open the editor for this job
     * (still inside the time-bound edit window for core fields).
     */
    public static function canOpenEditor(WorkLog $log, ?CarbonInterface $now = null): bool
    {
        return self::canEditDescription($log, $now) || self::canEditDate($log, $now);
    }

    /**
     * @return array{
     *     can_edit_description: bool,
     *     can_edit_date: bool,
     *     can_edit_media: bool,
     *     can_edit_optional: bool,
     *     can_edit: bool,
     *     review_requested: bool,
     *     description_edit_days: int,
     *     date_edit_hours: int,
     * }
     */
    public static function flags(WorkLog $log): array
    {
        return [
            'can_edit_description' => self::canEditDescription($log),
            'can_edit_date' => self::canEditDate($log),
            'can_edit_media' => self::canEditMedia($log),
            'can_edit_optional' => self::canEditOptional($log),
            'can_edit' => self::canOpenEditor($log),
            'review_requested' => self::hasReviewRequested($log),
            'description_edit_days' => self::DESCRIPTION_EDIT_DAYS,
            'date_edit_hours' => self::DATE_EDIT_HOURS,
        ];
    }
}
