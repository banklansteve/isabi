<template>
    <Head title="Quotes" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl pb-10">
            <section
                class="relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.18]"
                    style="
                        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                        background-size: 18px 18px;
                    "
                    aria-hidden="true"
                />

                <div class="relative">
                    <p class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55">
                        <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                        Client pipeline
                    </p>
                    <h1 class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]">
                        Quotes
                    </h1>
                    <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/65">
                        One thread per enquiry — search, filter, and sort from first request to accepted work.
                    </p>
                </div>

                <div class="relative mt-7 grid grid-cols-2 gap-2 sm:mt-8 sm:grid-cols-4 sm:gap-3">
                    <div
                        v-for="stat in heroStats"
                        :key="stat.label"
                        class="rounded-2xl bg-white/[0.07] px-3 py-3 ring-1 ring-white/10 sm:px-4 sm:py-3.5"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            {{ stat.label }}
                        </p>
                        <p class="mt-1 font-display text-xl font-extrabold tabular-nums text-white sm:text-2xl">
                            {{ stat.value }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="mb-5 space-y-3 rounded-[1.5rem] bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-5">
                <div class="relative">
                    <i
                        class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35"
                        aria-hidden="true"
                    />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search client, subject, quote ID, phone…"
                        class="w-full rounded-xl border border-ink/10 bg-pale py-3 ps-10 pe-3 text-sm font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                        @keydown.enter.prevent="applyFilters()"
                    />
                </div>

                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                        :class="
                            filters.tab === tab.key && !filters.status && !filters.focus
                                ? 'bg-ink text-white shadow-sm'
                                : 'bg-pale text-ink/55 hover:bg-tint hover:text-ink'
                        "
                        @click="setTab(tab.key)"
                    >
                        {{ tab.label }}
                        <span class="opacity-70">· {{ tab.count }}</span>
                    </button>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    <button
                        type="button"
                        class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                        :class="
                            filters.focus === 'ready_to_log'
                                ? 'bg-base-action text-white shadow-sm'
                                : 'bg-tint text-base-action ring-1 ring-base/15 hover:bg-base-action/10'
                        "
                        @click="toggleFocus('ready_to_log')"
                    >
                        <i class="ti ti-briefcase me-1 text-[12px]" aria-hidden="true" />
                        Ready to log
                        <span v-if="counts.ready_to_log" class="opacity-80">· {{ counts.ready_to_log }}</span>
                    </button>
                    <button
                        type="button"
                        class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                        :class="
                            filters.focus === 'expiring'
                                ? 'bg-amber-900 text-white shadow-sm'
                                : 'bg-amber-50 text-amber-900/80 ring-1 ring-amber-200/80 hover:bg-amber-100'
                        "
                        @click="toggleFocus('expiring')"
                    >
                        <i class="ti ti-clock-hour-4 me-1 text-[12px]" aria-hidden="true" />
                        Expiring soon
                        <span v-if="counts.expiring" class="opacity-80">· {{ counts.expiring }}</span>
                    </button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <FormSelect
                        id="quotes-status"
                        v-model="status"
                        label="Status"
                        icon="ti ti-filter"
                        placeholder="Any status"
                        :options="filterOptions.statuses"
                        @change="applyFilters()"
                    />
                    <FormSelect
                        id="quotes-sort"
                        v-model="sort"
                        label="Sort by"
                        icon="ti ti-arrows-sort"
                        placeholder="Newest first"
                        :options="filterOptions.sorts"
                        @change="applyFilters()"
                    />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold text-ink/35">
                        Showing {{ quotes.data.length }} of {{ quotes.total }} quote{{ quotes.total === 1 ? '' : 's' }}
                    </p>
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="tap-target rounded-xl px-3 py-2 text-xs font-semibold text-ink/45 transition-colors hover:bg-pale hover:text-ink"
                        @click="clearFilters"
                    >
                        Clear filters
                    </button>
                </div>
            </div>

            <AppEmptyState
                v-if="!quotes.data.length && (counts.all ?? 0) === 0"
                icon="ti ti-file-invoice"
                title="No quotes here yet"
                description="Share your public page — new quote requests will appear in this list."
                cta-label="View my page"
                :cta-href="route('page.index')"
            />

            <AppEmptyState
                v-else-if="!quotes.data.length && hasActiveFilters"
                icon="ti ti-filter-off"
                title="No matching quotes"
                description="Try another search or clear filters to see everything."
                cta-label="Clear filters"
                @action="clearFilters"
            />

            <AppEmptyState
                v-else-if="!quotes.data.length"
                icon="ti ti-inbox"
                title="Nothing in this view"
                description="No quotes match this pipeline stage right now."
                cta-label="View all quotes"
                @action="setTab('all')"
            />

            <ul v-else class="space-y-3">
                <li
                    v-for="(row, index) in quotes.data"
                    :key="row.uid"
                    class="quote-row"
                    :style="{ animationDelay: `${Math.min(index, 8) * 40}ms` }"
                >
                    <Link
                        :href="row.show_url"
                        class="group flex items-start gap-3.5 rounded-[1.35rem] bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-hover hover:ring-base-action/20 sm:gap-5 sm:p-5"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-pale text-base-action ring-1 ring-ink/[0.05] transition-colors group-hover:bg-tint sm:h-12 sm:w-12"
                        >
                            <i class="ti ti-file-invoice text-lg" aria-hidden="true" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[0.98rem] font-semibold tracking-tight text-ink sm:text-[1.02rem]">
                                        {{ row.name }}
                                    </p>
                                    <p class="mt-0.5 truncate text-sm font-medium text-ink/45">
                                        {{ row.subject }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                    :class="badgeClass(row.status_tone)"
                                >
                                    {{ row.status_label }}
                                </span>
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                <span
                                    v-if="row.quote_number"
                                    class="inline-flex items-center gap-1 rounded-full bg-tint px-2.5 py-1 font-mono text-[10px] font-bold text-deep"
                                >
                                    {{ row.quote_number }}
                                </span>
                                <span
                                    v-if="row.total_label"
                                    class="inline-flex items-center rounded-full bg-pale px-2.5 py-1 text-[11px] font-bold tabular-nums text-ink/70"
                                >
                                    {{ row.total_label }}
                                </span>
                                <span
                                    v-if="row.valid_until_label"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="
                                        row.expiring_soon
                                            ? 'bg-amber-50 text-amber-900 ring-1 ring-amber-200/80'
                                            : 'bg-pale text-ink/45'
                                    "
                                >
                                    <i class="ti ti-calendar-event text-[12px]" aria-hidden="true" />
                                    Valid {{ row.valid_until_label }}
                                </span>
                                <span class="text-[11px] font-semibold text-ink/35">
                                    {{ row.updated_relative || row.submitted_relative }}
                                </span>
                            </div>

                            <p
                                v-if="row.should_log_job"
                                class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-bold text-base-action"
                            >
                                <i class="ti ti-briefcase" aria-hidden="true" />
                                Ready to log as a job
                            </p>
                        </div>

                        <i
                            class="ti ti-chevron-right mt-1 shrink-0 text-ink/20 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:text-base-action"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>

            <div v-if="quotes.data.length && quotes.links?.length > 3" class="mt-5">
                <AppPagination :links="quotes.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AppPagination from '@/Components/App/AppPagination.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    quotes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
    filterOptions: {
        type: Object,
        default: () => ({
            statuses: [],
            sorts: [],
        }),
    },
});

