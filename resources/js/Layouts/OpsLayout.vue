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
                    <BrandMark variant="solid" class="h-8 w-8 shrink-0" />
                    <span class="hidden truncate text-[0.95rem] font-semibold tracking-tight text-ink sm:inline">
                        Kraftrack
                    </span>
                </Link>

                <nav class="hidden min-w-0 flex-1 items-center gap-1 pl-2 sm:flex" aria-label="Sections">
                    <button
                        v-for="hub in hubs"
                        :key="hub.key"
                        type="button"
                        class="nav-hit gap-1.5 px-3"
                        :class="hub.key === activeHubKey ? 'bg-tint text-deep' : ''"
                        :aria-current="hub.key === activeHubKey ? 'page' : undefined"
                        @click="goHub(hub)"
                    >
                        <i :class="hub.icon" class="text-[1.05rem]" aria-hidden="true" />
                        <span class="text-[13px] font-semibold">{{ hub.label }}</span>
                        <span
                            v-if="hubBadge(hub) > 0"
                            class="rounded-full bg-coral px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                        >
                            {{ formatBadgeCount(hubBadge(hub)) }}
                        </span>
                    </button>
                </nav>

                <div class="min-w-0 flex-1 sm:hidden" />

                <div class="flex shrink-0 items-center gap-0.5 sm:gap-1">
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
        </header>

        <OpsSectionBar
            :pages="hubPages"
            :active-page-key="currentItemKey"
            :tabs="subTabs"
            :current-route="currentRoute"
            :tab-query="tabQuery"
            @select-page="goPage"
            @select-tab="selectTab"
        />

        <main class="px-4 py-5 pb-24 sm:px-6 sm:pb-8 lg:px-8 lg:py-8">
            <div class="mx-auto w-full max-w-[1400px]">
                <div
                    v-if="restricted && !isHome"
                    class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] font-medium text-amber-900"
                >
                    You can sign in, but no roles have been assigned yet. Ask a Super Admin to grant access — this screen stays empty on purpose.
                </div>
                <slot />
            </div>
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-30 grid border-t border-ink/[0.08] bg-white/95 backdrop-blur-xl sm:hidden"
            :style="{ gridTemplateColumns: `repeat(${hubs.length}, minmax(0, 1fr))`, paddingBottom: 'env(safe-area-inset-bottom)' }"
            aria-label="Sections"
        >
            <button
                v-for="hub in hubs"
                :key="hub.key"
                type="button"
                class="relative flex flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] font-semibold transition-colors"
                :class="hub.key === activeHubKey ? 'text-base-action' : 'text-ink/45'"
                :aria-current="hub.key === activeHubKey ? 'page' : undefined"
                @click="goHub(hub)"
            >
                <i :class="hub.icon" class="text-[1.35rem]" aria-hidden="true" />
                <span>{{ hub.label }}</span>
                <span
                    v-if="hubBadge(hub) > 0"
                    class="absolute right-[22%] top-1.5 h-2 w-2 rounded-full bg-coral"
                />
            </button>
        </nav>

        <OpsNotificationSheet
            :open="inboxOpen"
            :items="unreadItems"
            @close="inboxOpen = false"
        />
    </div>
</template>

<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import OpsAccountMenu from '@/Components/Admin/OpsAccountMenu.vue';
import OpsNotificationSheet from '@/Components/Admin/OpsNotificationSheet.vue';
import OpsSectionBar from '@/Components/Admin/OpsSectionBar.vue';
import {
    activeNavItem,
    activeOpsHub,
    canSeeNavTab,
    navItemHref,
    opsHubHref,
    tabHref,
    visibleOpsHubs,
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

const inboxOpen = ref(false);

const currentRoute = computed(() => {
    void page.url;

    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const isHome = computed(() => currentRoute.value === 'admin.dashboard');

const tabQuery = ref(parseQuery(page.url));
provide('adminTabQuery', tabQuery);

watch(
    () => page.url,
    (url) => {
        tabQuery.value = parseQuery(url);
        inboxOpen.value = false;
    },
);

const hubs = computed(() => visibleOpsHubs(false, abilities.value));
const activeHub = computed(() => activeOpsHub(currentRoute.value, false, abilities.value));
const activeHubKey = computed(() => activeHub.value?.key || '');
const hubPages = computed(() => activeHub.value?.pages || []);

const currentItem = computed(() => activeNavItem(currentRoute.value, false, abilities.value));
const currentItemKey = computed(() => currentItem.value?.key || '');
const subTabs = computed(() =>
    (currentItem.value?.tabs || []).filter((tab) => canSeeNavTab(tab, false, abilities.value)),
);

const hubBadge = (hub) => {
    if (hub.key === 'comms') {
        return Number(page.props.asap_unread || 0);
    }
    if (hub.key === 'queues') {
        return Number(page.props.my_approvals_pending || 0);
    }
    return 0;
};

const goHub = (hub) => {
    if (hub.key === activeHubKey.value) {
        return;
    }
    visitAdmin(opsHubHref(hub, false, abilities.value));
};

const goPage = (item) => {
    if (item.key === currentItemKey.value) {
        return;
    }
    visitAdmin(navItemHref(item, false, abilities.value));
};

const selectTab = (tab) => {
    if (tab.route === currentRoute.value) {
        tabQuery.value = { ...tabQuery.value, ...(tab.params || {}) };
        window.history.replaceState(window.history.state, '', tabHref(tab));
        return;
    }

    visitAdmin(tabHref(tab));
};

watch(hubPages, (pages) => {
    pages.forEach((item) => prefetchAdmin(navItemHref(item, false, abilities.value)));
}, { immediate: true });

watch(subTabs, (tabs) => {
    tabs.forEach((tab) => prefetchAdmin(tabHref(tab)));
}, { immediate: true });

useOpsAttentionLive();
</script>

<style scoped>
.nav-hit {
    min-height: 44px;
    @apply relative inline-flex items-center justify-center rounded-xl text-ink/50 transition-colors hover:bg-pale hover:text-ink;
}
</style>
