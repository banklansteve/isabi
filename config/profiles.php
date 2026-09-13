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
        'faq', 'help', 'home', 'internal', 'kraftrack', 'login', 'logout', 'my-page',
        'page', 'pricing', 'privacy', 'profile', 'p', 'r', 'register', 'referrals',
        'reset-password', 'forgot-password', 'support', 'terms', 'verify-email',
        'welcome', 'work-log', 'www', 'null', 'undefined', 'settings', 'billing',
        'account', 'user', 'users', 'artisan', 'artisans', 'review', 'reviews',
        'sitemap', 'jobs', 'directory', 'explore', 'q', 'quotes',
    ],

    /** Max times an artisan may change their public slug. */
    'max_slug_changes' => 3,

    /** Review invite link lifetime (days). */
    'review_token_days' => 30,

    /** Client quote response link fallback lifetime (days) when valid_until is unset. */
    'quote_token_days' => 30,

    /** Days before valid_until to send a one-shot looming-expiry nudge. */
    'quote_expiry_nudge_days' => 3,
];
