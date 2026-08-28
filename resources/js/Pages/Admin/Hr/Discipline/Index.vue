<template>
    <Head title="Disciplinary cases" />

    <AdminChrome title="Disciplinary cases" :eyebrow="eyebrow" />

    <div class="mb-4 grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Open</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-ink">{{ stats.open }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Decision pending</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-amber-800">{{ stats.decision_pending }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Appeals</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-violet-700">{{ stats.appealed }}</p>
        </div>
    </div>

    <form class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4" @submit.prevent="applyFilters">
        <div class="flex flex-col gap-3">
            <div class="relative min-w-0">
                <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Search by staff name or case reference…"
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
                <select v-model="form.category" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="">All categories</option>
                    <option v-for="opt in options.categories" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <input v-model="form.from" type="date" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base" />
                <input v-model="form.to" type="date" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base" />
                <label class="inline-flex items-center gap-2 rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink">
                    <input v-model="form.archived" type="checkbox" class="rounded border-ink/20" />
                    Archived
                </label>
                <button type="submit" class="tap-target rounded-full bg-base-action px-4 py-2 text-[13px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover">
                    Filter
                </button>
            </div>
        </div>
    </form>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div class="flex gap-2">
            <Link :href="route('admin.hr.discipline.reports')" class="text-xs font-semibold text-ink/45 hover:text-ink">Aggregate report</Link>
            <Link :href="route('admin.hr.discipline.templates')" class="text-xs font-semibold text-ink/45 hover:text-ink">Letter templates</Link>
        </div>
        <FormButton v-if="can.manage" variant="primary" icon-left="ti ti-plus" label="Open a case" @click="createOpen = true" />
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            v-if="rows.length === 0"
            title="No cases in this view"
            description="Cases are a permanent employment record. They can be closed or archived, but they are never removed."
            icon="ti ti-clipboard-text"
        >
            <FormButton v-if="can.manage" variant="primary" icon-left="ti ti-plus" label="Open the first case" @click="createOpen = true" />
        </AdminEmpty>
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="item in rows" :key="item.id">
                <button
                    :ref="(el) => setRowRef(item.id, el)"
                    type="button"
                    class="flex w-full items-start gap-3.5 px-5 py-4 text-left transition-colors duration-150 sm:px-6"
                    :class="openId === item.id ? 'bg-tint/80' : 'hover:bg-pale/70'"
                    @pointerenter="prefetchCase(item)"
                    @focus="prefetchCase(item)"
                    @pointerdown="prefetchCase(item)"
                    @click="openCase(item)"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-xs font-bold text-deep">
                        {{ item.staff_initials }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-ink">{{ item.staff_name }}</p>
                            <span class="text-xs font-semibold text-ink/35">{{ item.reference }}</span>
                            <span :class="[pillBase, statusMeta(item.status).class]">{{ item.status_label }}</span>
                            <span :class="[pillBase, severityMeta(item.severity).class]">{{ item.severity_label }}</span>
                        </div>
                        <p class="mt-0.5 text-sm font-medium text-ink/60">{{ item.category_label }} · Incident {{ item.incident_label }}</p>
                        <p class="mt-1 text-xs font-medium text-ink/40">
                            Opened {{ item.opened_label }} · Updated {{ item.updated_label }}
                            <span v-if="item.owner_name"> · Owner {{ item.owner_name }}</span>
                        </p>
                    </div>
                    <i class="ti ti-chevron-right mt-2 shrink-0 text-ink/25" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </div>

    <DisciplinaryCaseDrawer
        :open="!!openId"
        :row="openRow"
        :panel="panel"
        :loading="panelLoading"
        @close="closeCase"
        @refresh="refreshPanel"
        @updated="onUpdated"
    />

    <AdminDrawer :open="createOpen" title="Open a case" eyebrow="Incident report" @close="createOpen = false">
        <form class="space-y-4" @submit.prevent="submitCase">
            <p class="text-[13px] font-medium leading-relaxed text-ink/50">
                Opening a case starts a formal record. Describe the incident as it is known now. Findings belong in later notes.
            </p>
            <FormSelect id="dc-staff" v-model="createForm.user_id" label="Staff member" icon="ti ti-user" :options="staffOptions" searchable :error="createForm.errors.user_id" />
            <FormSelect id="dc-owner" v-model="createForm.owner_id" label="Case owner" icon="ti ti-user-check" :options="ownerOptions" searchable :error="createForm.errors.owner_id" />
            <FormSelect id="dc-category" v-model="createForm.category" label="Category" icon="ti ti-tag" :options="options.categories" :error="createForm.errors.category" />
            <FormTextInput v-if="createForm.category === 'other'" id="dc-category-label" v-model="createForm.category_label" label="Custom category" icon="ti ti-text-caption" :error="createForm.errors.category_label" required />
            <FormSelect id="dc-severity" v-model="createForm.severity" label="Initial severity" icon="ti ti-scale" :options="options.severities" :error="createForm.errors.severity" />
            <FormTextInput id="dc-incident" v-model="createForm.incident_on" type="date" :max="today" label="Date of incident" icon="ti ti-calendar" :error="createForm.errors.incident_on" required />
            <FormTextarea id="dc-desc" v-model="createForm.description" label="Incident description" :error="createForm.errors.description" required />
            <FormTextarea id="dc-reason" v-model="createForm.reason" label="Reason for opening this case" :error="createForm.errors.reason" required />
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <FormButton type="button" variant="secondary" label="Cancel" @click="createOpen = false" />
                <FormButton type="submit" variant="primary" label="Open case" :loading="createForm.processing" loading-label="Opening…" />
            </div>
        </form>
    </AdminDrawer>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import DisciplinaryCaseDrawer from '@/Components/Admin/DisciplinaryCaseDrawer.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { adminPath, visitAdmin } from '@/utils/adminVisit';
import { toast } from '@/utils/adminRange';
import { disciplineSeverityMeta, disciplineStatusMeta, pillBase } from '@/utils/hrStatus';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    cases: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    staff: { type: Array, default: () => [] },
    owners: { type: Array, default: () => [] },
    options: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
    opened_id: { type: Number, default: null },
});

