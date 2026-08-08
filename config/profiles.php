<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reserved profile slugs (cannot be claimed by artisans)
    |--------------------------------------------------------------------------
    */
    'reserved' => [
        'admin', 'api', 'app', 'about', 'acceptable-use', 'auth', 'careers',
        'contact', 'cookies', 'cookie-consent', 'credits', 'dashboard', 'docs',
        'faq', 'help', 'home', 'internal', 'isabi', 'login', 'logout', 'my-page',
        'page', 'pricing', 'privacy', 'profile', 'p', 'r', 'register', 'referrals',
        'reset-password', 'forgot-password', 'support', 'terms', 'verify-email',
        'welcome', 'work-log', 'www', 'null', 'undefined', 'settings', 'billing',
        'account', 'user', 'users', 'artisan', 'artisans', 'review', 'reviews',
    ],

    /** Max times an artisan may change their public slug. */
    'max_slug_changes' => 3,

    /** Review invite link lifetime (days). */
    'review_token_days' => 30,
];
