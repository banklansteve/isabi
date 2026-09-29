<template>
    <nav
        v-if="tabs.length > 1"
        class="mb-5 overflow-hidden rounded-2xl bg-white p-1.5 shadow-premium ring-1 ring-ink/[0.05]"
        aria-label="Section views"
    >
        <div class="no-scrollbar flex gap-1 overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.label"
                type="button"
                class="shrink-0 rounded-xl px-4 py-2.5 text-[13px] font-semibold transition-all duration-150 active:scale-[0.98]"
                :class="
                    isActive(tab)
                        ? 'bg-base-action text-white shadow-[0_8px_20px_-10px_rgba(26,79,181,0.55)]'
                        : 'text-ink/50 hover:bg-[#F4F6FA] hover:text-ink'
                "
                :aria-current="isActive(tab) ? 'page' : undefined"
                @click="select(tab)"
            >
                {{ tab.label }}
            </button>
        </div>
    </nav>
</template>

<script setup>
import { tabHref, tabIsActive } from '@/Data/adminNav';
import { visitAdmin } from '@/utils/adminVisit';
import { usePage } from '@inertiajs/vue3';
import { computed, inject } from 'vue';

const props = defineProps({
    tabs: { type: Array, default: () => [] },
});

const page = usePage();
const tabQuery = inject('adminTabQuery', null);

const currentRoute = computed(() => {
    void page.url;

    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const query = computed(() => tabQuery?.value || {});

const isActive = (tab) => tabIsActive(tab, currentRoute.value, query.value);

const SERVER_TAB_KEYS = ['kind', 'window', 'segment', 'filter', 'q'];

const select = (tab) => {
    if (isActive(tab)) {
        return;
    }

    const params = tab.params || {};

    if (tabQuery) {
        tabQuery.value = { ...params };
    }

    const needsServer = tab.route !== currentRoute.value
        || Object.keys(params).some((key) => SERVER_TAB_KEYS.includes(key));

    if (!needsServer) {
        window.history.replaceState(window.history.state, '', tabHref(tab));
        return;
    }

    visitAdmin(tabHref(tab), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
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
