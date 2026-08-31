<template>
    <div class="admin-shell min-h-dvh bg-[#F4F6FA] font-admin text-ink antialiased">
        <header class="sticky top-0 z-30 border-b border-ink/[0.06] bg-white/90 shadow-nav backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-[1400px] items-center gap-1 px-4 sm:gap-2 sm:px-6 lg:h-[4.25rem] lg:px-8">
                <Link
                    :href="route('admin.dashboard')"
                    prefetch
                    cache-for="5m"
                    :show-progress="false"
                    class="flex shrink-0 items-center gap-2.5"
                    aria-label="Operations home"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-ink text-[0.7rem] font-extrabold tracking-tight text-white"
                    >
                        I
                    </span>
                    <span class="hidden truncate text-[0.95rem] font-semibold tracking-tight text-ink sm:inline">
                        Isabi
                    </span>
                </Link>

                <div class="min-w-0 flex-1" />

                <div class="flex shrink-0 items-center gap-0.5 sm:gap-1">
                    <Dropdown align="right" width="80" content-classes="overflow-hidden rounded-2xl p-0">
                        <template #trigger="{ open }">
                            <button
                                type="button"
                                class="nav-hit gap-1.5 px-2.5"
                                :class="isTasks ? 'bg-tint text-deep' : ''"
                                :aria-expanded="open"
                                aria-label="Tasks"
                            >
                                <i class="ti ti-checkbox text-[1.15rem]" aria-hidden="true" />
                                <span class="hidden text-[13px] font-semibold sm:inline">Tasks</span>
                                <span
                                    v-if="unreadCount > 0"
                                    class="rounded-full bg-coral px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                                >
                                    {{ formatBadgeCount(unreadCount) }}
                                </span>
                                <i class="ti ti-chevron-down hidden text-sm text-ink/35 sm:inline" aria-hidden="true" />
                            </button>
                        </template>
                        <template #content>
                            <div class="w-80 p-2">
                                <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">
                                    Work
                                </p>
                                <Link
                                    :href="route('admin.tasks')"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-pale"
                                    :class="currentRoute === 'admin.tasks' ? 'bg-tint text-deep' : ''"
                                >
                                    <i class="ti ti-layout-list text-lg text-ink/40" aria-hidden="true" />
                                    All tasks
                                </Link>
                                <Link
                                    :href="route('admin.assigned.index', { queue: 'moderation' })"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-pale"
                                    :class="isAssigned ? 'bg-tint text-deep' : ''"
                                >
                                    <i class="ti ti-user-check text-lg text-ink/40" aria-hidden="true" />
                                    Assigned to me
                                </Link>
                                <Link
                                    v-if="!user?.is_super_admin"
                                    :href="route('admin.my-approvals.index')"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-pale"
                                    :class="isMyApprovals ? 'bg-tint text-deep' : ''"
                                >
                                    <i class="ti ti-clock-hour-4 text-lg text-ink/40" aria-hidden="true" />
                                    <span class="min-w-0 flex-1 truncate">My approvals</span>
                                    <span
                                        v-if="pendingApprovals > 0"
                                        class="rounded-full bg-base-action px-1.5 py-0.5 text-[10px] font-extrabold text-white"
                                    >
                                        {{ formatBadgeCount(pendingApprovals) }}
                                    </span>
                                </Link>

                                <div class="my-1 border-t border-ink/[0.06]" />

                                <Link
                                    v-if="canModerationDesk"
                                    :href="route('admin.moderation-desk.index')"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-pale"
                                    :class="isModerationDesk ? 'bg-tint text-deep' : ''"
                                >
                                    <i class="ti ti-layout-grid text-lg text-ink/40" aria-hidden="true" />
                                    Moderation desk
                                    <span class="rounded-full bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold text-violet-700">Preview</span>
                                </Link>

                                <div class="my-1 border-t border-ink/[0.06]" />

                                <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">
                                    Queues
                                </p>
                                <Link
                                    v-for="item in queueItems"
                                    :key="item.key"
                                    :href="item.href"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-pale"
                                    :class="item.active ? 'bg-tint text-deep' : 'text-ink'"
                                >
                                    <i :class="item.icon" class="text-lg text-ink/40" aria-hidden="true" />
                                    <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                                    <span
                                        v-if="item.badge"
                                        class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold"
                                        :class="item.unread ? 'bg-base-action text-white' : 'bg-pale text-ink/50'"
                                    >
                                        {{ formatBadgeCount(item.badge) }}
                                    </span>
                                </Link>
                                <p v-if="!queueItems.length" class="px-3 py-3 text-[12px] font-medium text-ink/40">
                                    No queues assigned yet.
                                </p>

                                <template v-if="attentionTasks.length">
                                    <div class="my-1 border-t border-ink/[0.06]" />
                                    <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">
                                        Needs attention
                                    </p>
                                    <button
                                        v-for="item in attentionTasks"
                                        :key="item.key"
                                        type="button"
                                        class="flex w-full items-start gap-3 rounded-xl px-3 py-2.5 text-left hover:bg-pale"
                                        @click="openTask(item)"
                                    >
                                        <i
                                            :class="item.icon || 'ti ti-circle'"
                                            class="mt-0.5 text-lg text-ink/40"
                                            aria-hidden="true"
                                        />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-ink">{{ item.title }}</span>
                                            <span class="mt-0.5 block truncate text-[11px] font-medium text-ink/40">
                                                {{ item.subtitle }}
                                            </span>
                                        </span>
                                    </button>
                                </template>

                                <div class="my-1 border-t border-ink/[0.06]" />

                                <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">
                                    Performance
                                </p>
                                <Link
                                    :href="route('admin.insights.index')"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-pale"
                                    :class="isInsights ? 'bg-tint text-deep' : ''"
                                >
                                    <i class="ti ti-chart-bar text-lg text-ink/40" aria-hidden="true" />
                                    My stats
                                </Link>
                                <Link
                                    v-if="canSupport"
                                    :href="route('admin.support.reports')"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-pale"
                                    :class="isReports ? 'bg-tint text-deep' : ''"
                                >
                                    <i class="ti ti-report-analytics text-lg text-ink/40" aria-hidden="true" />
                                    Support reports
                                </Link>
                            </div>
                        </template>
                    </Dropdown>

                    <button
                        type="button"
                        class="nav-hit h-10 w-10"
                        aria-label="Notifications"
                        @click="inboxOpen = true"
                    >
                        <i class="ti ti-bell text-xl" aria-hidden="true" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-coral px-1 text-[10px] font-bold text-white"
                        >
                            {{ formatBadgeCount(unreadCount) }}
                        </span>
                    </button>

                    <OpsAccountMenu />
                </div>
            </div>

            <div
                v-if="currentTabs.length > 1"
                class="no-scrollbar mx-auto flex w-full max-w-[1400px] gap-1 overflow-x-auto border-t border-ink/[0.05] px-4 pb-2.5 pt-1.5 sm:px-6 lg:px-8"
            >
                <button
                    v-for="tab in currentTabs"
                    :key="tab.label"
                    type="button"
                    class="shrink-0 rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                    :class="
                        tabIsActive(tab, currentRoute, tabQuery)
                            ? 'bg-base-action text-white shadow-sm'
                            : 'text-ink/50 hover:bg-tint hover:text-deep'
                    "
                    :aria-current="tabIsActive(tab, currentRoute, tabQuery) ? 'page' : undefined"
                    @click="selectTab(tab)"
                >
                    {{ tab.label }}
                </button>
            </div>
        </header>

        <main class="px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
            <div class="mx-auto w-full max-w-[1400px] pb-4">
                <div
                    v-if="restricted && !isHome"
                    class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] font-medium text-amber-900"
                >
                    You can sign in, but no roles have been assigned yet. Ask a Super Admin to grant access — this screen stays empty on purpose.
                </div>
                <slot />
            </div>
        </main>

        <OpsNotificationSheet
            :open="inboxOpen"
            :items="unreadItems"
            @close="inboxOpen = false"
        />
    </div>
