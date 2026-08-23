<template>
    <Head title="Work log" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl">
            <!-- Hero -->
            <section
                class="worklog-hero relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
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

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Your proof trail
                        </p>
                        <h1
                            class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]"
                        >
                            Work log
                        </h1>
                        <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                            Finished jobs you’ve recorded — the quiet evidence that wins the next client.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <a
                            v-if="entries.length"
                            :href="route('work-log.export')"
                            class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 transition-colors hover:bg-white/16"
                        >
                            <i class="ti ti-file-type-pdf" aria-hidden="true" />
                            Export PDF
                        </a>
                        <Link
                            :href="route('work-log.create')"
                            class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] transition-colors hover:bg-coral-deep"
                        >
                            <i class="ti ti-plus" aria-hidden="true" />
                            Log a job
                        </Link>
                    </div>
                </div>

                <div
                    v-if="entries.length"
                    class="relative mt-7 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3"
                >
                    <div
                        v-for="stat in stats"
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

            <!-- Reminder banner -->
            <div
                v-if="dueReminderCount > 0"
                class="mb-5 flex flex-col gap-3 overflow-hidden rounded-[1.35rem] bg-gradient-to-r from-amber-50 to-orange-50/80 px-4 py-3.5 ring-1 ring-amber-200/70 sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-800"
                    >
                        <i class="ti ti-bell text-lg" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-amber-950">
                            {{ dueReminderCount }} client{{ dueReminderCount === 1 ? '' : 's' }} waiting for a nudge
                        </p>
                        <p class="mt-0.5 text-xs font-medium text-amber-900/70">
                            One WhatsApp reminder each — same send flow as the first invite.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="tap-target shrink-0 rounded-xl bg-amber-950 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-ink"
                    @click="filterDueReminders"
                >
                    Show due jobs
                </button>
            </div>

            <!-- Filters -->
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
                            class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                            :class="
                                period === option.value
                                    ? 'bg-ink text-white shadow-sm'
                                    : 'bg-pale text-ink/55 hover:bg-tint hover:text-ink'
                            "
                            @click="period = option.value"
                        >
                            {{ option.label }}
                        </button>
                        <button
                            v-if="dueReminderCount > 0"
                            type="button"
                            class="tap-target rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                            :class="
                                dueOnly
                                    ? 'bg-amber-900 text-white shadow-sm'
                                    : 'bg-amber-50 text-amber-900/70 ring-1 ring-amber-200/80 hover:bg-amber-100'
                            "
                            @click="dueOnly = !dueOnly"
                        >
                            Due nudges
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

                <p class="text-[11px] font-semibold text-ink/35">
                    Showing {{ filteredEntries.length }} of {{ entries.length }} job{{
                        entries.length === 1 ? '' : 's'
                    }}
                </p>
            </div>

            <AppEmptyState
                v-if="!entries.length"
                icon="ti ti-notebook"
                title="No jobs yet"
                description="Log your first finished job — a short description and date is enough to start your proof trail."
                cta-label="Log a job"
                :cta-href="route('work-log.create')"
            />

            <AppEmptyState
                v-else-if="!filteredEntries.length"
                icon="ti ti-filter-off"
                title="No matching jobs"
                description="Try another search or period — or clear filters to see everything."
                cta-label="Clear filters"
                @action="clearFilters"
            />

            <!-- List -->
            <ul v-else class="space-y-3">
                <li
                    v-for="(entry, index) in pagedEntries"
                    :key="entry.uid"
                    class="worklog-row"
                    :style="{ animationDelay: `${Math.min(index, 8) * 40}ms` }"
                >
                    <Link
                        :href="route('work-log.show', entry.uid)"
                        class="group flex items-center gap-3.5 rounded-[1.35rem] bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.06] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-hover hover:ring-base-action/20 sm:gap-5 sm:p-4"
                    >
                        <div
                            class="relative flex h-[4.75rem] w-[4.75rem] shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-pale sm:h-[5.5rem] sm:w-[5.5rem]"
                        >
                            <img
                                v-if="entry.thumbnail"
                                :src="entry.thumbnail"
                                alt=""
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"
                            />
                            <i
                                v-else
                                class="ti ti-briefcase text-2xl text-ink/20"
                                aria-hidden="true"
                            />
                            <span
                                v-if="entry.media_count"
                                class="absolute bottom-1.5 right-1.5 inline-flex items-center gap-0.5 rounded-md bg-ink/75 px-1.5 py-0.5 text-[10px] font-bold text-white backdrop-blur-sm"
                            >
                                <i class="ti ti-photo text-[10px]" aria-hidden="true" />
                                {{ entry.media_count }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1.5">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-[0.98rem] font-semibold tracking-tight text-ink sm:text-[1.02rem]"
                                    >
                                        {{ entry.description }}
                                    </p>
                                    <p class="mt-1 truncate text-xs font-medium text-ink/45">
                                        <span v-if="entry.client_name">{{ entry.client_name }}</span>
                                        <span
                                            v-if="entry.client_name && (entry.category_label || entry.job_category)"
                                            class="text-ink/25"
                                        >
                                            ·
                                        </span>
                                        <span v-if="entry.category_label || entry.job_category">
                                            {{ entry.category_label || entry.job_category }}
                                        </span>
                                        <span
                                            v-if="entry.service_label"
                                            class="text-ink/25"
                                        >
                                            ·
                                        </span>
                                        <span v-if="entry.service_label">{{ entry.service_label }}</span>
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold tabular-nums text-ink/50"
                                >
                                    {{ entry.worked_on_label }}
                                </span>
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="statusClass(entry)"
                                >
                                    <i :class="statusIcon(entry)" class="text-[12px]" aria-hidden="true" />
                                    {{ statusLabel(entry) }}
                                </span>
                                <span
                                    v-if="entry.amount_naira != null"
                                    class="inline-flex items-center rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/55"
                                >
                                    ₦{{ formatAmount(entry.amount_naira) }}
                                    <span class="ms-1 font-medium text-ink/30">private</span>
                                </span>
                                <span
                                    v-if="entry.reminder_due"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800"
                                >
                                    <i class="ti ti-bell text-[12px]" aria-hidden="true" />
                                    Nudge due
                                </span>
                            </div>
                        </div>

                        <i
                            class="ti ti-chevron-right shrink-0 text-lg text-ink/15 transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-base-action"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>

            <div
                v-if="pageCount > 1"
                class="mt-7 flex items-center justify-between rounded-2xl bg-white px-4 py-3 shadow-premium ring-1 ring-ink/[0.05]"
            >
                <button
                    type="button"
                    class="tap-target inline-flex items-center gap-1.5 text-sm font-semibold text-ink/55 transition-colors hover:text-ink disabled:cursor-not-allowed disabled:opacity-30"
                    :disabled="page <= 1"
                    @click="page -= 1"
                >
                    <i class="ti ti-chevron-left" aria-hidden="true" />
                    Previous
                </button>
                <p class="text-xs font-semibold tabular-nums text-ink/40">
                    {{ page }} / {{ pageCount }}
                </p>
                <button
                    type="button"
                    class="tap-target inline-flex items-center gap-1.5 text-sm font-semibold text-ink/55 transition-colors hover:text-ink disabled:cursor-not-allowed disabled:opacity-30"
                    :disabled="page >= pageCount"
                    @click="page += 1"
                >
                    Next
                    <i class="ti ti-chevron-right" aria-hidden="true" />
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    entries: { type: Array, default: () => [] },
    maxLookbackDays: { type: Number, default: 14 },
    dueReminderCount: { type: Number, default: 0 },
});

