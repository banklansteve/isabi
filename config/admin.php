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
                'admin.ops_messages.send',
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
                'admin.ops_messages.send',
            ],
        ],
        [
            'slug' => 'patrol',
            'name' => 'Patrol',
            'description' => 'Investigate flagged artisan job logs. Cannot finalize high-severity dismissals or removals.',
            'icon' => 'ti ti-binoculars',
            'permissions' => [
                'admin.users.view',
                'admin.content.manage',
                'patrol.view',
                'patrol.investigate',
            ],
        ],
        [
            'slug' => 'growth_ops',
            'name' => 'Growth / referrals',
            'description' => 'View referral lists and details. No platform revenue or token sales totals.',
            'icon' => 'ti ti-gift',
            'permissions' => [
                'admin.users.view',
                'admin.referrals.view',
            ],
        ],
        [
            'slug' => 'finance_officer',
            'name' => 'Billing issues',
            'description' => 'Resolve payment failures, multi-charges, and chargebacks. Cannot view platform revenue.',
            'icon' => 'ti ti-credit-card',
            'permissions' => [
                'admin.users.view',
                'admin.billing_issues.manage',
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
        [
            'slug' => 'people_discipline',
            'name' => 'Disciplinary cases',
            'description' => 'Prepare, investigate, and record formal employment matters. Does not include general HR access.',
            'icon' => 'ti ti-clipboard-text',
            'permissions' => [
                'hr.discipline.view',
                'hr.discipline.manage',
            ],
        ],
        [
            'slug' => 'referral_monitoring',
            'name' => 'Referral monitoring',
            'description' => 'Watch referral and vouch chains for suspicious patterns before they cost credits.',
            'icon' => 'ti ti-affiliate',
            'permissions' => [
                'admin.users.view',
                'admin.referrals.view',
            ],
        ],
        [
            'slug' => 'onboarding_followup',
            'name' => 'Onboarding follow-up',
            'description' => 'Reach out to users stuck mid-funnel — verified but no first job logged yet.',
            'icon' => 'ti ti-route',
            'permissions' => [
                'admin.users.view',
                'ops.onboarding.manage',
                'admin.ops_messages.send',
            ],
        ],
        [
            'slug' => 'reengagement',
            'name' => 'Re-engagement outreach',
            'description' => 'Nudge inactive users back via WhatsApp and one-to-one outreach.',
            'icon' => 'ti ti-flame',
            'permissions' => [
                'admin.users.view',
                'ops.reengagement.manage',
                'admin.ops_messages.send',
            ],
        ],
        [
            'slug' => 'knowledge_base',
            'name' => 'Knowledge base upkeep',
            'description' => 'Flag FAQ gaps from recurring questions and keep canned responses sharp.',
            'icon' => 'ti ti-books',
            'permissions' => [
                'ops.knowledge.manage',
                'admin.support.manage',
            ],
        ],
        [
            'slug' => 'verification',
            'name' => 'Verification',
            'description' => 'Review new sign-ups’ trade claims and confirm WhatsApp numbers where manual checks are needed.',
            'icon' => 'ti ti-rosette-discount-check',
            'permissions' => [
                'admin.users.view',
                'ops.verification.manage',
                'admin.users.manage',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Operations home
    |--------------------------------------------------------------------------
    |
    | Short labels and colours for role pills. Shortcuts are destinations on
    | the ops home — never shown unless the person has the matching ability.
    |
    */
    'duty_badges' => [
        'customer_support' => ['short' => 'Support', 'tone' => 'support', 'route' => 'admin.support.index'],
        'moderation' => ['short' => 'Moderator', 'tone' => 'moderator', 'route' => 'admin.jobs.index'],
        'trust_safety' => ['short' => 'Moderator', 'tone' => 'moderator', 'route' => 'admin.jobs.index'],
        'patrol' => ['short' => 'Patrol', 'tone' => 'patrol', 'route' => 'admin.patrol.jobs'],
        'verification_officer' => ['short' => 'Verification', 'tone' => 'verify', 'route' => 'admin.users.index'],
        'people_hr' => ['short' => 'HR', 'tone' => 'hr', 'route' => 'admin.hr.index'],
        'people_discipline' => ['short' => 'Discipline', 'tone' => 'discipline', 'route' => 'admin.hr.discipline.index'],
        'finance_officer' => ['short' => 'Billing issues', 'tone' => 'finance', 'route' => 'admin.billing-issues.index'],
        'growth_ops' => ['short' => 'Growth', 'tone' => 'growth', 'route' => 'admin.referrals.index'],
        'content_comms' => ['short' => 'Content', 'tone' => 'content', 'route' => 'admin.messaging.index'],
        'referral_monitoring' => ['short' => 'Referrals', 'tone' => 'growth', 'route' => 'admin.referrals.index'],
        'onboarding_followup' => ['short' => 'Onboarding', 'tone' => 'growth', 'route' => 'admin.onboarding.index'],
        'reengagement' => ['short' => 'Re-engage', 'tone' => 'growth', 'route' => 'admin.reengagement.index'],
        'knowledge_base' => ['short' => 'Knowledge', 'tone' => 'content', 'route' => 'admin.knowledge.index'],
        'verification' => ['short' => 'Verification', 'tone' => 'verify', 'route' => 'admin.verification.index'],
    ],

    'ops_shortcuts' => [
        [
            'key' => 'support',
            'label' => 'Customer support',
            'icon' => 'ti ti-headset',
            'route' => 'admin.support.index',
            'ability' => 'admin.support.manage',
            'hint' => 'assigned to you',
        ],
        [
            'key' => 'asap',
            'label' => 'ASAP',
            'icon' => 'ti ti-bolt',
            'route' => 'admin.asap.index',
            'hint' => 'unread',
        ],
        [
            'key' => 'billing',
            'label' => 'Billing issues',
            'icon' => 'ti ti-credit-card',
            'route' => 'admin.billing-issues.index',
            'ability' => 'admin.billing_issues.manage',
            'hint' => 'open',
        ],
        [
            'key' => 'approvals',
            'label' => 'Approvals',
            'icon' => 'ti ti-shield-check',
            'route' => 'admin.approvals.index',
            'ability' => 'admin.approvals.manage',
            'hint' => 'pending',
        ],
        [
            'key' => 'patrol_jobs',
            'label' => 'Job logs patrol',
            'icon' => 'ti ti-binoculars',
            'route' => 'admin.patrol.jobs',
            'ability' => 'patrol.view',
            'hint' => 'active',
        ],
        [
            'key' => 'patrol_reviews',
            'label' => 'Reviews patrol',
            'icon' => 'ti ti-star-half',
            'route' => 'admin.patrol.reviews',
            'ability' => 'patrol.view',
            'hint' => 'active',
        ],
        [
            'key' => 'jobs',
            'label' => 'Job logs',
            'icon' => 'ti ti-briefcase',
            'route' => 'admin.jobs.index',
            'ability' => 'admin.content.manage',
            'hint' => 'to review',
        ],
        [
            'key' => 'users',
            'label' => 'Users',
            'icon' => 'ti ti-users',
            'route' => 'admin.users.index',
            'ability' => 'admin.users.view',
            'hint' => 'to verify',
        ],
        [
            'key' => 'hr',
            'label' => 'HR',
            'icon' => 'ti ti-id-badge-2',
            'route' => 'admin.hr.index',
            'abilitiesAny' => ['hr.view', 'hr.discipline.view'],
            'hint' => 'pending',
        ],
        [
            'key' => 'credits',
            'label' => 'Credits',
            'icon' => 'ti ti-coin',
            'route' => 'admin.credits.index',
            'ability' => 'admin.credits.view',
            'hint' => 'open',
        ],
        [
            'key' => 'analytics',
            'label' => 'Analytics',
            'icon' => 'ti ti-chart-dots-2',
            'route' => 'admin.analytics.index',
            'ability' => 'admin.analytics.view',
            'hint' => 'live',
        ],
        [
            'key' => 'reviews',
            'label' => 'Reviews',
            'icon' => 'ti ti-star',
            'route' => 'admin.reviews.index',
            'ability' => 'admin.content.manage',
            'hint' => 'pending',
        ],
        [
            'key' => 'referrals',
            'label' => 'Referrals',
            'icon' => 'ti ti-gift',
            'route' => 'admin.referrals.index',
            'ability' => 'admin.referrals.view',
            'hint' => 'new',
        ],
        [
            'key' => 'onboarding',
            'label' => 'Onboarding follow-up',
            'icon' => 'ti ti-route',
            'route' => 'admin.onboarding.index',
            'ability' => 'ops.onboarding.manage',
            'hint' => 'to nudge',
        ],
        [
            'key' => 'reengagement',
            'label' => 'Re-engagement',
            'icon' => 'ti ti-flame',
            'route' => 'admin.reengagement.index',
            'ability' => 'ops.reengagement.manage',
            'hint' => 'inactive',
        ],
        [
            'key' => 'verification',
            'label' => 'Verification',
            'icon' => 'ti ti-rosette-discount-check',
            'route' => 'admin.verification.index',
            'ability' => 'ops.verification.manage',
            'hint' => 'to review',
        ],
        [
            'key' => 'knowledge',
            'label' => 'Knowledge base',
            'icon' => 'ti ti-books',
            'route' => 'admin.knowledge.index',
            'ability' => 'ops.knowledge.manage',
            'hint' => 'gaps',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Operations insights
    |--------------------------------------------------------------------------
    |
    | Scoring is relative to the team in the selected window. Presence only
    | counts as "away" while the person should be on duty.
    |
    */
    'ops_insights' => [
        'idle_after_minutes' => 7,
        'presence_write_seconds' => 30,
        'live_poll_ms' => 15000,
        'queues' => [
            'support' => [
                'label' => 'Customer service',
                'icon' => 'ti ti-headset',
                'prefixes' => ['support.'],
            ],
            'patrol' => [
                'label' => 'Patrol',
                'icon' => 'ti ti-binoculars',
                'prefixes' => ['patrol.'],
            ],
            'jobs' => [
                'label' => 'Job logs',
                'icon' => 'ti ti-briefcase',
                'prefixes' => ['jobs.'],
            ],
            'users' => [
                'label' => 'Users',
                'icon' => 'ti ti-users',
                'prefixes' => ['users.'],
            ],
            'messaging' => [
                'label' => 'Messaging',
                'icon' => 'ti ti-speakerphone',
                'prefixes' => ['messaging.', 'staff.messaged', 'staff.announced'],
            ],
            'referrals' => [
                'label' => 'Referrals',
                'icon' => 'ti ti-gift',
                'prefixes' => ['referrals.'],
            ],
            'hr' => [
                'label' => 'HR',
                'icon' => 'ti ti-id-badge-2',
                'prefixes' => ['hr.', 'discipline.'],
            ],
        ],
        'completion_actions' => [
            'support.resolved',
            'patrol.dismissed',
            'patrol.removed',
            'patrol.hidden',
            'patrol.handoff_suspend',
            'jobs.flagged',
            'jobs.unflagged',
            'jobs.hidden',
            'jobs.unhidden',
            'jobs.referred',
            'cases.referred',
            'cases.escalated',
            'users.verified',
            'users.suspended',
            'users.reinstated',
        ],
        'score_weights' => [
            'completions' => 40,
            'responsiveness' => 25,
            'volume' => 20,
            'presence' => 15,
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
                'admin.patrol.manage' => 'Legacy patrol access (prefer patrol.view / investigate / resolve)',
                'patrol.view' => 'View the Patrol queues (job logs and reviews), case detail, and notes',
                'patrol.investigate' => 'Add notes, move cases to in review, recommend outcomes, and dismiss low-severity cases',
                'patrol.resolve' => 'Finalize dismissals, hide or soft-remove content, and hand off to Users',
            ],
        ],
        'money' => [
            'label' => 'Credits & financials',
            'items' => [
                'admin.credits.view' => 'View credit transactions, purchases, and platform revenue (Super Admin / finance only)',
                'admin.billing.manage' => 'Adjust credits, pricing, and financials',
                'admin.billing_issues.manage' => 'Work payment failures, multi-charges, and chargebacks for assigned customers',
                'admin.referrals.view' => 'View referral lists and details (no platform revenue)',
            ],
        ],
        'ops' => [
            'label' => 'Support & messaging',
            'items' => [
                'admin.support.manage' => 'Handle artisan support tickets',
                'admin.messaging.manage' => 'Send announcements to artisans or staff (Super Admin)',
                'admin.ops_messages.send' => 'Send Super Admin templated messages to site users',
                'admin.analytics.view' => 'View growth and engagement analytics',
            ],
        ],
        'lifecycle' => [
            'label' => 'Growth & lifecycle',
            'items' => [
                'ops.onboarding.manage' => 'Follow up with users stuck mid-onboarding (verified but no first job)',
                'ops.reengagement.manage' => 'Run re-engagement outreach to inactive users',
                'ops.verification.manage' => 'Review trade claims and confirm WhatsApp numbers',
                'ops.knowledge.manage' => 'Maintain the knowledge base and canned response templates',
            ],
        ],
        'access' => [
            'label' => 'Staff management',
            'items' => [
                'admin.staff.invite' => 'Invite operations staff',
                'admin.staff.manage' => 'Disable, remove, and assign roles to staff',
                'admin.roles.manage' => 'Create and edit roles and permissions',
                'admin.activity.view' => 'View the audit log and staff activity',
                'admin.approvals.manage' => 'Review operations actions awaiting Super Admin approval',
                'admin.settings.manage' => 'Change platform settings',
            ],
        ],
        'people' => [
            'label' => 'People / HR',
            'items' => [
                'hr.view' => 'View the staff directory, leave, and HR profiles',
                'hr.manage' => 'Edit HR profiles, documents, checklists, and performance notes',
                'hr.leave.manage' => 'Book, approve, reject, cancel, and allocate leave',
                'hr.payroll.view' => 'View compensation and aggregate payroll reports',
                'hr.payroll.manage' => 'Edit compensation and generate payslips',
                'hr.discipline.view' => 'View disciplinary case files, timelines, and aggregate case reports',
                'hr.discipline.manage' => 'Open cases, record notes, issue formal actions, and manage letter templates',
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
            'description' => 'Must match the SMTP account for Gmail (the Gmail address). Other From addresses are usually dropped.',
        ],
        [
            'key' => 'mail.from.name',
            'label' => 'Mail from name',
            'group' => 'mail',
            'type' => 'string',
            'config' => 'mail.from.name',
            'description' => 'The name shown on outbound Kraftrack email.',
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
        [
            'key' => 'ops.templated_messaging_enabled',
            'label' => 'Allow operations templated messaging',
            'group' => 'ops',
            'type' => 'boolean',
            'description' => 'When off, operations cannot send templated messages to site users.',
        ],
        [
            'key' => 'ops.templated_messaging_require_approval',
            'label' => 'Require Super Admin approval for ops messages',
            'group' => 'ops',
            'type' => 'boolean',
            'description' => 'When on, every outbound templated message from operations waits for Super Admin approval.',
        ],
    ],

];
