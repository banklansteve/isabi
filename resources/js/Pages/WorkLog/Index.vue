<template>
    <Head title="Work log" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl">
            <AppPageHeader
                title="Work log"
                description="Finished jobs you’ve recorded — your proof trail."
            >
                <template #actions>
                    <Link
                        :href="route('work-log.create')"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        <i class="ti ti-plus" aria-hidden="true" />
                        Log a job
                    </Link>
                </template>
            </AppPageHeader>

            <div
                v-if="entries.length"
                class="mb-5 space-y-3 rounded-[1.5rem] bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-5"
            >
                <div class="relative">
                    <i
                        class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35"
                        aria-hidden="true"
                    />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search jobs, clients, categories…"
                        class="w-full rounded-xl border border-ink/10 bg-pale py-3 ps-10 pe-3 text-sm font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                    />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="option in periodOptions"
                            :key="option.value"
                            type="button"
                            class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                period === option.value
                                    ? 'bg-base-action text-white'
                                    : 'bg-pale text-ink/55 hover:bg-tint hover:text-ink'
                            "
                            @click="period = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="sr-only" for="work-log-sort">Sort</label>
                        <div class="relative min-w-[11rem] flex-1 sm:flex-none">
                            <i
                                class="ti ti-arrows-sort pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink/35"
                                aria-hidden="true"
                            />
                            <select
                                id="work-log-sort"
                                v-model="sort"
                                class="w-full appearance-none rounded-xl border border-ink/10 bg-pale py-2.5 ps-9 pe-8 text-xs font-semibold text-ink outline-none focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                            >
                                <option
                                    v-for="option in sortOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <i
                                class="ti ti-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink/30"
                                aria-hidden="true"
                            />
                        </div>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="tap-target rounded-xl px-3 py-2.5 text-xs font-semibold text-ink/45 transition-colors hover:bg-pale hover:text-ink"
                            @click="clearFilters"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="!entries.length"
                class="rounded-[1.5rem] border border-dashed border-ink/10 bg-white px-6 py-14 text-center shadow-premium"
            >
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-2xl text-ink/35">
                    <i class="ti ti-notebook" aria-hidden="true" />
                </span>
                <p class="mt-4 text-base font-semibold text-ink">No jobs yet</p>
                <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-ink/45">
                    Log your first finished job — a short description and date is enough to start.
                </p>
                <Link
                    :href="route('work-log.create')"
                    class="tap-target mt-6 inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    Log a job
                    <i class="ti ti-arrow-right" aria-hidden="true" />
                </Link>
            </div>

            <div
                v-else-if="!filteredEntries.length"
                class="rounded-[1.5rem] border border-dashed border-ink/10 bg-white px-6 py-14 text-center shadow-premium"
            >
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-2xl text-ink/35">
                    <i class="ti ti-filter-off" aria-hidden="true" />
                </span>
                <p class="mt-4 text-base font-semibold text-ink">No matching jobs</p>
                <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-ink/45">
                    Try another search, period, or sort — or clear filters to see everything.
                </p>
                <button
                    type="button"
                    class="tap-target mt-6 inline-flex items-center gap-2 rounded-2xl border border-ink/10 bg-white px-5 py-3 text-sm font-semibold text-ink/70 transition-colors hover:border-ink/20 hover:text-ink"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="entry in pagedEntries"
                    :key="entry.uid"
                >
                    <Link
                        :href="route('work-log.show', entry.uid)"
                        class="group flex items-center gap-4 rounded-[1.35rem] bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.06] transition-all hover:shadow-premium-hover hover:ring-base-action/20 sm:gap-5 sm:p-4"
                    >
                        <div
                            class="flex h-[4.75rem] w-[4.75rem] shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-pale sm:h-[5.5rem] sm:w-[5.5rem]"
                        >
                            <img
                                v-if="entry.thumbnail"
                                :src="entry.thumbnail"
                                alt=""
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                            />
                            <i v-else class="ti ti-briefcase text-2xl text-ink/25" aria-hidden="true" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <p class="truncate text-[0.95rem] font-semibold tracking-tight text-ink">
                                    {{ entry.description }}
                                </p>
                                <span
                                    class="shrink-0 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/50"
                                >
                                    {{ entry.worked_on_label }}
                                </span>
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                <span
                                    v-if="entry.client_name"
                                    class="inline-flex items-center gap-1 rounded-full bg-tint px-2.5 py-1 text-[11px] font-semibold text-deep"
                                >
                                    <i class="ti ti-user text-[12px]" aria-hidden="true" />
                                    {{ entry.client_name }}
                                </span>
                                <span
                                    v-if="entry.category_label || entry.job_category"
                                    class="inline-flex items-center rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/60"
                                >
                                    {{ entry.category_label || entry.job_category }}
                                </span>
                                <span
                                    v-if="entry.service_label"
                                    class="inline-flex items-center gap-1 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/60"
                                >
                                    <i class="ti ti-map-pin text-[12px]" aria-hidden="true" />
                                    {{ entry.service_label }}
                                </span>
                                <span
                                    v-if="entry.media_count"
                                    class="inline-flex items-center gap-1 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/60"
                                >
                                    <i class="ti ti-photo text-[12px]" aria-hidden="true" />
                                    {{ entry.media_count }}
                                </span>
                                <span
                                    v-if="entry.client_whatsapp"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                                >
                                    <i class="ti ti-brand-whatsapp text-[12px]" aria-hidden="true" />
                                    Review ready
                                </span>
                                <span
                                    v-if="entry.amount_naira != null"
                                    class="inline-flex items-center rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/60"
                                >
                                    ₦{{ formatAmount(entry.amount_naira) }}
                                    <span class="ms-1 text-ink/35">private</span>
                                </span>
                                <span
                                    v-if="entry.review_requested"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800"
                                >
                                    <i class="ti ti-lock text-[12px]" aria-hidden="true" />
                                    Review requested
                                </span>
                            </div>
                        </div>

                        <i
                            class="ti ti-chevron-right shrink-0 text-lg text-ink/20 transition-colors group-hover:text-base-action"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>

            <div
                v-if="pageCount > 1"
                class="mt-6 flex items-center justify-between"
            >
                <button
                    type="button"
                    class="tap-target text-sm font-semibold text-base-action transition-colors hover:text-base-hover disabled:cursor-not-allowed disabled:opacity-35"
                    :disabled="page <= 1"
                    @click="page -= 1"
                >
                    Previous
                </button>
                <p class="text-xs font-semibold text-ink/40">
                    {{ page }} / {{ pageCount }}
                </p>
                <button
                    type="button"
                    class="tap-target text-sm font-semibold text-base-action transition-colors hover:text-base-hover disabled:cursor-not-allowed disabled:opacity-35"
                    :disabled="page >= pageCount"
                    @click="page += 1"
                >
                    Next
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    entries: { type: Array, default: () => [] },
    maxLookbackDays: { type: Number, default: 14 },
});

