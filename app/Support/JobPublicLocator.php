<?php

namespace App\Support;

use App\Models\JobSlugRedirect;
use App\Models\WorkLog;

/**
 * Resolve a public job path to a work log.
 *
 * Canonical URL: /p/{artisan}/{seo-context}/{reference}
 * Legacy single-segment paths (hyphenated or bare reference) 301 via redirects.
 */
class JobPublicLocator
{
    public static function findForArtisan(int $userId, string $contextOrSegment, ?string $reference = null): ?WorkLog
    {
        $contextOrSegment = strtolower(trim($contextOrSegment, "/ \t"));

        if ($contextOrSegment === '') {
            return null;
        }

        // Canonical two-segment lookup: context + opaque reference.
        if ($reference !== null && $reference !== '') {
            $reference = strtolower(trim($reference));

            if (! JobReference::isValid($reference)) {
                return null;
            }

            $log = WorkLog::query()
                ->where('user_id', $userId)
                ->where('reference', $reference)
                ->first();

            if ($log) {
                return $log;
            }

            // Context/ref path may be an old remembered relative path.
            $combined = $contextOrSegment.'/'.$reference;
            $redirect = JobSlugRedirect::query()
                ->where('from_path', $combined)
                ->where('user_id', $userId)
                ->with('workLog')
                ->first();

            if ($redirect?->workLog && (int) $redirect->workLog->user_id === $userId) {
                return $redirect->workLog;
            }

            return null;
        }

        // Legacy single segment: reference only, hyphenated compose, or remembered path.
        $segment = $contextOrSegment;
        $extracted = JobSlug::extractReference($segment);

        if ($extracted) {
            $log = WorkLog::query()
                ->where('user_id', $userId)
                ->where('reference', $extracted)
                ->first();

            if ($log) {
                return $log;
            }
        }

        $redirect = JobSlugRedirect::query()
            ->where('from_path', $segment)
            ->where('user_id', $userId)
            ->with('workLog')
            ->first();

        if ($redirect?->workLog && (int) $redirect->workLog->user_id === $userId) {
            return $redirect->workLog;
        }

        // Legacy: slug stored without the appended reference.
        $bySlug = WorkLog::query()
            ->where('user_id', $userId)
            ->where('slug', $segment)
            ->first();

        if ($bySlug) {
            return $bySlug;
        }

        return WorkLog::query()
            ->where('user_id', $userId)
            ->where('uid', $segment)
            ->first();
    }
}
