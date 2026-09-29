<template>
    <Head :title="pageTitle" />

    <AdminChrome :title="pageTitle" :eyebrow="eyebrow" />

    <div class="space-y-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                    {{ pageTitle }}
                </h1>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                    {{ pageHint }}
                </p>
            </div>
        </header>

        <div
            v-if="queue === 'dormant'"
            class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1"
        >
            <button
                v-for="chip in windowChips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="window === chip.key ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                @click="selectWindow(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="window === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

        <form
            class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
            @submit.prevent="applySearch"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search name, business, email, WhatsApp, trade…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <div class="flex shrink-0 gap-2">
                    <button
                        type="submit"
                        class="tap-target inline-flex h-[42px] items-center justify-center rounded-xl bg-base-action px-4 text-[13px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover"
                    >
                        Search
                    </button>
                    <button
                        type="button"
                        class="tap-target inline-flex h-[42px] items-center justify-center rounded-xl bg-pale px-4 text-[13px] font-semibold text-ink/60 hover:bg-tint hover:text-deep"
                        @click="resetSearch"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </form>

        <AdminPulseUserList
            :title="listTitle"
            :hint="listHint"
            :people="people.data || []"
            :empty-label="emptyLabel"
            :categories="outreachCategories"
            :outreach-title="outreachTitle"
        />

        <AdminPagination :links="people.links || []" />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import AdminPulseUserList from '@/Components/Admin/AdminPulseUserList.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    queue: { type: String, default: 'dormant' },
    people: { type: Object, default: () => ({ data: [], links: [] }) },
    counts: { type: Object, default: () => ({}) },
    window: { type: String, default: '30' },
    windows: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const query = ref(props.filters.q || '');

watch(
    () => props.filters.q,
    (value) => {
        query.value = value || '';
    },
);

const isDormant = computed(() => props.queue === 'dormant');

const pageTitle = computed(() =>
    isDormant.value ? 'Dormant / at-risk artisans' : 'Single-session users',
);

const pageHint = computed(() =>
    isDormant.value
        ? 'Artisans with no job logged or review activity for 30+ days — including signup-only accounts. Short gaps after real work are normal and stay off this list.'
        : 'Signed up 30+ days ago, did one thing (or nothing), and never came back — an onboarding signal.',
);

const eyebrow = computed(() => {
    const total = props.people?.total ?? props.people?.data?.length ?? 0;
    if (isDormant.value) {
        return `${total} in the ${props.window}+ day window`;
    }
    return total ? `${total} one-and-done accounts` : 'No single-session drop-offs';
});

const listTitle = computed(() =>
    isDormant.value ? `Quiet for ${props.window}+ days` : 'One-and-done signups',
);

const listHint = computed(() =>
    isDormant.value
        ? 'WhatsApp, email, or in-app — then open Audit for last activity.'
        : 'Reach out early; these accounts rarely come back without a nudge.',
);

const emptyLabel = computed(() =>
    isDormant.value
        ? 'No artisans look dormant in this window'
        : 'No single-session drop-offs right now',
);

const outreachCategories = computed(() =>
    isDormant.value ? ['dormant'] : ['onboarding', 'dormant'],
);

const outreachTitle = computed(() =>
    isDormant.value ? 'Dormant follow-up' : 'Onboarding nudge',
);

const windowChips = computed(() =>
    (props.windows || []).map((key) => ({
        key,
        label: `${key}+ days quiet`,
        count: props.counts[key] ?? 0,
    })),
);

const listRoute = computed(() =>
    isDormant.value ? 'admin.patrol.dormant' : 'admin.patrol.single-session',
);

const visit = (params = {}) => {
    router.get(
        route(listRoute.value, {
            ...(isDormant.value ? { window: props.window } : {}),
            ...(query.value ? { q: query.value } : {}),
            ...params,
        }),
        {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const selectWindow = (key) => {
    if (key === props.window) return;
    visit({ window: key });
};

const applySearch = () => visit();

const resetSearch = () => {
    query.value = '';
    visit({ q: '' });
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