const search = ref('');
const period = ref('all');
const sort = ref('newest');
const page = ref(1);
const pageSize = 12;

const periodOptions = [
    { value: 'all', label: 'All time' },
    { value: '7d', label: '7 days' },
    { value: '14d', label: '14 days' },
    { value: '30d', label: '1 month' },
];

const sortOptions = [
    { value: 'newest', label: 'Newest first' },
    { value: 'oldest', label: 'Oldest first' },
    { value: 'amount_high', label: 'Amount: high → low' },
    { value: 'amount_low', label: 'Amount: low → high' },
];

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || period.value !== 'all' || sort.value !== 'newest',
);

const periodStart = computed(() => {
    const days = { '7d': 7, '14d': 14, '30d': 30 }[period.value];
    if (!days) {
        return null;
    }
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() - days);
    return d;
});

const filteredEntries = computed(() => {
    const q = search.value.trim().toLowerCase();
    const start = periodStart.value;

    let list = props.entries.filter((entry) => {
        if (start && entry.worked_on) {
            const worked = new Date(`${entry.worked_on}T00:00:00`);
            if (worked < start) {
                return false;
            }
        }

        if (!q) {
            return true;
        }

        const haystack = [
            entry.description,
            entry.client_name,
            entry.job_category,
            entry.job_subcategory,
            entry.category_label,
            entry.service_label,
            entry.client_whatsapp,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(q);
    });

    list = [...list].sort((a, b) => {
        if (sort.value === 'oldest') {
            return String(a.worked_on).localeCompare(String(b.worked_on)) || a.id - b.id;
        }
        if (sort.value === 'amount_high') {
            return (b.amount_naira ?? -1) - (a.amount_naira ?? -1);
        }
        if (sort.value === 'amount_low') {
            return (a.amount_naira ?? Number.POSITIVE_INFINITY) - (b.amount_naira ?? Number.POSITIVE_INFINITY);
        }
        return String(b.worked_on).localeCompare(String(a.worked_on)) || b.id - a.id;
    });

    return list;
});

const pageCount = computed(() => Math.max(1, Math.ceil(filteredEntries.value.length / pageSize)));

const pagedEntries = computed(() => {
    const start = (page.value - 1) * pageSize;
    return filteredEntries.value.slice(start, start + pageSize);
});

const clearFilters = () => {
    search.value = '';
    period.value = 'all';
    sort.value = 'newest';
    page.value = 1;
};

watch([search, period, sort], () => {
    page.value = 1;
});

watch(pageCount, (count) => {
    if (page.value > count) {
        page.value = count;
    }
});

const formatAmount = (value) =>
    Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
</script>
