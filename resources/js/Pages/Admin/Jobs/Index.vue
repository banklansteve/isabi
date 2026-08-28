<template>
    <Head title="Job logs" />

    <AdminChrome title="Job logs" eyebrow="Platform work" />
    <div class="mb-4 flex flex-col gap-3">
        <AdminRangePicker :range="range" />
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                v-model="list.q.value"
                type="search"
                placeholder="Search jobs, clients, artisans…"
                class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-md"
            />
            <select v-model="list.sort.value" class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15">
                <option value="date_desc">Newest</option>
                <option value="date_asc">Oldest</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            v-if="!list.pageItems.value.length"
            title="No jobs here"
            description="Logged jobs across Isabi will show up in this list."
            icon="ti ti-briefcase"
        />
        <ul v-else class="divide-y divide-ink/[0.06]">
            <li v-for="job in list.pageItems.value" :key="job.uid">
                <button
                    :ref="(el) => setRowRef(job.uid, el)"
                    type="button"
                    class="flex w-full items-start gap-3 px-4 py-4 text-left transition-colors duration-150 sm:px-5"
                    :class="openUid === job.uid ? 'bg-tint/80' : 'hover:bg-pale/70'"
                    @pointerenter="prefetchJob(job)"
                    @focus="prefetchJob(job)"
                    @pointerdown="prefetchJob(job)"
                    @click="openJob(job)"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-bold text-ink">{{ job.description }}</p>
                            <span
                                v-if="job.flagged"
                                class="rounded-full bg-coral-tint px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-coral-deep"
                            >
                                Flagged
                            </span>
                            <span
                                v-if="job.removed"
                                class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/45"
                            >
                                Removed
                            </span>
                            <span
                                v-else-if="job.hidden"
                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800"
                            >
                                Hidden
                            </span>
                            <span
                                v-if="job.referred"
                                class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700"
                            >
                                Referred
                            </span>
                        </div>
                        <p class="mt-1 text-[13px] font-medium text-ink/45">
                            {{ job.user?.name }} · {{ job.client_name || 'No client' }} · {{ job.worked_on }}
                        </p>
                        <p v-if="job.backdated_days >= 30" class="mt-1 text-[12px] font-semibold text-coral">
                            Backdated {{ job.backdated_days }} days
                        </p>
                    </div>
                    <i class="ti ti-chevron-right mt-1 shrink-0 text-ink/25" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </div>
    <AdminClientPager
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

watch(
    () => props.jobs,
    (value) => {
        rows.value = [...value];
    },
);

const list = useClientList(
    () => rows.value.filter((job) => {
        if (tab.value === 'flagged' && !job.flagged) return false;
        if (tab.value === 'suspicious' && job.backdated_days < 30) return false;
        return range.matches(job.created_iso);
    }),
    {
        perPage: 20,
        searchFields: ['description', 'client_name', 'user.name', 'user.email'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([tab, () => range.preset.value], () => {
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
    if (el) {
        rowRefs.set(uid, el);
        return;
    }
    rowRefs.delete(uid);
};

const onUpdated = (payload) => {
    const job = payload?.job || payload?.record;
    if (!job?.uid) {
        return;
    }
    rows.value = rows.value.map((row) => (row.uid === job.uid ? { ...row, ...job } : row));
    if (openRow.value?.uid === job.uid) {
        openRow.value = { ...openRow.value, ...job };
    }
    const cached = panelCache.get(job.uid);
    if (payload?.record) {
        const next = {
            ...(cached || panel.value || {}),
            record: payload.record,
        };
        panelCache.set(job.uid, next);
        if (openUid.value === job.uid) {
            panel.value = next;
        }
    }
};

const requestPanel = (uid, { refresh = false } = {}) => {
    if (!refresh && panelCache.has(uid)) {
        return Promise.resolve(panelCache.get(uid));
    }
    if (!refresh && panelInflight.has(uid)) {
        return panelInflight.get(uid);
    }

    const request = axios
        .get(route('admin.jobs.show', uid), {
            headers: { Accept: 'application/json' },
        })
        .then(({ data }) => {
            panelCache.set(uid, data);
            return data;
        })
        .finally(() => {
            if (panelInflight.get(uid) === request) {
                panelInflight.delete(uid);
            }
        });

    panelInflight.set(uid, request);
    return request;
};

const prefetchJob = (job) => {
    if (!job?.uid) {
        return;
    }
    requestPanel(job.uid).catch(() => {});
};

const applyPanel = (uid, data) => {
    panelCache.set(uid, data);
    if (openUid.value === uid) {
        panel.value = data;
    }
};

const loadPanel = async ({ refresh = false } = {}) => {
    if (!openUid.value) {
        return;
    }
    const uid = openUid.value;
    const seq = ++panelSeq;
    const cached = panelCache.get(uid);
    if (cached) {
        panel.value = cached;
    } else if (panel.value?.record?.uid !== uid) {
        panel.value = null;
    }
    try {
        const data = await requestPanel(uid, { refresh });
        if (seq !== panelSeq || openUid.value !== uid) {
            return;
        }
        applyPanel(uid, data);
    } catch {
        if (seq === panelSeq) {
            toast({ type: 'error', message: 'The job did not load.' });
            closeJob();
        }
    }
};

const refreshPanel = () => loadPanel({ refresh: true });

const openJob = (job) => {
    if (!job?.uid) {
        return;
    }
    lastFocusEl.value = rowRefs.get(job.uid) || document.activeElement;
    openUid.value = job.uid;
    openRow.value = job;
    const cached = panelCache.get(job.uid);
    if (cached) {
        panel.value = cached;
    } else if (panel.value?.record?.uid !== job.uid) {
        panel.value = null;
    }
    replaceListUrl(listUrl(job.uid));
    loadPanel();
};

const closeJob = () => {
    const uid = openUid.value;
    openUid.value = null;
    replaceListUrl(listUrl());
    nextTick(() => {
        const target = lastFocusEl.value || rowRefs.get(uid);
        if (target && typeof target.focus === 'function') {
            target.focus();
        }
    });
};

watch(tab, (current, previous) => {
    if (previous && current !== previous) {
        closeJob();
    }
});

watch(
    () => props.opened_uid,
    (uid) => {
        if (!uid) {
            return;
        }
        const job = rows.value.find((row) => row.uid === uid) || { uid };
        openJob(job);
    },
    { immediate: true },
);
</script>
