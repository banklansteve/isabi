<template>
    <Head title="Moderation desk" />

    <AdminChrome title="Moderation desk" eyebrow="Preview unified triage — actions open in existing tools" />

    <div class="mx-auto max-w-5xl space-y-5">
        <div class="rounded-2xl border border-violet-200 bg-violet-50/80 px-4 py-3.5 sm:px-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[13px] font-bold text-violet-900">Comparison preview</p>
                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-violet-900/75">
                        This desk aggregates flagged jobs, reviews, patrol cases, referrals, and user signals in one place.
                        It does not replace your current Job logs, Reviews, Patrol, or Assigned pages — use it to compare workflows side by side.
                    </p>
                </div>
                <span class="inline-flex shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-violet-700 ring-1 ring-violet-200">
                    Preview
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <article
                v-for="chip in statChips"
                :key="chip.key"
                class="rounded-2xl bg-white px-4 py-3 shadow-premium ring-1 ring-ink/[0.05]"
            >
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">{{ chip.label }}</p>
                <p class="mt-1 font-editorial text-2xl font-semibold tracking-tight text-ink">{{ chip.value }}</p>
            </article>
        </div>

        <div class="rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search title, artisan, source…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <FormButton variant="secondary" label="Search" @click="applyFilters" />
            </div>

            <nav class="mt-3 flex gap-1 overflow-x-auto border-t border-ink/[0.05] pt-3" aria-label="Desk filters">
                <Link
                    v-for="tab in filters"
                    :key="tab.key"
                    :href="filterHref(tab.key)"
                    preserve-state
                    preserve-scroll
                    class="shrink-0 rounded-xl px-3.5 py-2 text-[13px] font-semibold transition-colors"
                    :class="filter === tab.key ? 'bg-base-action text-white shadow-sm' : 'text-ink/45 hover:bg-pale hover:text-ink'"
                >
                    {{ tab.label }}
                    <span class="ms-1 opacity-70">{{ stats[tab.key] ?? 0 }}</span>
                </Link>
            </nav>
        </div>

        <AdminEmpty
            v-if="!items.length"
            title="Nothing in this view"
            description="Try another filter or search term. Items appear here when content is flagged, referred, or needs attention."
            icon="ti ti-shield-check"
        />

        <ul v-else class="space-y-2">
            <li v-for="item in items" :key="item.id">
                <Link
                    :href="item.href"
                    class="block rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] transition-colors hover:bg-pale/50 sm:p-5"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="iconClass(item.kind)"
                        >
                            <i :class="item.icon || 'ti ti-flag'" class="text-lg" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[15px] font-bold tracking-tight text-ink">{{ item.title }}</p>
                                <span class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/50">
                                    {{ kindLabel(item.kind) }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="severityClass(item.severity)">
                                    {{ item.source_label }}
                                </span>
                            </div>
                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/55">{{ item.subtitle }}</p>
                            <p class="mt-2 text-[12px] font-semibold text-ink/35">
                                {{ item.subject_label }}
                                <span v-if="item.age"> · {{ item.age }}</span>
                                <span v-if="item.meta?.assignee"> · Assigned to {{ item.meta.assignee }}</span>
                            </p>
                        </div>
                        <i class="ti ti-external-link mt-2 shrink-0 text-ink/25" aria-hidden="true" />
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    filter: { type: String, default: 'all' },
    query: { type: String, default: '' },
    stats: { type: Object, default: () => ({}) },
    items: { type: Array, default: () => [] },
    filters: { type: Array, default: () => [] },
});

const search = ref(props.query || '');

const statChips = computed(() =>
    props.filters.map((tab) => ({
        key: tab.key,
        label: tab.label,
        value: Number(props.stats[tab.key] ?? 0).toLocaleString(),
    })),
);

const filterHref = (key) =>
    route('admin.moderation-desk.index', {
        filter: key === 'all' ? undefined : key,
        q: search.value || undefined,
    });

const applyFilters = () => {
    router.get(
        route('admin.moderation-desk.index'),
        {
            filter: props.filter === 'all' ? undefined : props.filter,
            q: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const kindLabel = (kind) => {
    const map = {
        jobs: 'Job',
        reviews: 'Review',
        patrol: 'Patrol',
        referrals: 'Referral',
        users: 'User',
    };

    return map[kind] || kind;
};

const iconClass = (kind) => {
    const map = {
        jobs: 'bg-blue-50 text-base-action',
        reviews: 'bg-amber-50 text-amber-700',
        patrol: 'bg-violet-50 text-violet-700',
        referrals: 'bg-coral/10 text-coral-deep',
        users: 'bg-red-50 text-red-600',
    };

    return map[kind] || 'bg-pale text-ink/45';
};

const severityClass = (severity) => {
    if (severity === 'high') return 'bg-red-50 text-red-600';
    if (severity === 'medium') return 'bg-amber-50 text-amber-800';
    return 'bg-emerald-50 text-emerald-700';
};
</script>
