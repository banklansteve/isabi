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
                ability: 'hr.view',
                tabs: [
                    { label: 'Directory', route: 'admin.hr.index' },
                    { label: 'Leave', route: 'admin.hr.leave.index' },
                    { label: 'Calendar', route: 'admin.hr.calendar' },
                    { label: 'Discipline', route: 'admin.hr.discipline.index' },
                    { label: 'Reports', route: 'admin.hr.reports.index' },
                    { label: 'Settings', route: 'admin.hr.settings' },
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
                    { label: 'Suspicious', route: 'admin.jobs.index', params: { tab: 'suspicious' } },
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
                label: 'Messaging',
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
                key: 'support',
                label: 'Support',
                icon: 'ti ti-headset',
                route: 'admin.support.index',
                match: ['admin.support.*'],
                ability: 'admin.support.manage',
                tabs: [
                    { label: 'Open', route: 'admin.support.index' },
                    { label: 'Resolved', route: 'admin.support.index', params: { status: 'resolved' } },
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

    if (item.ability && !isSuperAdmin && !(abilities || []).includes(item.ability)) {
        return false;
    }

    return true;
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

export function tabHref(tab) {
    const params = tab.params || {};
    try {
        return route(tab.route, params);
    } catch {
        return '#';
    }
}

export function tabIsActive(tab, current, query = {}) {
    if (current !== tab.route) {
        return false;
    }

    const params = tab.params || {};
    const keys = Object.keys(params);

    if (keys.length === 0) {
        return !query.tab && !query.status && !query.audience;
    }

    return keys.every((key) => String(query[key] || '') === String(params[key]));
}
