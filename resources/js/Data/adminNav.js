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
                    { label: 'Duties', route: 'admin.roles.index' },
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
                    { label: 'Hidden', route: 'admin.jobs.index', params: { tab: 'hidden' } },
                ],
            },
            {
                key: 'patrol-jobs',
                label: 'Job logs patrol',
                shortLabel: 'Job patrol',
                icon: 'ti ti-binoculars',
                route: 'admin.patrol.jobs',
                match: ['admin.patrol.jobs', 'admin.patrol.show'],
                ability: 'patrol.view',
            },
            {
                key: 'patrol-reviews',
                label: 'Reviews patrol',
                shortLabel: 'Review patrol',
                icon: 'ti ti-star-half',
                route: 'admin.patrol.reviews',
                match: ['admin.patrol.reviews'],
                ability: 'patrol.view',
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
                    { label: 'Hidden', route: 'admin.reviews.index', params: { tab: 'hidden' } },
                    { label: 'Health', route: 'admin.reviews.index', params: { tab: 'health' } },
                ],
            },
            {
                key: 'assigned',
                label: 'Assigned to me',
                shortLabel: 'Assigned',
                icon: 'ti ti-user-check',
                route: 'admin.assigned.index',
                match: ['admin.assigned.*'],
            },
            {
                key: 'my-approvals',
                label: 'My approvals',
                shortLabel: 'Approvals',
                icon: 'ti ti-clock-hour-4',
                route: 'admin.my-approvals.index',
                match: ['admin.my-approvals.*'],
                opsOnly: true,
            },
            {
                key: 'moderation-desk',
                label: 'Moderation desk',
                shortLabel: 'Desk',
                icon: 'ti ti-layout-grid',
                route: 'admin.moderation-desk.index',
                match: ['admin.moderation-desk.*'],
                abilitiesAny: ['admin.content.manage', 'admin.moderation.manage', 'patrol.view', 'admin.users.view'],
                preview: true,
            },
        ],
    },
    {
        label: 'Growth',
        items: [
            {
                key: 'referrals',
                label: 'Referrals',
                icon: 'ti ti-gift',
                route: 'admin.referrals.index',
                match: ['admin.referrals.*'],
                ability: 'admin.referrals.view',
                tabs: [
                    { label: 'Performance', route: 'admin.referrals.index' },
                    { label: 'Signals', route: 'admin.referrals.index', params: { tab: 'signals' } },
                ],
            },
            {
                key: 'verification',
                label: 'Verification',
                icon: 'ti ti-rosette-discount-check',
                route: 'admin.verification.index',
                match: ['admin.verification.*'],
                ability: 'ops.verification.manage',
            },
            {
                key: 'onboarding',
                label: 'Onboarding follow-up',
                shortLabel: 'Onboarding',
                icon: 'ti ti-route',
                route: 'admin.onboarding.index',
                match: ['admin.onboarding.*'],
                ability: 'ops.onboarding.manage',
            },
            {
                key: 'reengagement',
                label: 'Re-engagement',
                icon: 'ti ti-flame',
                route: 'admin.reengagement.index',
                match: ['admin.reengagement.*'],
                ability: 'ops.reengagement.manage',
            },
            {
                key: 'knowledge',
                label: 'Knowledge base',
                shortLabel: 'Knowledge',
                icon: 'ti ti-books',
                route: 'admin.knowledge.index',
                match: ['admin.knowledge.*'],
                ability: 'ops.knowledge.manage',
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
                key: 'faqs',
                label: 'FAQs',
                icon: 'ti ti-help',
                route: 'admin.faqs.index',
                match: ['admin.faqs.*'],
                super: true,
                tabs: [{ label: 'All FAQs', route: 'admin.faqs.index' }],
            },
            {
                key: 'careers',
                label: 'Careers',
                icon: 'ti ti-id',
                route: 'admin.careers.index',
                match: ['admin.careers.*'],
                super: true,
                tabs: [{ label: 'Vacancies', route: 'admin.careers.index' }],
            },
            {
                key: 'taxonomy',
                label: 'Trades & skills',
                icon: 'ti ti-briefcase',
                route: 'admin.taxonomy.index',
                match: ['admin.taxonomy.*'],
                super: true,
                tabs: [
                    { label: 'Categories', route: 'admin.taxonomy.index', params: { tab: 'categories' } },
                    { label: 'Skills', route: 'admin.taxonomy.index', params: { tab: 'skills' } },
                ],
            },
            {
                key: 'messaging',
                label: 'Announcements',
                icon: 'ti ti-megaphone',
                route: 'admin.messaging.index',
                match: ['admin.messaging.*'],
                super: true,
                ability: 'admin.messaging.manage',
                tabs: [
                    { label: 'Users', route: 'admin.messaging.index', params: { audience: 'users' } },
                    { label: 'Staff', route: 'admin.messaging.index', params: { audience: 'staff' } },
                    { label: 'Templates', route: 'admin.messaging.templates' },
                ],
            },
            {
                key: 'ops_messages',
                label: 'Message templates',
                icon: 'ti ti-mail-forward',
                route: 'admin.ops-messages.index',
                match: ['admin.ops-messages.*'],
                super: true,
                tabs: [{ label: 'Templates', route: 'admin.ops-messages.index' }],
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
                label: 'Team',
                icon: 'ti ti-messages',
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

/**
 * Operations console hubs. The long ops menu is collapsed into a handful of
 * hubs; each hub exposes its member pages as a top-tab row so staff can switch
 * between related pages seamlessly. Hubs with a `route` are single landing
 * pages (Home, Insights); hubs with `items` fan out into page tabs.
 */
export const opsHubs = [
    {
        key: 'home',
        label: 'Home',
        icon: 'ti ti-home',
        route: 'admin.dashboard',
        match: ['admin.dashboard', 'admin.tasks'],
    },
    {
        key: 'queues',
        label: 'Queues',
        icon: 'ti ti-inbox',
        items: [
            'assigned',
            'my-approvals',
            'support',
            'patrol-jobs',
            'patrol-reviews',
            'jobs',
            'reviews',
            'moderation-desk',
            'billing_issues',
        ],
    },
    {
        key: 'growth',
        label: 'Growth',
        icon: 'ti ti-trending-up',
        items: ['referrals', 'verification', 'onboarding', 'reengagement'],
    },
    {
        key: 'comms',
        label: 'Comms',
        icon: 'ti ti-messages',
        items: ['knowledge', 'asap'],
    },
    {
        key: 'insights',
        label: 'Insights',
        icon: 'ti ti-chart-bar',
        route: 'admin.insights.index',
        match: ['admin.insights.*'],
    },
];

function navItemByKey(key) {
    for (const group of adminNavGroups) {
        const found = group.items.find((item) => item.key === key);
        if (found) {
            return found;
        }
    }
    return null;
}

/**
 * Resolve hubs to only those visible to the current user, each carrying its
 * visible member pages. Single-route hubs are always kept.
 */
export function visibleOpsHubs(isSuperAdmin = false, abilities = []) {
    return opsHubs
        .map((hub) => {
            if (hub.route) {
                return { ...hub, pages: [] };
            }

            const pages = (hub.items || [])
                .map((key) => navItemByKey(key))
                .filter((item) => item && canSeeNavItem(item, isSuperAdmin, abilities));

            return { ...hub, pages };
        })
        .filter((hub) => hub.route || hub.pages.length > 0);
}

function hubMatchesRoute(hub, current) {
    if (hub.route) {
        return (hub.match || [hub.route]).some((pattern) => {
            if (pattern.endsWith('.*')) {
                return String(current || '').startsWith(pattern.slice(0, -2));
            }
            return current === pattern;
        });
    }

    return (hub.pages || []).some((item) => matchNavItem(item, current));
}

/**
 * The hub that owns the current route (falls back to the first hub).
 */
export function activeOpsHub(current, isSuperAdmin = false, abilities = []) {
    const hubs = visibleOpsHubs(isSuperAdmin, abilities);
    return hubs.find((hub) => hubMatchesRoute(hub, current)) || hubs[0] || null;
}

export function opsHubHref(hub, isSuperAdmin = false, abilities = []) {
    if (hub.route) {
        try {
            return route(hub.route);
        } catch {
            return '#';
        }
    }

    const first = hub.pages?.[0];
    return first ? navItemHref(first, isSuperAdmin, abilities) : '#';
}

export function flattenAdminNav(isSuperAdmin = false, abilities = []) {
    return adminNavGroups.flatMap((group) =>
        group.items.filter((item) => canSeeNavItem(item, isSuperAdmin, abilities)),
    );
}

export function canSeeNavItem(item, isSuperAdmin = false, abilities = []) {
    if (item.super && !isSuperAdmin) {
        return false;
    }

    if (item.opsOnly && isSuperAdmin) {
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
