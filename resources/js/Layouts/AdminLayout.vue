<template>
    <div class="admin-shell min-h-dvh bg-[#F4F6FA] font-admin text-ink antialiased">
        <!-- Mobile overlay -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <button
                v-if="mobileOpen"
                type="button"
                class="fixed inset-0 z-40 bg-ink/40 backdrop-blur-[2px] lg:hidden"
                aria-label="Close menu"
                @click="mobileOpen = false"
            />
        </Transition>

        <aside
            class="admin-sidebar fixed inset-y-0 left-0 z-50 flex flex-col border-r border-ink/[0.07] bg-white transition-[width,transform] duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="[
                collapsed ? 'lg:w-[4.75rem]' : 'lg:w-[16.25rem]',
                mobileOpen ? 'translate-x-0 w-[16.25rem]' : '-translate-x-full w-[16.25rem] lg:translate-x-0',
            ]"
        >
            <div class="flex h-[3.75rem] items-center gap-2.5 border-b border-ink/[0.06] px-3.5">
                <Link
                    :href="route('admin.dashboard')"
                    class="flex min-w-0 items-center gap-2.5"
                    @click="mobileOpen = false"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-ink text-[0.7rem] font-extrabold tracking-tight text-white"
                    >
                        I
                    </span>
                    <span
                        v-show="!collapsed || mobileOpen"
                        class="truncate text-[0.95rem] font-semibold tracking-tight text-ink"
                    >
                        Isabi
                    </span>
                </Link>
            </div>

            <nav class="flex-1 overflow-y-auto px-2.5 py-3">
                <div
                    v-for="group in visibleGroups"
                    :key="group.label"
                    class="mb-4"
                >
                    <p
                        v-show="!collapsed || mobileOpen"
                        class="px-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-ink/35"
                    >
                        {{ group.label }}
                    </p>
                    <div class="space-y-0.5">
                        <Link
                            v-for="item in group.items"
                            :key="item.key"
                            :href="navItemHref(item, isSuper, abilities)"
                            prefetch
                            cache-for="5m"
                            :show-progress="false"
                            class="group relative flex items-center gap-3 rounded-xl px-2.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.98]"
                            :class="
                                isItemActive(item)
                                    ? 'bg-tint text-deep'
                                    : 'text-ink/50 hover:bg-pale hover:text-ink'
                            "
                            :title="collapsed && !mobileOpen ? item.label : undefined"
                            @click="mobileOpen = false"
                        >
                            <i
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[1.15rem]"
                                :class="[
                                    item.icon,
                                    isItemActive(item) ? 'bg-white text-base-action shadow-sm' : 'text-ink/40 group-hover:text-ink/70',
                                ]"
                                aria-hidden="true"
                            />
                            <span v-show="!collapsed || mobileOpen" class="truncate">{{ item.label }}</span>
                            <span
                                v-if="item.key === 'asap' && asapUnread > 0 && (!collapsed || mobileOpen)"
                                class="ms-auto rounded-full bg-base-action px-1.5 py-0.5 text-[10px] font-extrabold text-white"
                            >
                                {{ asapUnread > 9 ? '9+' : asapUnread }}
                            </span>
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="border-t border-ink/[0.06] p-2.5">
                <button
                    type="button"
                    class="mb-1 hidden w-full items-center gap-3 rounded-xl px-2.5 py-2 text-[13px] font-semibold text-ink/45 transition-colors hover:bg-ink/[0.035] hover:text-ink lg:flex"
                    :title="collapsed ? 'Expand menu' : 'Collapse menu'"
                    @click="toggleCollapsed"
                >
                    <i
                        class="flex h-8 w-8 items-center justify-center text-lg"
                        :class="collapsed ? 'ti ti-layout-sidebar-right-expand' : 'ti ti-layout-sidebar-left-collapse'"
                        aria-hidden="true"
                    />
                    <span v-show="!collapsed">Collapse</span>
                </button>
                <div class="flex items-center gap-2.5 rounded-xl px-2.5 py-2">
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-tint text-[11px] font-bold text-deep"
                    >
                        {{ initials }}
                    </span>
                    <div v-show="!collapsed || mobileOpen" class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-bold text-ink">{{ user?.name }}</p>
                        <p class="truncate text-[11px] font-medium text-ink/40">{{ user?.role_label }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div
            class="flex min-h-dvh flex-col transition-[padding] duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="collapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-[16.25rem]'"
        >
            <header
                class="sticky top-0 z-30 border-b border-ink/[0.06] bg-white/90 backdrop-blur-xl"
            >
                <div class="flex h-[3.75rem] items-center gap-3 px-4 sm:px-6">
                    <button
                        type="button"
                        class="tap-target -ml-1 flex h-10 w-10 items-center justify-center rounded-xl text-ink/50 hover:bg-pale hover:text-ink lg:hidden"
                        aria-label="Open menu"
                        @click="mobileOpen = true"
                    >
                        <i class="ti ti-menu-2 text-xl" aria-hidden="true" />
                    </button>
                    <Link
                        v-if="backHref"
                        :href="backHref"
                        prefetch
                        cache-for="5m"
                        :show-progress="false"
                        class="tap-target flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-ink/50 transition-colors hover:bg-pale hover:text-ink"
                        :aria-label="backLabel"
                    >
                        <i class="ti ti-arrow-left text-xl" aria-hidden="true" />
                    </Link>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[15px] font-bold tracking-tight text-ink">
                            {{ heading }}
                        </p>
                        <p v-if="subheading" class="hidden truncate text-[11px] font-medium text-ink/40 sm:block">
                            {{ subheading }}
                        </p>
                    </div>
                    <Link
                        v-if="user?.is_staff"
                        :href="route('admin.notices.index')"
                        class="tap-target inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold text-ink/55 transition-colors hover:bg-pale hover:text-ink"
                    >
                        Notices
                        <span
                            v-if="pendingNotices > 0"
                            class="rounded-full bg-tint px-1.5 py-0.5 text-[10px] font-bold text-deep"
                        >{{ pendingNotices }}</span>
                    </Link>
                    <NotificationBell />
                    <Link
                        :href="route('home')"
                        class="hidden items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold text-ink/45 transition-colors hover:bg-pale hover:text-ink sm:inline-flex"
                    >
                        View site
                    </Link>
                    <Link
                        :href="route('admin.logout')"
                        method="post"
                        as="button"
                        class="tap-target inline-flex items-center justify-center rounded-xl px-3 text-xs font-semibold text-ink/45 transition-colors hover:bg-pale hover:text-ink"
                    >
                        Log out
                    </Link>
                </div>

                <div
                    v-if="currentTabs.length > 1"
                    class="no-scrollbar flex gap-1 overflow-x-auto px-4 pb-2.5 sm:px-6 lg:justify-end"
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

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-[1400px] pb-20 lg:pb-0">
                    <div
                        v-if="restricted"
                        class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] font-medium text-amber-900"
                    >
                        You can sign in, but no roles have been assigned yet. Ask a Super Admin to grant access — this screen stays empty on purpose.
                    </div>
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile bottom nav -->
        <nav
            class="fixed inset-x-0 bottom-0 z-30 border-t border-ink/[0.08] bg-white/95 backdrop-blur-xl lg:hidden"
            style="padding-bottom: env(safe-area-inset-bottom)"
        >
            <div class="grid grid-cols-5 px-1 pt-1">
                <Link
                    v-for="item in mobileItems"
                    :key="item.key"
                    :href="route(item.route)"
                    prefetch
                    cache-for="5m"
                    :show-progress="false"
                    class="flex flex-col items-center gap-0.5 py-2 text-[10px] font-bold transition-colors active:scale-[0.96]"
                    :class="isItemActive(item) ? 'text-base-action' : 'text-ink/40'"
                >
                    <i :class="item.icon" class="text-xl" aria-hidden="true" />
                    {{ item.shortLabel || item.label }}
                </Link>
                <button
                    type="button"
                    class="flex flex-col items-center gap-0.5 py-2 text-[10px] font-bold"
                    :class="moreActive ? 'text-base-action' : 'text-ink/40'"
                    @click="mobileOpen = true"
                >
                    <i class="ti ti-dots text-xl" aria-hidden="true" />
                    More
                </button>
            </div>
        </nav>
    </div>
