<?php

return [
    'hours' => [
        'timezone' => env('SUPPORT_TIMEZONE', env('APP_DISPLAY_TIMEZONE', 'Africa/Lagos')),
        'days' => [1, 2, 3, 4, 5, 6],
        'start' => '08:00',
        'end' => '18:00',
    ],

    'presence_ttl' => 50,
    'typing_ttl' => 5,
    'offline_after' => 90,
    'idle_after_minutes' => (int) env('OPS_IDLE_AFTER_MINUTES', 7),
    'hot_wait_minutes' => 30,
    'poll_interval_ms' => 8000,
    'rate_per_minute' => 20,
    'max_attachment_kb' => 8192,
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ],

    'offline_copy' => 'Our team is offline right now. Average reply time is a few hours — leave a message and we will pick it up.',
    'online_copy' => 'An agent usually replies within a few minutes.',
    'waiting_copy' => 'Waiting for a reply',
    'connected_copy' => 'Agent connected',
    'resolved_copy' => 'Resolved',
    'next_agent_copy' => 'Waiting for the next available agent',

    'starters' => [
        [
            'key' => 'billing',
            'label' => 'I have a billing question',
            'message' => 'I have a question about credits or billing.',
            'ability' => 'admin.credits.view',
        ],
        [
            'key' => 'reviews',
            'label' => 'Help with a review request',
            'message' => 'I need help with a client review request.',
            'ability' => 'admin.support.manage',
        ],
        [
            'key' => 'page',
            'label' => 'Something about my public page',
            'message' => 'I need help with my public page.',
            'ability' => 'admin.support.manage',
        ],
        [
            'key' => 'work-log',
            'label' => 'A job I logged',
            'message' => 'I need help with a job on my work log.',
            'ability' => 'admin.content.manage',
        ],
        [
            'key' => 'other',
            'label' => 'Something else',
            'message' => null,
            'ability' => 'admin.support.manage',
        ],
    ],
];
