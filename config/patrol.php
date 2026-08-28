<?php

use App\Support\Patrol\ReviewRules\BurstReviewsRule;
use App\Support\Patrol\ReviewRules\DuplicateReviewTextRule;
use App\Support\Patrol\ReviewRules\GenericMinimalPraiseRule;
use App\Support\Patrol\ReviewRules\ReviewRequestTooFastRule;
use App\Support\Patrol\ReviewRules\SameClientContactBurstRule;
use App\Support\Patrol\ReviewRules\SameIpAsJobLoggerRule;
use App\Support\Patrol\ReviewRules\SelfVouchOrCircularRule;
use App\Support\Patrol\Rules\BackdatingRule;
use App\Support\Patrol\Rules\DuplicateDescriptionsRule;
use App\Support\Patrol\Rules\GenericMinimalRule;
use App\Support\Patrol\Rules\NoPhotoPatternRule;
use App\Support\Patrol\Rules\RapidLoggingRule;
use App\Support\Patrol\Rules\ReusedClientContactRule;
use App\Support\Patrol\Rules\ReviewRequestChainingRule;

return [

    /*
    |--------------------------------------------------------------------------
    | Case statuses and severities
    |--------------------------------------------------------------------------
    |
    | Case severity is the maximum of attached rule severities
    | (high > medium > low). Rules only surface cases — they never
    | auto-remove, auto-suspend, or auto-resolve.
    |
    */

    'statuses' => [
        'new' => 'New',
        'in_review' => 'In review',
        'pending_approval' => 'Pending Super Admin approval',
        'resolved_dismissed' => 'Resolved — dismissed',
        'resolved_actioned' => 'Resolved — actioned',
    ],

    'open_statuses' => [
        'new',
        'in_review',
        'pending_approval',
    ],

    'severities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],

    'severity_rank' => [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
    ],

    'recommendations' => [
        'dismiss' => 'Dismiss',
        'remove' => 'Remove entry',
        'suspend' => 'Warn or suspend artisan',
    ],

    'review_recommendations' => [
        'dismiss' => 'Dismiss',
        'hide' => 'Hide review',
        'remove' => 'Remove review',
        'suspend' => 'Warn or suspend artisan',
    ],

    'hidden_reasons' => [
        'patrol_auto' => 'Automatically hidden pending patrol review',
        'patrol_hide' => 'Hidden after patrol review',
        'patrol_remove' => 'Soft-removed after patrol review',
    ],

    /*
    |--------------------------------------------------------------------------
    | Detection rules
    |--------------------------------------------------------------------------
    |
    | Thresholds and default severities live here only. A new rule is a
    | class implementing PatrolRule plus an entry below.
    |
    */

    'rules' => [
        'rapid_logging' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Rapid logging',
            'class' => RapidLoggingRule::class,
            // Submit time (created_at), not the job's performed date.
            'min_logs' => 5,
            'window_minutes' => 15,
            // Only flag clusters whose newest created_at is recent. A first
            // historical scan of seeders / imports / “created prior” jobs
            // must not open high-severity cases for weeks-old bulk inserts.
            'max_age_hours' => 48,
            // Live abuse has slightly different seconds. Exact-same-second
            // batches (seeders, factories, imports) are excluded because
            // distinct created_at timestamps fall below this floor.
            'min_distinct_seconds' => 3,
        ],
        'backdating' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Backdating',
            'class' => BackdatingRule::class,
            'allowed_days' => 2,
            'repeat_min' => 3,
            'repeat_window_days' => 30,
        ],
        'no_photo_pattern' => [
            'enabled' => true,
            'severity' => 'medium',
            'label' => 'No-photo pattern',
            'class' => NoPhotoPatternRule::class,
            'recent_limit' => 12,
            'min_recent' => 6,
            'no_photo_percent' => 70,
        ],
        'duplicate_descriptions' => [
            'enabled' => true,
            'severity' => 'medium',
            'label' => 'Duplicate descriptions',
            'class' => DuplicateDescriptionsRule::class,
            'same_artisan_min' => 3,
            'same_artisan_days' => 30,
            'cross_artisan_min' => 3,
            'similarity_percent' => 92,
        ],
        'generic_minimal' => [
            'enabled' => true,
            'severity' => 'low',
            'label' => 'Generic / minimal description',
            'class' => GenericMinimalRule::class,
            'min_chars' => 12,
            'phrases' => [
                'job done',
                'work done',
                'completed',
                'done',
                'ok',
                'okay',
                'good',
                'finished',
                'service',
                'repair',
                'fixed',
            ],
        ],
        'review_request_chaining' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Review-request chaining',
            'class' => ReviewRequestChainingRule::class,
            'max_gap_minutes' => 5,
            'min_chain' => 4,
            'window_hours' => 6,
        ],
        'reused_client_contact' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Reused client contact',
            'class' => ReusedClientContactRule::class,
            'min_jobs' => 5,
            'window_days' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Review detection rules
    |--------------------------------------------------------------------------
    |
    | Same runner contract as job-log rules. Thresholds live here only.
    | Conservative: an isolated honest 5-star from a different IP days
    | later must not open a case.
    |
    */

    'review_rules' => [
        'same_ip_as_job_logger' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Same IP as job logger',
            'class' => SameIpAsJobLoggerRule::class,
        ],
        'review_request_too_fast' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Review request too fast',
            'class' => ReviewRequestTooFastRule::class,
            // Under ~3 minutes is suspicious; an hour is not.
            'max_seconds' => 180,
            // A single sub-minute submit is enough. 2–3 minutes needs a repeat.
            'solo_seconds' => 60,
            'repeat_min' => 2,
            'repeat_window_hours' => 24,
        ],
        'duplicate_or_near_duplicate_text' => [
            'enabled' => true,
            'severity' => 'medium',
            'label' => 'Duplicate or near-duplicate text',
            'class' => DuplicateReviewTextRule::class,
            'min_chars' => 36,
            'similarity_percent' => 90,
            'same_artisan_min' => 2,
            'cross_artisan_min' => 3,
            'lookback_days' => 90,
        ],
        'generic_minimal_praise' => [
            'enabled' => true,
            'severity' => 'low',
            'label' => 'Generic / minimal praise',
            'class' => GenericMinimalPraiseRule::class,
            'max_chars' => 18,
            'min_stars' => 5,
            'require_no_photo' => true,
            'min_volume' => 3,
            'volume_days' => 14,
        ],
        'same_client_contact_burst' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Same client contact burst',
            'class' => SameClientContactBurstRule::class,
            'min_reviews' => 4,
            'window_days' => 5,
        ],
        'self_vouch_or_circular' => [
            'enabled' => true,
            'severity' => 'medium',
            'label' => 'Self-vouch or circular',
            'class' => SelfVouchOrCircularRule::class,
            'min_name_chars' => 4,
        ],
        'burst_reviews' => [
            'enabled' => true,
            'severity' => 'high',
            'label' => 'Burst reviews',
            'class' => BurstReviewsRule::class,
            'min_reviews' => 4,
            'window_minutes' => 20,
            'max_age_hours' => 48,
            'min_distinct_seconds' => 3,
        ],
    ],

];
