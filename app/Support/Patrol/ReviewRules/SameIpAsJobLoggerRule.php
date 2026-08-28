<?php

namespace App\Support\Patrol\ReviewRules;

use App\Models\Review;
use App\Support\Patrol\PatrolIp;
use App\Support\Patrol\ReviewRule;

class SameIpAsJobLoggerRule implements ReviewRule
{
    public function key(): string
    {
        return 'same_ip_as_job_logger';
    }

    public function evaluate(Review $review): ?array
    {
        $reviewIp = $review->submitted_ip;
        if (PatrolIp::isIgnored($reviewIp)) {
            return null;
        }

        $log = $review->relationLoaded('workLog') ? $review->workLog : $review->workLog()->first();
        $artisan = $review->relationLoaded('artisan') ? $review->artisan : $review->artisan()->first();

        $jobIp = $log?->created_ip;
        $loginIp = $artisan?->last_login_ip;
        $matchedOn = null;

        if (PatrolIp::equals($reviewIp, $jobIp)) {
            $matchedOn = 'job';
        } elseif (PatrolIp::equals($reviewIp, $loginIp)) {
            $matchedOn = 'login';
        }

        if ($matchedOn === null) {
            return null;
        }

        $mask = PatrolIp::mask($reviewIp);

        return [
            'trigger' => $matchedOn === 'job'
                ? sprintf('Review IP %s matches job logger IP', $mask)
                : sprintf('Review IP %s matches the artisan’s last login IP', $mask),
            'source' => $matchedOn,
            'review_ip_mask' => $mask,
        ];
    }
}