const page = usePage();
const today = new Date().toISOString().slice(0, 10);
const createOpen = ref(false);
const rows = ref([...props.cases]);
const openId = ref(null);
const openRow = ref(null);
const panel = ref(null);
const panelLoading = ref(false);
const panelCache = new Map();
const panelInflight = new Map();
const rowRefs = new Map();
const lastFocusEl = ref(null);
let panelSeq = 0;
const statusMeta = (status) => disciplineStatusMeta(status);
const severityMeta = (severity) => disciplineSeverityMeta(severity);

const eyebrow = computed(() => {
    const open = props.stats.open || 0;
    return open === 1 ? '1 open case' : `${open} open cases`;
});

const staffOptions = computed(() => props.staff.map((person) => ({ value: person.id, label: person.name })));
const ownerOptions = computed(() => props.owners.map((person) => ({ value: person.id, label: person.name })));

const form = useForm({
    q: props.filters.q || '',
    status: props.filters.status || '',
    severity: props.filters.severity || '',
    category: props.filters.category || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
    archived: !!props.filters.archived,
});

const createForm = useForm({
    user_id: '',
    owner_id: page.props.auth?.user?.id || '',
    category: 'conduct',
    category_label: '',
    severity: 'moderate',
    incident_on: today,
    description: '',
    reason: '',
});

watch(
    () => props.cases,
    (value) => {
        rows.value = [...value];
    },
);

const filterQuery = (caseId = null) => ({
    q: form.q || undefined,
    status: form.status || undefined,
    severity: form.severity || undefined,
    category: form.category || undefined,
    from: form.from || undefined,
    to: form.to || undefined,
    archived: form.archived ? 1 : undefined,
    case: caseId || undefined,
});

const listUrl = (caseId = null) => route('admin.hr.discipline.index', filterQuery(caseId));

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
        .get(route('admin.hr.discipline.show', id), {
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
            toast({ type: 'error', message: 'The case file did not load.' });
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
    createOpen.value = false;
    lastFocusEl.value = rowRefs.get(item.id) || document.activeElement;
    openId.value = item.id;
    openRow.value = item.reference ? item : (rows.value.find((row) => row.id === item.id) || { id: item.id });
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
    visitAdmin(route('admin.hr.discipline.index', filterQuery()), { preserveState: true, replace: true });
};

const submitCase = () => {
    createForm.post(route('admin.hr.discipline.store'), {
        preserveScroll: true,
        onSuccess: () => { createOpen.value = false; },
    });
};
</script>
