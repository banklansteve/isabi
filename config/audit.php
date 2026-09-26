<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit retention tiers (metadata only — no purge jobs in this pass)
    |--------------------------------------------------------------------------
    |
    | standard_public: hot 90 days, archive after, total ~2 years
    | financial:       hot 90 days, archive after, retain ≥6 years (confirm with accountant)
    | staff:           hot 12 months, archive after, retain indefinitely
    |
    */

    'retention' => [
        'tiers' => [
            'standard_public' => [
                'hot_days' => 90,
                'retain_days' => 730,
                'purge' => false,
            ],
            'financial' => [
                'hot_days' => 90,
                'retain_days' => 2190,
                'purge' => false,
            ],
            'staff' => [
                'hot_days' => 365,
                'retain_days' => null,
                'purge' => false,
            ],
        ],

        'financial_actions' => [
            'tokens.purchased',
            'credits.adjusted',
            'referral.rewarded',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product analytics (analytics_events → daily summaries)
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'raw_retention_days' => 45,
        'presence_write_seconds' => 30,
    ],

];