const search = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const sort = ref(props.filters.sort || 'newest');
let searchTimer = null;

watch(
    () => props.filters,
    (value) => {
        search.value = value.q || '';
        status.value = value.status || '';
        sort.value = value.sort || 'newest';
    },
);

watch(search, (value) => {
    if (value === (props.filters.q || '')) {
        return;
    }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 320);
});

const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.counts.all ?? 0 },
    { key: 'accepted', label: 'Accepted', count: props.counts.accepted ?? 0 },
    { key: 'awaiting_client', label: 'Waiting', count: props.counts.awaiting_client ?? 0 },
    { key: 'needs_response', label: 'Needs you', count: props.counts.needs_response ?? 0 },
    { key: 'closed', label: 'Closed', count: props.counts.closed ?? 0 },
]);

const heroStats = computed(() => [
    { label: 'All', value: props.counts.all ?? 0 },
    { label: 'Accepted', value: props.counts.accepted ?? 0 },
    { label: 'Waiting', value: props.counts.awaiting_client ?? 0 },
    { label: 'Needs you', value: props.counts.needs_response ?? 0 },
]);

const hasActiveFilters = computed(() => {
    const f = props.filters;
    return Boolean(
        f.q
        || f.status
        || f.focus
        || (f.sort && f.sort !== 'newest')
        || (f.tab && f.tab !== 'all'),
    );
});

const badgeClass = (tone) =>
    ({
        rose: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80',
        blue: 'bg-tint text-base-action ring-1 ring-base/15',
        amber: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200/70',
        emerald: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/80',
        slate: 'bg-pale text-ink/45',
    })[tone] || 'bg-pale text-ink/45';

const queryPayload = (overrides = {}) => {
    const next = {
        q: search.value.trim() || undefined,
        tab: props.filters.tab || 'all',
        status: status.value || undefined,
        focus: props.filters.focus || undefined,
        sort: sort.value !== 'newest' ? sort.value : undefined,
        ...overrides,
    };

    if (next.tab === 'all') {
        delete next.tab;
    }
    if (!next.status) {
        delete next.status;
    }
    if (!next.focus) {
        delete next.focus;
    }
    if (!next.q) {
        delete next.q;
    }
    if (!next.sort) {
        delete next.sort;
    }

    return next;
};

const applyFilters = (overrides = {}) => {
    router.get(route('quotes.index'), queryPayload(overrides), {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

const setTab = (tab) => {
    status.value = '';
    applyFilters({ tab, status: undefined, focus: undefined });
};

const toggleFocus = (focus) => {
    const next = props.filters.focus === focus ? undefined : focus;
    applyFilters({
        focus: next,
        tab: next ? 'all' : props.filters.tab,
        status: undefined,
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    sort.value = 'newest';
    router.get(
        route('quotes.index'),
        {},
        { preserveState: true, replace: true, preserveScroll: true },
    );
};
</script>

<style scoped>
.quote-row {
    animation: quote-row-in 0.35s ease both;
}

@keyframes quote-row-in {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
