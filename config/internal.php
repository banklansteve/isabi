<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Internal documentation access
    |--------------------------------------------------------------------------
    |
    | Leave allowed_emails empty to allow any authenticated user (useful while
    | you are the only account). Later, set INTERNAL_DOCS_EMAILS to a comma-
    | separated allowlist, e.g. "you@example.com".
    |
    */

    'docs_enabled' => (bool) env('INTERNAL_DOCS_ENABLED', true),

    'allowed_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('INTERNAL_DOCS_EMAILS', ''))
    ))),

];