const search = ref('');
const period = ref('all');
const sort = ref('newest');
const dueOnly = ref(false);
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

const stats = computed(() => {
    const total = props.entries.length;
    const reviewed = props.entries.filter((e) => e.has_review).length;
    const awaiting = props.entries.filter((e) => !e.has_review && e.review_requested).length;
    return [
        { label: 'Jobs', value: total },
        { label: 'Reviewed', value: reviewed },
        { label: 'Awaiting', value: awaiting },
    ];
});

const hasActiveFilters = computed(
    () =>
        search.value.trim() !== '' ||
        period.value !== 'all' ||
        sort.value !== 'newest' ||
        dueOnly.value,
);

const filterDueReminders = () => {
    dueOnly.value = true;
    search.value = '';
    period.value = 'all';
    page.value = 1;
};

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
        if (dueOnly.value && !entry.reminder_due) {
            return false;
        }

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
            return (
                (a.amount_naira ?? Number.POSITIVE_INFINITY) -
                (b.amount_naira ?? Number.POSITIVE_INFINITY)
            );
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
    dueOnly.value = false;
    page.value = 1;
};

watch([search, period, sort, dueOnly], () => {
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

const statusLabel = (entry) => {
    if (entry.has_review) return 'Reviewed';
    if (entry.review_requested) return 'Review requested';
    if (entry.client_whatsapp) return 'Ready to invite';
    return 'Logged';
};

const statusIcon = (entry) => {
    if (entry.has_review) return 'ti ti-star';
    if (entry.review_requested) return 'ti ti-hourglass';
    if (entry.client_whatsapp) return 'ti ti-brand-whatsapp';
    return 'ti ti-check';
};

const statusClass = (entry) => {
    if (entry.has_review) return 'bg-emerald-50 text-emerald-800';
    if (entry.review_requested) return 'bg-amber-50 text-amber-800';
    if (entry.client_whatsapp) return 'bg-emerald-50/80 text-emerald-700';
    return 'bg-tint text-deep';
};
</script>

<style scoped>
.worklog-hero {
    animation: hero-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.worklog-row {
    animation: row-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes hero-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes row-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