</template>

<script setup>
import {
    activeNavItem,
    adminNavGroups,
    canSeeNavItem,
    canSeeNavTab,
    flattenAdminNav,
    mobilePrimaryNav,
    navItemHref,
    tabHref,
    tabIsActive,
} from '@/Data/adminNav';
import { adminChrome } from '@/Composables/useAdminChrome';
import NotificationBell from '@/Components/App/NotificationBell.vue';
import { parseQuery } from '@/utils/adminRange';
import { prefetchAdmin, prefetchAdminSoon, visitAdmin } from '@/utils/adminVisit';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, provide, ref, watch } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuper = computed(() => !!user.value?.is_super_admin);
const abilities = computed(() => user.value?.abilities || []);
const restricted = computed(() => !!user.value?.restricted);
const initials = computed(() => user.value?.initials || 'I');
const asapUnread = computed(() => Number(page.props.asap_unread || 0));
const collapsed = ref(false);
const mobileOpen = ref(false);
const heading = computed(() => adminChrome.title || props.title || 'Admin');
const subheading = computed(() => adminChrome.eyebrow || props.eyebrow || '');
const backHref = computed(() => adminChrome.backHref || '');
const backLabel = computed(() => adminChrome.backLabel || 'Back');

const currentRoute = computed(() => {
    void page.url;

    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const tabQuery = ref(parseQuery(page.url));
provide('adminTabQuery', tabQuery);

watch(
    () => page.url,
    (url) => {
        tabQuery.value = parseQuery(url);
    },
);

const visibleGroups = computed(() =>
    adminNavGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => canSeeNavItem(item, isSuper.value, abilities.value)),
        }))
        .filter((group) => group.items.length > 0),
);

