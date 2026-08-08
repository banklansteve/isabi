<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Internal pricing documentation
    |--------------------------------------------------------------------------
    |
    | Tunable numbers for Isabi's commercial model. Narrative docs live on the
    | internal pricing page and should read these values so product copy and
    | admin settings stay aligned as you iterate.
    |
    */

    'currency' => 'NGN',
    'currency_symbol' => '₦',

    'free' => [
        'monthly_review_links' => 5,
        'review_link_reset' => 'calendar_month', // calendar_month | rolling_30_days
        'review_link_rollover' => false,
        'unlimited_work_log' => true,
        'unlimited_page_sharing' => true,
    ],

    'credits' => [
        'name' => 'Isabi Credits',
        'expire' => false,
        'actions' => [
            'review_link' => 1,
            'qr_download' => 1,
            'vanity_slug' => 5,
            // 'reach_boost' => null, // planned
        ],
        'packs' => [
            [
                'key' => 'starter',
                'name' => 'Starter',
                'credits' => 10,
                'price' => 3000,
            ],
            [
                'key' => 'standard',
                'name' => 'Standard',
                'credits' => 35,
                'price' => 9500,
            ],
            [
                'key' => 'power',
                'name' => 'Power',
                'credits' => 80,
                'price' => 20000,
            ],
        ],
    ],

    'annual' => [
        'price' => 25000,
        'label' => 'Annual unlock',
        'auto_renew' => false,
        'reminder_days' => [14, 7, 1],
        'reminder_channels' => ['whatsapp', 'sms'],
        'includes' => [
            'unlimited_review_links',
            'all_credit_gated_actions',
        ],
    ],

    'referral' => [
        'credits_reward' => 5,
        'expire' => false,
        'qualify_when' => 'referred_user_logs_job',
    ],

    'payments' => [
        'processors' => ['paystack', 'flutterwave'],
        'methods' => ['bank_transfer', 'ussd', 'debit_card_once'],
        'forbidden' => ['card_on_file', 'standing_instruction', 'silent_auto_renew'],
    ],

    'planned' => [
        'reach_visibility' => [
            'status' => 'planned',
            'uses_same_credits' => true,
        ],
    ],

];
