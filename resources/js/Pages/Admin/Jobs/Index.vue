<template>
    <Head title="Job logs" />

    <AdminChrome title="Job logs" :eyebrow="`${list.total.value.toLocaleString()} logged across Isabi`" />

    <div class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                <input
                    v-model="list.q.value"
                    type="search"
                    placeholder="Search description, client, artisan…"
                    class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                />
            </div>
            <div class="no-scrollbar flex gap-1.5 overflow-x-auto lg:justify-end">
                <select v-model="statusFilter" class="chip-select" :class="statusFilter ? 'chip-select--on' : ''">
                    <option value="">All statuses</option>
                    <option value="flagged">Flagged</option>
                    <option value="hidden">Hidden</option>
                    <option value="removed">Removed</option>
                    <option value="referred">Referred</option>
                </select>
                <select v-model="list.sort.value" class="chip-select">
                    <option value="date_desc">Newest first</option>
                    <option value="date_asc">Oldest first</option>
                    <option value="backdated_desc">Most backdated</option>
                </select>
            </div>
        </div>
        <div class="mt-3 border-t border-ink/[0.05] pt-3">
            <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Logged</p>
            <AdminRangePicker :range="range" />
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            v-if="!list.pageItems.value.length"
            title="No job logs match"
            description="Try a different search, status, or date range."
            icon="ti ti-briefcase"
        />
        <ul v-else class="divide-y divide-ink/[0.06]">
            <li v-for="job in list.pageItems.value" :key="job.uid">
                <button
                    :ref="(el) => setRowRef(job.uid, el)"
                    type="button"
                    class="group flex w-full items-start gap-3 px-4 py-4 text-left transition-colors duration-150 sm:gap-4 sm:px-5"
                    :class="openUid === job.uid ? 'bg-tint/80' : 'hover:bg-pale/70'"
                    @pointerenter="prefetchJob(job)"
                    @focus="prefetchJob(job)"
                    @click="openJob(job)"
                >
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-base-action">
                        <i class="ti ti-briefcase text-lg" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-bold text-ink sm:text-[15px]">{{ job.description }}</span>
                            <JobStatusBadges :job="job" />
                        </span>
                        <span class="mt-1 block text-[13px] font-medium text-ink/45">
                            {{ job.user?.name || 'Unknown artisan' }}
                            <span v-if="job.client_name"> · {{ job.client_name }}</span>
                            <span v-if="job.worked_on"> · {{ job.worked_on }}</span>
                        </span>
                        <span v-if="job.backdated_days >= 30" class="mt-1 block text-[12px] font-semibold text-coral">
                            Backdated {{ job.backdated_days }} days
                        </span>
                    </span>
                    <i class="ti ti-chevron-right mt-2 shrink-0 text-sm text-ink/20 transition-colors group-hover:text-ink/40" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </div>

    <AdminClientPager
        class="mt-4"
        :page="list.page.value"
        :pages="list.pageCount.value"
        :total="list.total.value"
        :per-page="list.perPage"
        @update:page="list.page.value = $event"
    />

    <JobLogDrawer
        :open="!!openUid"
        :row="openRow"
        :panel="panel"
        @close="closeJob"
        @refresh="refreshPanel"
        @updated="onUpdated"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import JobLogDrawer from '@/Components/Admin/JobLogDrawer.vue';
import JobStatusBadges from '@/Components/Admin/JobStatusBadges.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import { toast } from '@/utils/adminRange';
import { adminPath } from '@/utils/adminVisit';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    jobs: { type: Array, default: () => [] },
    opened_uid: { type: String, default: null },
    can: { type: Object, default: () => ({}) },
});

const { tab } = useAdminTabs({ tab: 'all' });
const range = useDateRange('all');
const rows = ref([...props.jobs]);
const statusFilter = ref(tab.value === 'flagged' ? 'flagged' : tab.value === 'hidden' ? 'hidden' : '');

watch(
    () => props.jobs,
    (value) => {
        rows.value = [...value];
    },
);

watch(tab, (value) => {
    if (value === 'flagged') statusFilter.value = 'flagged';
    else if (value === 'hidden') statusFilter.value = 'hidden';
    else if (value === 'all' || !value) statusFilter.value = '';
});