const currentItem = computed(() => activeNavItem(currentRoute.value, isSuper.value, abilities.value));
const currentTabs = computed(() =>
    (currentItem.value?.tabs || []).filter((tab) => canSeeNavTab(tab, isSuper.value, abilities.value)),
);

const pendingNotices = computed(() => Number(user.value?.pending_notices || 0));

const mobileItems = computed(() =>
    flattenAdminNav(isSuper.value, abilities.value).filter((item) => mobilePrimaryNav.includes(item.key)),
);

const moreActive = computed(() => {
    const key = currentItem.value?.key;
    return key && !mobilePrimaryNav.includes(key);
});

const isItemActive = (item) => currentItem.value?.key === item.key;

const selectTab = (tab) => {
    if (tab.route === currentRoute.value) {
        tabQuery.value = { ...(tab.params || {}) };
        window.history.replaceState(window.history.state, '', tabHref(tab));
        return;
    }

    visitAdmin(tabHref(tab));
};

const prefetchCurrentTabs = () => {
    currentTabs.value.forEach((tab) => prefetchAdmin(tabHref(tab)));
};

const prefetchSidebar = () => {
    const hrefs = flattenAdminNav(isSuper.value, abilities.value).map((item) => route(item.route));

    prefetchAdminSoon(hrefs, 250);
};

const toggleCollapsed = () => {
    collapsed.value = !collapsed.value;
    try {
        localStorage.setItem('isabi:admin-nav-collapsed', collapsed.value ? '1' : '0');
    } catch {
        // ignore
    }
};

watch(currentTabs, prefetchCurrentTabs, { immediate: true });

onMounted(() => {
    try {
        collapsed.value = localStorage.getItem('isabi:admin-nav-collapsed') === '1';
    } catch {
        // ignore
    }

    prefetchCurrentTabs();
    prefetchSidebar();
});
</script>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
