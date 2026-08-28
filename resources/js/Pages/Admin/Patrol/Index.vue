<template>
    <Head title="Patrol" />

    <AdminChrome title="Patrol" :eyebrow="eyebrow" />

    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">New</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-ink">{{ tabStats.new }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">In review</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-amber-800">{{ tabStats.in_review }}</p>
        </div>
        <div class="col-span-2 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:col-span-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Pending approval</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-violet-700">{{ tabStats.pending_approval }}</p>
        </div>
    </div>

    <form class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4" @submit.prevent="applyFilters">
        <div class="flex flex-col gap-3">
            <div class="relative min-w-0">
                <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Search artisan name…"
                    class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                />
            </div>
            <div class="no-scrollbar flex flex-wrap gap-1.5">
                <select v-model="form.status" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="">All statuses</option>
                    <option v-for="opt in options.statuses" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <select v-model="form.severity" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="">All severities</option>
                    <option v-for="opt in options.severities" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <select v-model="form.rule" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="">All rules</option>
                    <option v-for="opt in ruleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <input v-model="form.from" type="date" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base" />
                <input v-model="form.to" type="date" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base" />
                <select v-model="form.sort" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="severity">Severity first</option>
                    <option value="flagged">Flagged date</option>
                </select>
                <button type="submit" class="tap-target rounded-full bg-base-action px-4 py-2 text-[13px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover">
                    Filter
                </button>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            v-if="rows.length === 0"
            :title="activeTab === 'reviews' ? 'No flagged reviews right now' : 'No flagged job logs right now'"
            description="This is a good sign. Patrol only surfaces cases when a named detection rule matches."
            icon="ti ti-binoculars"
        />
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="item in rows" :key="item.id">
                <button
                    :ref="(el) => setRowRef(item.id, el)"
                    type="button"
                    class="flex w-full items-start gap-3.5 px-5 py-4 text-left transition-colors duration-150 sm:px-6"
                    :class="rowClass(item)"
                    @pointerenter="prefetchCase(item)"
                    @focus="prefetchCase(item)"
                    @pointerdown="prefetchCase(item)"
                    @click="openCase(item)"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-xs font-bold text-deep">
                        {{ item.artisan?.initials || '—' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-ink">{{ item.artisan?.name }}</p>
                            <span :class="[patrolPill, patrolStatusMeta(item.status).class]">{{ item.status_label }}</span>
                            <span :class="[patrolPill, patrolSeverityMeta(item.severity).class]">{{ item.severity_label }}</span>
                            <span :class="[patrolPill, patrolVisibilityMeta(item.visibility).class]">{{ patrolVisibilityMeta(item.visibility).label }}</span>
                        </div>
                        <p class="mt-0.5 text-sm font-medium text-ink/60">
                            <span v-if="item.kind === 'review' && item.rating != null" class="me-1.5 font-semibold text-ink">{{ item.rating }}★</span>
                            {{ item.kind === 'review' ? item.review_excerpt : item.job_summary }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-ink/40">
                            {{ item.rules.map((rule) => rule.label).join(' · ') }}
                            <span v-if="item.worked_on_label"> · Job {{ item.worked_on_label }}</span>
                            · Flagged {{ item.flagged_label }}
                        </p>
                        <p v-if="item.pending_approval && can.resolve" class="mt-1 text-xs font-semibold text-violet-700">
                            Pending your approval
                        </p>
                    </div>
                    <i class="ti ti-chevron-right mt-2 shrink-0 text-ink/25" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </div>

    <PatrolCaseDrawer
        :open="!!openId"
        :row="openRow"
        :panel="panel"
        :fallback-can="can"
        @close="closeCase"
        @refresh="refreshPanel"
        @updated="onUpdated"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import PatrolCaseDrawer from '@/Components/Admin/PatrolCaseDrawer.vue';
import { adminPath, visitAdmin } from '@/utils/adminVisit';
import { toast } from '@/utils/adminRange';
import { patrolPill, patrolSeverityMeta, patrolStatusMeta, patrolVisibilityMeta } from '@/utils/patrolStatus';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, inject, nextTick, ref, watch } from 'vue';

const props = defineProps({
    cases: { type: Array, default: () => [] },
    job_cases: { type: Array, default: () => [] },
    review_cases: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    job_stats: { type: Object, default: () => ({ open: 0, new: 0, pending_approval: 0, in_review: 0 }) },
    review_stats: { type: Object, default: () => ({ open: 0, new: 0, pending_approval: 0, in_review: 0 }) },
    options: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
    opened_id: { type: Number, default: null },
    tab: { type: String, default: 'jobs' },
});

const tabQuery = inject('adminTabQuery', ref({}));
const activeTab = computed(() => (tabQuery.value?.tab === 'reviews' || props.tab === 'reviews' ? 'reviews' : 'jobs'));
const rows = ref([...props.cases]);
const ruleOptions = computed(() => (activeTab.value === 'reviews' ? (props.options.review_rules || []) : (props.options.rules || [])));
const tabStats = computed(() => (activeTab.value === 'reviews' ? props.review_stats : props.job_stats));
const openId = ref(null);
const openRow = ref(null);
const panel = ref(null);
const panelLoading = ref(false);
const panelCache = new Map();
const panelInflight = new Map();
const rowRefs = new Map();
const lastFocusEl = ref(null);
let panelSeq = 0;

const eyebrow = computed(() => {
    const next = [];
    const current = activeTab.value === 'reviews' ? props.review_stats : props.job_stats;
    if (current.new) {
        next.push(`${current.new} new`);
    }
    if (current.pending_approval) {
        next.push(`${current.pending_approval} pending approval`);
    }
    return next.length ? next.join(', ') : 'No open flags';
});

const form = useForm({
    q: props.filters.q || '',
    status: props.filters.status || '',
    severity: props.filters.severity || '',
    rule: props.filters.rule || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
    sort: props.filters.sort || 'severity',
});

const syncRows = () => {
    rows.value = [...(activeTab.value === 'reviews' ? props.review_cases : props.job_cases)];
};

watch(
    () => [props.job_cases, props.review_cases, activeTab.value],
    () => syncRows(),
    { immediate: true },
);

watch(activeTab, (tab, previous) => {
    if (previous && tab !== previous) {
        closeCase();
        if (! ruleOptions.value.some((opt) => opt.value === form.rule)) {
            form.rule = '';
        }
    }
});

const filterQuery = (caseId = null) => ({
    q: form.q || undefined,
    status: form.status || undefined,
    severity: form.severity || undefined,
    rule: form.rule || undefined,
    from: form.from || undefined,
    to: form.to || undefined,
    sort: form.sort !== 'severity' ? form.sort : undefined,
    tab: activeTab.value === 'reviews' ? 'reviews' : 'jobs',
    case: caseId && activeTab.value !== 'reviews' ? caseId : undefined,
    review: caseId && activeTab.value === 'reviews' ? caseId : undefined,
});

const listUrl = (caseId = null) => route('admin.patrol.index', filterQuery(caseId));

const replaceListUrl = (href) => {
    window.history.replaceState(window.history.state, '', adminPath(href));
};

const setRowRef = (id, el) => {
    if (el) {
        rowRefs.set(id, el);
        return;
    }
    rowRefs.delete(id);
};

const rowClass = (item) => {
    if (openId.value === item.id) {
        return 'bg-tint/80';
    }
    if (item.pending_approval) {
        return 'bg-violet-50/70 hover:bg-violet-50';
    }
    return 'hover:bg-pale/70';
};

const onUpdated = (record) => {
    if (!record?.id) {
        return;
    }
    rows.value = rows.value.map((row) => (row.id === record.id ? { ...row, ...record } : row));
    if (openRow.value?.id === record.id) {
        openRow.value = { ...openRow.value, ...record };
    }
    const cached = panelCache.get(record.id);
    if (cached?.record) {
        const next = { ...cached, record: { ...cached.record, ...record } };
        panelCache.set(record.id, next);
        if (panel.value?.record?.id === record.id) {
            panel.value = next;
        }
    }
};

const requestPanel = (id, { refresh = false } = {}) => {
    if (!refresh && panelCache.has(id)) {
        return Promise.resolve(panelCache.get(id));
    }
    if (!refresh && panelInflight.has(id)) {
        return panelInflight.get(id);
    }

    const request = axios
        .get(route('admin.patrol.show', id), {
            headers: { Accept: 'application/json' },
        })
        .then(({ data }) => {
            panelCache.set(id, data);
            return data;
        })
        .finally(() => {
            if (panelInflight.get(id) === request) {
                panelInflight.delete(id);
            }
        });

    panelInflight.set(id, request);
    return request;
};

const prefetchCase = (item) => {
    if (!item?.id) {
        return;
    }
    requestPanel(item.id).catch(() => {});
};

const applyPanel = (id, data) => {
    panelCache.set(id, data);
    if (openId.value === id) {
        panel.value = data;
    }
};

const loadPanel = async ({ refresh = false } = {}) => {
    if (!openId.value) {
        return;
    }
    const id = openId.value;
    const seq = ++panelSeq;
    const cached = panelCache.get(id);
    if (cached) {
        panel.value = cached;
        panelLoading.value = false;
    } else {
        panelLoading.value = true;
    }
    try {
        const data = await requestPanel(id, { refresh });
        if (seq !== panelSeq || openId.value !== id) {
            return;
        }
        applyPanel(id, data);
    } catch {
        if (seq === panelSeq) {
            toast({ type: 'error', message: 'The case did not load.' });
            closeCase();
        }
    } finally {
        if (seq === panelSeq) {
            panelLoading.value = false;
        }
    }
};

const refreshPanel = () => loadPanel({ refresh: true });

const openCase = (item) => {
    lastFocusEl.value = rowRefs.get(item.id) || document.activeElement;
    openId.value = item.id;
    openRow.value = item.job_summary ? item : (rows.value.find((row) => row.id === item.id) || { id: item.id });
    const cached = panelCache.get(item.id);
    if (cached) {
        panel.value = cached;
        panelLoading.value = false;
    } else if (panel.value?.record?.id !== item.id) {
        panel.value = null;
    }
    replaceListUrl(listUrl(item.id));
    loadPanel();
};

const closeCase = () => {
    const id = openId.value;
    openId.value = null;
    replaceListUrl(listUrl());
    nextTick(() => {
        const target = lastFocusEl.value || rowRefs.get(id);
        if (target && typeof target.focus === 'function') {
            target.focus();
        }
    });
};

watch(
    () => props.opened_id,
    (id) => {
        if (!id) {
            return;
        }
        const item = rows.value.find((row) => row.id === id) || { id };
        openCase(item);
    },
    { immediate: true },
);

const applyFilters = () => {
    visitAdmin(route('admin.patrol.index', filterQuery()), { preserveState: true, replace: true });
};
</script>