</template>

<script setup>
import OpsAccountMenu from '@/Components/Admin/OpsAccountMenu.vue';
import OpsNotificationSheet from '@/Components/Admin/OpsNotificationSheet.vue';
import Dropdown from '@/Components/Dropdown.vue';
import {
    activeNavItem,
    canSeeNavTab,
    tabHref,
    tabIsActive,
} from '@/Data/adminNav';
import { useOpsAttentionLive } from '@/Composables/useOpsAttentionLive';
import { formatBadgeCount } from '@/utils/opsStatus';
import { parseQuery } from '@/utils/adminRange';
import { prefetchAdmin, visitAdmin } from '@/utils/adminVisit';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, provide, ref, watch } from 'vue';

defineProps({
    title: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const abilities = computed(() => user.value?.abilities || []);
const restricted = computed(() => !!user.value?.restricted);
const inbox = computed(() => page.props.ops_inbox || {});
const unreadCount = computed(() => Number(inbox.value.unread_count || 0));
const unreadItems = computed(() => inbox.value.items || []);
const shortcuts = computed(() => inbox.value.shortcuts || []);

const inboxOpen = ref(false);

const currentRoute = computed(() => {
    void page.url;

    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const currentPath = computed(() => {
    try {
        return new URL(page.url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch {
        return String(page.url || '').split('?')[0] || '';
    }
});

const isHome = computed(() => currentRoute.value === 'admin.dashboard');
const isTasks = computed(() => currentRoute.value === 'admin.tasks' || isAssigned.value || isMyApprovals.value);
const isAssigned = computed(() => String(currentRoute.value || '').startsWith('admin.assigned'));
const isMyApprovals = computed(() => String(currentRoute.value || '').startsWith('admin.my-approvals'));
const isInsights = computed(() => String(currentRoute.value || '').startsWith('admin.insights'));
const isModerationDesk = computed(() => String(currentRoute.value || '').startsWith('admin.moderation-desk'));
const isReports = computed(() => currentRoute.value === 'admin.support.reports');
const pendingApprovals = computed(() => Number(page.props.my_approvals_pending || 0));
const canModerationDesk = computed(() => {
    if (user.value?.is_super_admin) {
        return true;
    }

    const keys = abilities.value || [];

    return keys.includes('admin.content.manage')
        || keys.includes('admin.moderation.manage')
        || keys.includes('patrol.view')
        || keys.includes('admin.users.view');
});
const canSupport = computed(() =>
    abilities.value.includes('admin.support.manage') || !!user.value?.is_super_admin,
);

const hrefPath = (href) => {
    try {
        return new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch {
        return '';
    }
};

const isQueueActive = (item) => {
    const path = hrefPath(item.href);

    if (!path) {
        return false;
    }

    if (item.key === 'support' && isReports.value) {
        return false;
    }

    return currentPath.value === path || currentPath.value.startsWith(`${path}/`);
};

const tabQuery = ref(parseQuery(page.url));
provide('adminTabQuery', tabQuery);

watch(
    () => page.url,
    (url) => {
        tabQuery.value = parseQuery(url);
        inboxOpen.value = false;
    },
);

const currentItem = computed(() => activeNavItem(currentRoute.value, false, abilities.value));
const currentTabs = computed(() =>
    (currentItem.value?.tabs || []).filter((tab) => canSeeNavTab(tab, false, abilities.value)),
);

const DROPDOWN_QUEUE_KEYS = new Set(['moderation_desk', 'my_approvals', 'ops_messages']);

const queueItems = computed(() =>
    shortcuts.value
        .filter((item) => !DROPDOWN_QUEUE_KEYS.has(item.key))
        .map((item) => ({
        key: item.key,
        label: item.label,
        icon: item.icon || 'ti ti-circle',
        href: item.href,
        active: isQueueActive(item),
        badge: item.key === 'asap'
            ? Math.max(Number(item.count || 0), Number(page.props.asap_unread || 0))
            : (item.count || 0),
        unread: item.key === 'asap' && Number(item.count || page.props.asap_unread || 0) > 0,
    })),
);

const attentionTasks = computed(() => {
    const tasks = inbox.value.tasks;

    if (Array.isArray(tasks)) {
        return tasks.slice(0, 6);
    }

    return (unreadItems.value || [])
        .filter((item) => !['asap', 'ops_chat'].includes(item.group))
        .slice(0, 6);
});

const openTask = (item) => {
    if (item?.href) {
        visitAdmin(item.href);
    }
};

const selectTab = (tab) => {
    if (tab.route === currentRoute.value) {
        tabQuery.value = { ...tabQuery.value, ...(tab.params || {}) };
        window.history.replaceState(window.history.state, '', tabHref(tab));
        return;
    }

    visitAdmin(tabHref(tab));
};

watch(currentTabs, (tabs) => {
    tabs.forEach((tab) => prefetchAdmin(tabHref(tab)));
}, { immediate: true });

useOpsAttentionLive();
</script>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.nav-hit {
    min-height: 44px;
    min-width: 44px;
    @apply relative inline-flex items-center justify-center rounded-xl text-ink/50 transition-colors hover:bg-pale hover:text-ink;
}
</style>
