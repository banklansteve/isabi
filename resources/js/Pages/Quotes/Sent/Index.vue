<template>
    <Head title="My quotes" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl">
            <section
                class="relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55">
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Your proposals
                        </p>
                        <h1 class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]">
                            My quotes
                        </h1>
                        <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/65">
                            Drafts and finished quotes you’ve built for clients — pick up where you left off any time.
                        </p>
                    </div>

                    <QuoteWorkspaceNav
                        active="sent"
                        :request-count="requestCounts.total"
                        :quote-count="counts.total"
                    />
                </div>

                <div class="relative mt-7 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3">
                    <div
                        v-for="stat in statCards"
                        :key="stat.key"
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

            <section class="mb-5 space-y-3 rounded-[1.5rem] bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-5">
                <div class="relative">
                    <i
                        class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35"
                        aria-hidden="true"
                    />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search by quote #, client, or job…"
                        class="w-full rounded-xl border border-ink/10 bg-pale py-3 ps-10 pe-3 text-sm font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                        @keydown.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            type="button"
                            class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                            :class="
                                filters.status === option.value
                                    ? 'bg-base-action text-white shadow-sm'
                                    : 'bg-pale text-ink/55 hover:bg-tint hover:text-base-action'
                            "
                            @click="setStatus(option.value)"
                        >
                            {{ option.label }}
                            <span v-if="option.count !== null" class="opacity-70">· {{ option.count }}</span>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="flex items-center gap-2 text-xs font-semibold text-ink/45">
                            Sort
                            <select
                                v-model="sort"
                                class="rounded-xl border border-ink/10 bg-pale px-3 py-2 text-sm font-semibold text-ink outline-none focus:border-base focus:ring-2 focus:ring-base/15"
                                @change="applyFilters"
                            >
                                <option value="newest">Recently updated</option>
                                <option value="oldest">Oldest first</option>
                                <option value="total_high">Highest total</option>
                                <option value="total_low">Lowest total</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            class="tap-target rounded-xl bg-base-action px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-base-hover"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </section>

            <div v-if="quotes.data.length" class="space-y-3">
                <article
                    v-for="row in quotes.data"
                    :key="row.uid"
                    class="group overflow-hidden rounded-[1.35rem] bg-white shadow-premium ring-1 ring-ink/[0.06] transition duration-200 hover:ring-base-action/20"
                >
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-display text-sm font-extrabold tracking-wide text-base-action">
                                    {{ row.quote_number }}
                                </p>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.08em]"
                                    :class="
                                        row.status === 'sent'
                                            ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/80'
                                            : 'bg-tint text-base-action ring-1 ring-base/15'
                                    "
                                >
                                    {{ row.status_label }}
                                </span>
                            </div>

                            <h2 class="mt-2 font-editorial text-lg font-semibold tracking-tight text-ink">
                                {{ row.client?.name || 'Client' }}
                            </h2>

                            <p class="mt-1 text-sm font-medium text-ink/45">
                                Updated {{ row.updated_relative }}
                                <span v-if="row.valid_until"> · Valid until {{ row.valid_until }}</span>
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-ink/55">
                                <span
                                    v-if="row.client?.phone"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5"
                                >
                                    <i class="ti ti-phone text-sm text-base-action" aria-hidden="true" />
                                    {{ row.client.phone }}
                                </span>
                                <span
                                    v-if="row.job"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-tint/70 px-3 py-1.5 text-base-action"
                                >
                                    <i class="ti ti-briefcase text-sm" aria-hidden="true" />
                                    {{ row.job.description }}
                                </span>
                            </div>

                            <p class="mt-4 text-2xl font-bold tracking-tight text-ink">
                                {{ formatNaira(row.total_naira) }}
                            </p>
                            <p v-if="row.sent_at_label" class="mt-1 text-xs font-semibold text-emerald-700">
                                Marked ready {{ row.sent_at_label }}
                            </p>
                        </div>

                        <Link
                            v-if="row.builder_url"
                            :href="row.builder_url"
                            class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-3 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                        >
                            <i class="ti ti-pencil text-base" aria-hidden="true" />
                            {{ row.status === 'sent' ? 'View quote' : 'Continue editing' }}
                        </Link>
                    </div>
                </article>

                <AppPagination :links="quotes.links" />
            </div>

            <AppEmptyState
                v-else
                :icon="hasActiveFilters ? 'ti ti-search-off' : 'ti ti-file-invoice'"
                :title="hasActiveFilters ? 'No quotes match those filters' : 'No quotes yet'"
                :description="
                    hasActiveFilters
                        ? 'Try a different search or clear the filters.'
                        : 'Open a quote request and build your first proposal — it will appear here as a draft or finished quote.'
                "
                :cta-label="hasActiveFilters ? 'Clear filters' : 'View quote requests'"
                :cta-href="hasActiveFilters ? route('quotes.sent.index') : route('quotes.requests.index')"
                @action="clearFilters"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AppPagination from '@/Components/App/AppPagination.vue';
import QuoteWorkspaceNav from '@/Components/Quotes/QuoteWorkspaceNav.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    quotes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
    requestCounts: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q || '');
const sort = ref(props.filters.sort || 'newest');

watch(
    () => props.filters,
    (value) => {
        search.value = value.q || '';
        sort.value = value.sort || 'newest';
    },
);

const statCards = computed(() => [
    { key: 'total', label: 'Total', value: props.counts.total ?? 0 },
    { key: 'draft', label: 'Drafts', value: props.counts.draft ?? 0 },
    { key: 'sent', label: 'Ready', value: props.counts.sent ?? 0 },
]);

const statusOptions = computed(() => [
    { value: 'all', label: 'All', count: props.counts.total ?? 0 },
    { value: 'draft', label: 'Drafts', count: props.counts.draft ?? 0 },
    { value: 'sent', label: 'Ready', count: props.counts.sent ?? 0 },
]);

const hasActiveFilters = computed(
    () =>
        !!props.filters.q
        || (props.filters.status && props.filters.status !== 'all')
        || (props.filters.sort && props.filters.sort !== 'newest'),
);

const formatNaira = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);

const applyFilters = () => {
    router.get(
        route('quotes.sent.index'),
        {
            q: search.value || undefined,
            status: props.filters.status !== 'all' ? props.filters.status : undefined,
            sort: sort.value !== 'newest' ? sort.value : undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const setStatus = (status) => {
    router.get(
        route('quotes.sent.index'),
        {
            q: search.value || undefined,
            status: status !== 'all' ? status : undefined,
            sort: sort.value !== 'newest' ? sort.value : undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    search.value = '';
    sort.value = 'newest';
    router.get(route('quotes.sent.index'), {}, { replace: true });
};
</script>
