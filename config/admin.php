<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Staff invitation codes
    |--------------------------------------------------------------------------
    */
    'invite' => [
        'code_length' => 6,
        'ttl_hours' => 48,
        'ttl_minutes' => 48 * 60,
        'max_attempts' => 5,
        'resend_delay_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default operations duties
    |--------------------------------------------------------------------------
    |
    | Super admin can create more later. These are seeded as system roles and
    | cannot be deleted — only deactivated.
    |
    */
    'roles' => [
        [
            'slug' => 'customer_support',
            'name' => 'Customer support',
            'description' => 'Attend to in-app chats and artisan support queues.',
            'icon' => 'ti ti-headset',
            'permissions' => [
                'admin.users.view',
                'admin.support.manage',
                'admin.messaging.manage',
            ],
        ],
        [
            'slug' => 'moderation',
            'name' => 'Moderation',
            'description' => 'Review reported content, fake work, and policy violations.',
            'icon' => 'ti ti-shield',
            'permissions' => [
                'admin.users.view',
                'admin.users.manage',
                'admin.content.manage',
                'admin.moderation.manage',
            ],
        ],
        [
            'slug' => 'patrol',
            'name' => 'Patrol',
            'description' => 'Review public profiles, jobs, and reviews for spam, impersonation, and policy issues.',
            'icon' => 'ti ti-binoculars',
            'permissions' => [
                'admin.users.view',
                'admin.content.manage',
                'admin.patrol.manage',
            ],
        ],
        [
            'slug' => 'people_hr',
            'name' => 'People / HR',
            'description' => 'Employment records, leave, and documents for operations staff.',
            'icon' => 'ti ti-id-badge-2',
            'permissions' => [
                'hr.view',
                'hr.manage',
                'hr.leave.manage',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Granular staff permissions
    |--------------------------------------------------------------------------
    |
    | Super Admin always has every key. Operations staff inherit the union of
    | permissions on their assigned roles. `admin.access` is implied for any
    | active staff member so they can reach an empty console if role-less.
    |
    */
    'permissions' => [
        'users' => [
            'label' => 'Users',
            'items' => [
                'admin.users.view' => 'View artisan accounts',
                'admin.users.manage' => 'Suspend, verify, edit, and message artisans',
                'admin.users.impersonate' => 'View the product as an artisan',
            ],
        ],
        'work' => [
            'label' => 'Jobs & reviews',
            'items' => [
                'admin.content.manage' => 'View and moderate job logs and reviews',
                'admin.moderation.manage' => 'Handle flagged and policy-violating content',
                'admin.patrol.manage' => 'Run patrol queues on public profiles',
            ],
        ],
        'money' => [
            'label' => 'Credits & financials',
            'items' => [
                'admin.credits.view' => 'View credit transactions and purchases',
                'admin.billing.manage' => 'Adjust credits, pricing, and financials',
            ],
        ],
        'ops' => [
            'label' => 'Support & messaging',
            'items' => [
                'admin.support.manage' => 'Handle artisan support tickets',
                'admin.messaging.manage' => 'Send announcements to artisans or staff',
                'admin.analytics.view' => 'View growth and engagement analytics',
            ],
        ],
        'access' => [
            'label' => 'Staff management',
            'items' => [
                'admin.staff.invite' => 'Invite operations staff',
                'admin.staff.manage' => 'Disable, remove, and assign roles to staff',
                'admin.roles.manage' => 'Create and edit roles and permissions',
                'admin.activity.view' => 'View the audit log and staff activity',
                'admin.settings.manage' => 'Change platform settings',
            ],
        ],
        'people' => [
            'label' => 'People / HR',
            'items' => [
                'hr.view' => 'View the staff directory, leave, discipline, and HR profiles',
                'hr.manage' => 'Edit HR profiles, documents, checklists, performance notes, and disciplinary records',
                'hr.leave.manage' => 'Book, approve, reject, cancel, and allocate leave',
                'hr.payroll.view' => 'View compensation and aggregate payroll reports',
                'hr.payroll.manage' => 'Edit compensation and generate payslips',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super-admin editable settings
    |--------------------------------------------------------------------------
    |
    | Values live in the app_settings table and are merged over config() at
    | boot. Super admin has full control of these keys from the admin UI.
    |
    */
    'settings' => [
        [
            'key' => 'app.name',
            'label' => 'Application name',
            'group' => 'general',
            'type' => 'string',
            'config' => 'app.name',
            'description' => 'Shown in emails, the browser title, and public pages.',
        ],
        [
            'key' => 'app.display_timezone',
            'label' => 'Display timezone',
            'group' => 'general',
            'type' => 'string',
            'config' => 'app.display_timezone',
            'description' => 'Timezone used for dates shown to people in the app.',
        ],
        [
            'key' => 'mail.from.address',
            'label' => 'Mail from address',
            'group' => 'mail',
            'type' => 'string',
            'config' => 'mail.from.address',
            'description' => 'The address Isabi sends transactional email from.',
        ],
        [
            'key' => 'mail.from.name',
            'label' => 'Mail from name',
            'group' => 'mail',
            'type' => 'string',
            'config' => 'mail.from.name',
            'description' => 'The name shown on outbound Isabi email.',
        ],
        [
            'key' => 'profiles.max_slug_changes',
            'label' => 'Max public URL changes',
            'group' => 'profiles',
            'type' => 'integer',
            'config' => 'profiles.max_slug_changes',
            'description' => 'How many times an artisan may change their public page slug.',
        ],
        [
            'key' => 'pricing.payments.instant_fulfill',
            'label' => 'Instant token fulfillment',
            'group' => 'features',
            'type' => 'boolean',
            'config' => 'pricing.payments.instant_fulfill',
            'description' => 'Credit tokens immediately after a purchase (turn off once Paystack/Flutterwave is live).',
        ],
        [
            'key' => 'services.paystack.secret',
            'label' => 'Paystack secret key',
            'group' => 'payments',
            'type' => 'string',
            'config' => 'services.paystack.secret',
            'description' => 'Server secret. Stored in the database override table, never committed.',
        ],
        [
            'key' => 'services.paystack.public',
            'label' => 'Paystack public key',
            'group' => 'payments',
            'type' => 'string',
            'config' => 'services.paystack.public',
            'description' => 'Browser-safe public key.',
        ],
        [
            'key' => 'services.flutterwave.secret',
            'label' => 'Flutterwave secret key',
            'group' => 'payments',
            'type' => 'string',
            'config' => 'services.flutterwave.secret',
            'description' => 'Server secret. Stored in the database override table, never committed.',
        ],
        [
            'key' => 'profiles.reserved',
            'label' => 'Reserved profile slugs',
            'group' => 'slugs',
            'type' => 'json',
            'config' => 'profiles.reserved',
            'description' => 'Public URLs artisans cannot claim.',
        ],
        [
            'key' => 'session.lifetime_users',
            'label' => 'Session lifetime for artisans / users',
            'group' => 'session',
            'type' => 'integer',
            'config' => 'session.lifetime_users',
            'description' => 'How long artisans stay signed in after their last activity.',
        ],
        [
            'key' => 'session.lifetime_operations',
            'label' => 'Session lifetime for operations / staff',
            'group' => 'session',
            'type' => 'integer',
            'config' => 'session.lifetime_operations',
            'description' => 'How long operations staff stay signed in after their last activity.',
        ],
        [
            'key' => 'session.lifetime_super_admin',
            'label' => 'Session lifetime for super admin',
            'group' => 'session',
            'type' => 'integer',
            'config' => 'session.lifetime_super_admin',
            'description' => 'How long the super admin stays signed in after their last activity.',
        ],
    ],

];