const list = useClientList(
    () => rows.value.filter((job) => {
        if (statusFilter.value === 'flagged' && !job.flagged) return false;
        if (statusFilter.value === 'hidden' && !job.hidden) return false;
        if (statusFilter.value === 'removed' && !job.removed) return false;
        if (statusFilter.value === 'referred' && !job.referred) return false;
        if (tab.value === 'suspicious' && job.backdated_days < 30) return false;
        return range.matches(job.created_iso);
    }),
    {
        perPage: 20,
        searchFields: ['description', 'client_name', 'user.name', 'user.email', 'category'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso', backdated: 'backdated_days' },
    },
);

watch([tab, statusFilter, () => range.preset.value], () => {
    list.page.value = 1;
});

const openUid = ref(null);
const openRow = ref(null);
const panel = ref(null);
const panelCache = new Map();
const panelInflight = new Map();
const rowRefs = new Map();
const lastFocusEl = ref(null);
let panelSeq = 0;

const listUrl = (uid = null) => route('admin.jobs.index', {
    tab: tab.value && tab.value !== 'all' ? tab.value : undefined,
    job: uid || undefined,
});

const replaceListUrl = (href) => {
    window.history.replaceState(window.history.state, '', adminPath(href));
};

const setRowRef = (uid, el) => {
    if (el) rowRefs.set(uid, el);
    else rowRefs.delete(uid);
};

const onUpdated = (payload) => {
    const job = payload?.job || payload?.record;
    if (!job?.uid) return;
    rows.value = rows.value.map((row) => (row.uid === job.uid ? { ...row, ...job } : row));
    if (openRow.value?.uid === job.uid) openRow.value = { ...openRow.value, ...job };
    const cached = panelCache.get(job.uid);
    if (payload?.record) {
        const next = { ...(cached || panel.value || {}), record: payload.record };
        panelCache.set(job.uid, next);
        if (openUid.value === job.uid) panel.value = next;
    }
};

const requestPanel = (uid, { refresh = false } = {}) => {
    if (!refresh && panelCache.has(uid)) return Promise.resolve(panelCache.get(uid));
    if (!refresh && panelInflight.has(uid)) return panelInflight.get(uid);
    const request = axios
        .get(route('admin.jobs.show', uid), { headers: { Accept: 'application/json' } })
        .then(({ data }) => {
            panelCache.set(uid, data);
            return data;
        })
        .finally(() => {
            if (panelInflight.get(uid) === request) panelInflight.delete(uid);
        });
    panelInflight.set(uid, request);
    return request;
};

const prefetchJob = (job) => {
    if (job?.uid) requestPanel(job.uid).catch(() => {});
};

const loadPanel = async ({ refresh = false } = {}) => {
    if (!openUid.value) return;
    const uid = openUid.value;
    const seq = ++panelSeq;
    const cached = panelCache.get(uid);
    if (cached) panel.value = cached;
    else if (panel.value?.record?.uid !== uid) panel.value = null;
    try {
        const data = await requestPanel(uid, { refresh });
        if (seq !== panelSeq || openUid.value !== uid) return;
        panelCache.set(uid, data);
        panel.value = data;
    } catch {
        if (seq === panelSeq) {
            toast({ type: 'error', title: 'Couldn’t load', message: 'The job details did not load.' });
            closeJob();
        }
    }
};

const refreshPanel = () => loadPanel({ refresh: true });

const openJob = (job) => {
    if (!job?.uid) return;
    lastFocusEl.value = rowRefs.get(job.uid) || document.activeElement;
    openUid.value = job.uid;
    openRow.value = job;
    panel.value = panelCache.get(job.uid) || (panel.value?.record?.uid === job.uid ? panel.value : null);
    replaceListUrl(listUrl(job.uid));
    loadPanel();
};

const closeJob = () => {
    const uid = openUid.value;
    openUid.value = null;
    openRow.value = null;
    replaceListUrl(listUrl());
    nextTick(() => {
        const target = lastFocusEl.value || rowRefs.get(uid);
        if (target?.focus) target.focus();
    });
};

watch(tab, (current, previous) => {
    if (previous && current !== previous) closeJob();
});

watch(
    () => props.opened_uid,
    (uid) => {
        if (!uid) return;
        openJob(rows.value.find((row) => row.uid === uid) || { uid });
    },
    { immediate: true },
);
</script>

<style scoped>
.chip-select {
    @apply appearance-none rounded-full border border-transparent bg-[#F4F6FA] px-3 py-2 pe-8 text-[12px] font-semibold text-ink/55 outline-none transition-colors duration-150 hover:bg-tint;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.65rem center;
    background-repeat: no-repeat;
}
.chip-select--on {
    @apply bg-tint text-deep ring-transparent;
}
</style>
