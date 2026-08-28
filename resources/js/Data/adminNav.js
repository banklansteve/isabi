export const adminNavGroups = [
    {
        label: 'Platform',
        items: [
            {
                key: 'overview',
                label: 'Overview',
                icon: 'ti ti-layout-dashboard',
                route: 'admin.dashboard',
                match: ['admin.dashboard'],
                tabs: [{ label: 'Home', route: 'admin.dashboard' }],
            },
            {
                key: 'insights',
                label: 'Insights',
                icon: 'ti ti-chart-bar',
                route: 'admin.insights.index',
                match: ['admin.insights.*'],
                tabs: [{ label: 'Performance', route: 'admin.insights.index' }],
            },
            {
                key: 'analytics',
                label: 'Analytics',
                icon: 'ti ti-chart-dots-2',
                route: 'admin.analytics.index',
                match: ['admin.analytics.*'],
                ability: 'admin.analytics.view',
                tabs: [
                    { label: 'Growth', route: 'admin.analytics.index', params: { tab: 'growth' } },
                    { label: 'Engagement', route: 'admin.analytics.index', params: { tab: 'engagement' } },
                    { label: 'Retention', route: 'admin.analytics.index', params: { tab: 'retention' } },
                    { label: 'Geography', route: 'admin.analytics.index', params: { tab: 'geography' } },
                ],
            },
        ],
    },
    {
        label: 'People',
        items: [
            {
                key: 'users',
                label: 'Users',
                icon: 'ti ti-users',
                route: 'admin.users.index',
                match: ['admin.users.*'],
                ability: 'admin.users.view',
                tabs: [
                    { label: 'Directory', route: 'admin.users.index' },
                    { label: 'Suspended', route: 'admin.users.index', params: { status: 'suspended' } },
                    { label: 'Unverified', route: 'admin.users.index', params: { status: 'unverified' } },
                ],
            },
            {
                key: 'staff',
                label: 'Admin & staff',
                icon: 'ti ti-shield-lock',
                route: 'admin.staff.index',
                match: ['admin.staff.*', 'admin.roles.*'],
                super: true,
                ability: 'admin.staff.manage',
                tabs: [
                    { label: 'Team', route: 'admin.staff.index' },
                    { label: 'Roles', route: 'admin.roles.index' },
                ],
            },
            {
                key: 'people',
                label: 'HR',
                icon: 'ti ti-id-badge-2',
                route: 'admin.hr.index',
                match: ['admin.hr.*'],
                abilitiesAny: ['hr.view', 'hr.discipline.view'],
                tabs: [
                    { label: 'Directory', route: 'admin.hr.index', ability: 'hr.view', matchPrefix: 'admin.hr.staff' },
                    { label: 'Leave', route: 'admin.hr.leave.index', ability: 'hr.view' },
                    { label: 'Calendar', route: 'admin.hr.calendar', ability: 'hr.view' },
                    { label: 'Cases', route: 'admin.hr.discipline.index', ability: 'hr.discipline.view', matchPrefix: 'admin.hr.discipline' },
                    { label: 'Reports', route: 'admin.hr.reports.index', ability: 'hr.view' },
                    { label: 'Settings', route: 'admin.hr.settings', ability: 'hr.view' },
                ],
            },
        ],
    },
    {
        label: 'Work',
        items: [
            {
                key: 'jobs',
                label: 'Job logs',
                shortLabel: 'Jobs',
                icon: 'ti ti-briefcase',
                route: 'admin.jobs.index',
                match: ['admin.jobs.*'],
                ability: 'admin.content.manage',
                tabs: [
                    { label: 'All', route: 'admin.jobs.index' },
                    { label: 'Flagged', route: 'admin.jobs.index', params: { tab: 'flagged' } },
                ],
            },
            {
                key: 'patrol',
                label: 'Patrol',
                icon: 'ti ti-binoculars',
                route: 'admin.patrol.index',
                match: ['admin.patrol.*'],
                ability: 'patrol.view',
                tabs: [
                    { label: 'Job logs', route: 'admin.patrol.index', params: { tab: 'jobs' } },
                    { label: 'Reviews', route: 'admin.patrol.index', params: { tab: 'reviews' } },
                ],
            },
            {
                key: 'reviews',
                label: 'Reviews',
                icon: 'ti ti-star',
                route: 'admin.reviews.index',
                match: ['admin.reviews.*'],
                ability: 'admin.content.manage',
                tabs: [
                    { label: 'All', route: 'admin.reviews.index' },
                    { label: 'Flagged', route: 'admin.reviews.index', params: { tab: 'flagged' } },
                    { label: 'Health', route: 'admin.reviews.index', params: { tab: 'health' } },
                ],
            },
            {
                key: 'referrals',
                label: 'Referrals',
                icon: 'ti ti-gift',
                route: 'admin.referrals.index',
                match: ['admin.referrals.*'],
                ability: 'admin.users.view',
                tabs: [
                    { label: 'Performance', route: 'admin.referrals.index' },
                    { label: 'Signals', route: 'admin.referrals.index', params: { tab: 'signals' } },
                ],
            },
        ],
    },
    {
        label: 'Money',
        items: [
            {
                key: 'credits',
                label: 'Credits',
                icon: 'ti ti-coin',
                route: 'admin.credits.index',
                match: ['admin.credits.*'],
                ability: 'admin.credits.view',
                tabs: [
                    { label: 'Transactions', route: 'admin.credits.index' },
                    { label: 'Purchases', route: 'admin.credits.index', params: { tab: 'purchases' } },
                ],
            },
            {
                key: 'pricing',
                label: 'Pricing & plans',
                icon: 'ti ti-tag',
                route: 'admin.pricing.index',
                match: ['admin.pricing.*'],
                super: true,
                tabs: [
                    { label: 'Live', route: 'admin.pricing.index' },
                    { label: 'History', route: 'admin.pricing.index', params: { tab: 'history' } },
                ],
            },
            {
                key: 'financials',
                label: 'Financials',
                icon: 'ti ti-report-money',
                route: 'admin.financials.index',
                match: ['admin.financials.*'],
                super: true,
                tabs: [
                    { label: 'Revenue', route: 'admin.financials.index' },
                    { label: 'Sources', route: 'admin.financials.index', params: { tab: 'sources' } },
                    { label: 'Gateways', route: 'admin.financials.index', params: { tab: 'gateways' } },
                    { label: 'Statements', route: 'admin.financials.index', params: { tab: 'statements' } },
                ],
            },
        ],
    },
    {
        label: 'Ops',
        items: [
            {
                key: 'messaging',
                label: 'Announcements',
                icon: 'ti ti-megaphone',
                route: 'admin.messaging.index',
                match: ['admin.messaging.*'],
                ability: 'admin.messaging.manage',
                tabs: [
                    { label: 'Users', route: 'admin.messaging.index', params: { audience: 'users' } },
                    { label: 'Staff', route: 'admin.messaging.index', params: { audience: 'staff' } },
                    { label: 'Templates', route: 'admin.messaging.templates' },
                ],
            },
            {
                key: 'ops_messages',
                label: 'Warn a user',
                icon: 'ti ti-mail-forward',
                route: 'admin.ops-messages.index',
                match: ['admin.ops-messages.*'],
                abilitiesAny: ['admin.ops_messages.send', 'admin.users.manage', 'admin.messaging.manage'],
                tabs: [{ label: 'Send', route: 'admin.ops-messages.index' }],
            },
            {
                key: 'approvals',
                label: 'Approvals',
                icon: 'ti ti-shield-check',
                route: 'admin.approvals.index',
                match: ['admin.approvals.*'],
                super: true,
                ability: 'admin.approvals.manage',
                tabs: [{ label: 'Pending', route: 'admin.approvals.index' }],
            },
            {
                key: 'billing_issues',
                label: 'Billing issues',
                icon: 'ti ti-credit-card',
                route: 'admin.billing-issues.index',
                match: ['admin.billing-issues.*'],
                ability: 'admin.billing_issues.manage',
                tabs: [{ label: 'Open', route: 'admin.billing-issues.index' }],
            },
            {
                key: 'support',
                label: 'Customer support',
                icon: 'ti ti-headset',
                route: 'admin.support.index',
                match: ['admin.support.*'],
                ability: 'admin.support.manage',
                tabs: [],
            },
            {
                key: 'asap',
                label: 'ASAP',
                icon: 'ti ti-bolt',
                route: 'admin.asap.index',
                match: ['admin.asap.*'],
                tabs: [
                    { label: 'Team chat', route: 'admin.asap.index' },
                ],
            },
            {
                key: 'audit',
                label: 'Audit log',
                icon: 'ti ti-history',
                route: 'admin.audit.index',
                match: ['admin.audit.*', 'admin.activity'],
                super: true,
                tabs: [
                    { label: 'Admin actions', route: 'admin.audit.index' },
                    { label: 'User activity', route: 'admin.activity' },
                ],
            },
            {
                key: 'settings',
                label: 'Settings',
                icon: 'ti ti-settings',
                route: 'admin.settings.index',
                match: ['admin.settings.*'],
                super: true,
                tabs: [
                    { label: 'General', route: 'admin.settings.index', params: { tab: 'general' } },
                    { label: 'Features', route: 'admin.settings.index', params: { tab: 'features' } },
                    { label: 'Ops', route: 'admin.settings.index', params: { tab: 'ops' } },
                    { label: 'Payments', route: 'admin.settings.index', params: { tab: 'payments' } },
                    { label: 'Session', route: 'admin.settings.index', params: { tab: 'session' } },
                    { label: 'Slugs', route: 'admin.settings.index', params: { tab: 'slugs' } },
                ],
            },
        ],
    },
];

