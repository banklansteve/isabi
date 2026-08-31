<template>
    <Head title="Quotes" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-3xl">
            <section
                class="relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:px-7 sm:py-8"
            >
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55">Client pipeline</p>
                    <h1 class="mt-2 font-editorial text-[2rem] font-semibold tracking-tight text-white sm:text-[2.2rem]">
                        Quotes
                    </h1>
                    <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/65">
                        One thread per enquiry — from first request through to accepted work.
                    </p>
                </div>
            </section>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="tap-target rounded-full px-3.5 py-2 text-sm font-bold transition-all"
                    :class="
                        filters.tab === tab.key
                            ? 'bg-tint text-base-action ring-1 ring-base/20'
                            : 'text-ink/50 hover:bg-pale hover:text-ink'
                    "
                    @click="setTab(tab.key)"
                >
                    {{ tab.label }}
                    <span v-if="tab.count" class="opacity-70">· {{ tab.count }}</span>
                </button>

                <div class="ms-auto">
                    <button
                        type="button"
                        class="tap-target inline-flex h-10 w-10 items-center justify-center rounded-xl text-ink/40 hover:bg-pale hover:text-ink"
                        aria-label="Search and sort"
                        @click="filtersOpen = !filtersOpen"
                    >
                        <i class="ti ti-dots text-lg" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div
                v-if="filtersOpen"
                class="mb-4 space-y-3 rounded-[1.35rem] bg-white p-4 shadow-premium ring-1 ring-ink/[0.06]"
            >
                <div class="relative">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" aria-hidden="true" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search quotes…"
                        class="w-full rounded-xl border border-ink/10 bg-pale py-3 ps-10 pe-3 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <select
                    v-model="sort"
                    class="w-full rounded-xl border border-ink/10 bg-pale px-3 py-2.5 text-sm font-semibold outline-none focus:border-base focus:ring-2 focus:ring-base/15"
                    @change="applyFilters"
                >
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                    <option value="name">Client name</option>
                </select>
            </div>

            <div v-if="quotes.data.length" class="overflow-hidden rounded-[1.35rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <ul class="divide-y divide-ink/[0.06]">
                    <li v-for="row in quotes.data" :key="row.uid">
                        <Link
                            :href="row.show_url"
                            class="tap-target flex items-start justify-between gap-4 px-5 py-4 transition-colors hover:bg-pale/60 sm:px-6 sm:py-5"
                        >
                            <div class="min-w-0">
                                <p class="font-semibold tracking-tight text-ink">{{ row.name }}</p>
                                <p class="mt-0.5 truncate text-sm font-medium text-ink/45">{{ row.subject }}</p>
                                <p
                                    v-if="row.should_log_job"
                                    class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-base-action"
                                >
                                    <i class="ti ti-briefcase" aria-hidden="true" />
                                    Ready to log as a job
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                :class="badgeClass(row.status_tone)"
                            >
                                {{ row.status_label }}
                            </span>
                        </Link>
                    </li>
                </ul>
                <div class="border-t border-ink/[0.06] px-4 py-3">
                    <AppPagination :links="quotes.links" />
                </div>
            </div>

            <AppEmptyState
                v-else
                icon="ti ti-inbox"
                title="No quotes here yet"
                description="When someone requests a quote on your public page, it will appear in this list."
                cta-label="View my page"
                :cta-href="route('page.index')"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AppPagination from '@/Components/App/AppPagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    quotes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q || '');
const sort = ref(props.filters.sort || 'newest');
const filtersOpen = ref(false);

watch(
    () => props.filters,
    (value) => {
        search.value = value.q || '';
        sort.value = value.sort || 'newest';
    },
);

const tabs = computed(() => [
    { key: 'needs_response', label: 'Needs response', count: props.counts.needs_response ?? 0 },
    { key: 'awaiting_client', label: 'Awaiting client', count: props.counts.awaiting_client ?? 0 },
    { key: 'accepted', label: 'Accepted', count: props.counts.accepted ?? 0 },
    { key: 'all', label: 'All', count: props.counts.all ?? 0 },
]);

const badgeClass = (tone) =>
    ({
        rose: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80',
        blue: 'bg-tint text-base-action ring-1 ring-base/15',
        amber: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200/70',
        emerald: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/80',
        slate: 'text-ink/40',
    })[tone] || 'bg-pale text-ink/45';

const applyFilters = () => {
    router.get(
        route('quotes.index'),
        {
            q: search.value || undefined,
            tab: props.filters.tab !== 'needs_response' ? props.filters.tab : undefined,
            sort: sort.value !== 'newest' ? sort.value : undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const setTab = (tab) => {
    router.get(
        route('quotes.index'),
        {
            q: search.value || undefined,
            tab: tab !== 'needs_response' ? tab : undefined,
            sort: sort.value !== 'newest' ? sort.value : undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};
</script>