export const mobilePrimaryNav = ['overview', 'users', 'jobs', 'support'];

export function flattenAdminNav(isSuperAdmin = false, abilities = []) {
    return adminNavGroups.flatMap((group) =>
        group.items.filter((item) => canSeeNavItem(item, isSuperAdmin, abilities)),
    );
}

export function canSeeNavItem(item, isSuperAdmin = false, abilities = []) {
    if (item.super && !isSuperAdmin) {
        return false;
    }

    if (isSuperAdmin) {
        return true;
    }

    if (item.abilitiesAny?.length) {
        return item.abilitiesAny.some((key) => (abilities || []).includes(key));
    }

    if (item.ability && !(abilities || []).includes(item.ability)) {
        return false;
    }

    return true;
}

export function canSeeNavTab(tab, isSuperAdmin = false, abilities = []) {
    if (tab.super && !isSuperAdmin) {
        return false;
    }

    if (!tab.ability || isSuperAdmin) {
        return true;
    }

    return (abilities || []).includes(tab.ability);
}

export function matchNavItem(item, current, query = {}) {
    const matches = (item.match || [item.route]).some((pattern) => {
        if (pattern.endsWith('.*')) {
            const prefix = pattern.slice(0, -2);
            return String(current || '').startsWith(prefix);
        }
        return current === pattern;
    });

    if (!matches) return false;

    return true;
}

export function activeNavItem(current, isSuperAdmin = false, abilities = []) {
    return flattenAdminNav(isSuperAdmin, abilities).find((item) => matchNavItem(item, current)) || null;
}

export function navItemHref(item, isSuperAdmin = false, abilities = []) {
    const tabs = (item.tabs || []).filter((tab) => canSeeNavTab(tab, isSuperAdmin, abilities));

    if (tabs[0]) {
        return tabHref(tabs[0]);
    }

    try {
        return route(item.route);
    } catch {
        return '#';
    }
}

export function tabHref(tab) {
    const params = tab.params || {};
    try {
        return route(tab.route, params);
    } catch {
        return '#';
    }
}

export function tabIsActive(tab, current, query = {}) {
    if (tab.matchPrefix && String(current || '').startsWith(tab.matchPrefix)) {
        return true;
    }

    if (current !== tab.route) {
        return false;
    }

    const params = tab.params || {};
    const keys = Object.keys(params);

    if (keys.length === 0) {
        return !query.tab && !query.status && !query.audience;
    }

    return keys.every((key) => {
        const actual = String(query[key] || '');
        const expected = String(params[key]);
        if (key === 'tab' && expected === 'jobs' && actual === '') {
            return true;
        }
        return actual === expected;
    });
}
