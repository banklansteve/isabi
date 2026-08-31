<template>
    <Head title="Reviews" />

    <AdminChrome
        :title="tab === 'health' ? 'Review health' : 'Reviews'"
        :eyebrow="tab === 'health' ? 'Platform reputation signals' : `${list.total.value.toLocaleString()} client testimonials`"
    />

    <div v-if="tab === 'health'" class="space-y-4">
        <AdminRangePicker :range="range" />
        <AdminAreaChart
            title="Review request-to-completion"
            hint="Percent of sent links that got a response"
            :series="completionSeries"
        />
        <div class="grid gap-4 xl:grid-cols-2">
            <AdminHistogram title="Rating distribution" hint="Healthy platforms cluster at 4–5" :buckets="ratingBuckets" />
            <AdminAreaChart title="Flagged reviews" hint="Trust-and-safety volume" :series="flaggedSeries" />
        </div>
    </div>

    <template v-else>
        <div class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="list.q.value"
                        type="search"
                        placeholder="Search comment, client, artisan…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <div class="no-scrollbar flex gap-1.5 overflow-x-auto lg:justify-end">
                    <select v-model="statusFilter" class="chip-select" :class="statusFilter ? 'chip-select--on' : ''">
                        <option value="">All statuses</option>
                        <option value="flagged">Flagged</option>
                        <option value="hidden">Hidden</option>
                        <option value="removed">Removed</option>
                    </select>
                    <select v-model="ratingFilter" class="chip-select" :class="ratingFilter ? 'chip-select--on' : ''">
                        <option value="">All ratings</option>
                        <option v-for="star in [5, 4, 3, 2, 1]" :key="star" :value="String(star)">{{ star }} stars</option>
                    </select>
                    <select v-model="list.sort.value" class="chip-select">
                        <option value="date_desc">Newest first</option>
                        <option value="date_asc">Oldest first</option>
                        <option value="rating_desc">Highest rated</option>
                        <option value="rating_asc">Lowest rated</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 border-t border-ink/[0.05] pt-3">
                <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Submitted</p>
                <AdminRangePicker :range="range" />
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                title="No reviews match"
                description="Try a different search, rating, or date range."
                icon="ti ti-star"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="review in list.pageItems.value" :key="review.uid">
                    <button
                        :ref="(el) => setRowRef(review.uid, el)"
                        type="button"
                        class="group flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors duration-150 sm:gap-4 sm:px-5"
                        :class="openUid === review.uid ? 'bg-tint/80' : 'hover:bg-pale/70'"
                        @pointerenter="prefetchReview(review)"
                        @focus="prefetchReview(review)"
                        @click="openReview(review)"
                    >
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-sm font-bold text-deep"
                        >
                            {{ review.rating }}★
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-bold text-ink sm:text-[15px]">{{ review.client || 'Client' }}</span>
                                <ReviewStatusBadges :review="review" />
                            </span>
                            <span class="mt-0.5 line-clamp-2 block text-[13px] font-medium leading-relaxed text-ink/55">
                                {{ snapshot(review.comment) }}
                            </span>
                            <span class="mt-1 block text-[12px] font-medium text-ink/35">
                                {{ review.user?.name || 'Unknown artisan' }} · {{ review.submitted_at }}
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
    </template>

    <ReviewDrawer
        :open="!!openUid"
        :row="openRow"
        :panel="panel"
        @close="closeReview"
        @refresh="refreshPanel"
        @updated="onUpdated"
    />
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminHistogram from '@/Components/Admin/AdminHistogram.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import ReviewDrawer from '@/Components/Admin/ReviewDrawer.vue';
import ReviewStatusBadges from '@/Components/Admin/ReviewStatusBadges.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import { toast } from '@/utils/adminRange';
import { adminPath } from '@/utils/adminVisit';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    reviews: { type: Array, default: () => [] },
    completion: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
    flagged_trend: { type: Array, default: () => [] },
    opened_uid: { type: String, default: null },
    can: { type: Object, default: () => ({}) },
});

const { tab } = useAdminTabs({ tab: 'all' });
const range = useDateRange('all');
const rows = ref([...props.reviews]);
const statusFilter = ref(tab.value === 'flagged' ? 'flagged' : tab.value === 'hidden' ? 'hidden' : '');
const ratingFilter = ref('');

watch(() => props.reviews, (value) => { rows.value = [...value]; });
watch(tab, (value) => {
    if (value === 'flagged') statusFilter.value = 'flagged';
    else if (value === 'hidden') statusFilter.value = 'hidden';
    else if (value === 'all' || !value) statusFilter.value = '';
});

const list = useClientList(
    () => rows.value.filter((review) => {
        if (statusFilter.value === 'flagged' && !review.flagged) return false;
        if (statusFilter.value === 'hidden' && !review.hidden) return false;
        if (statusFilter.value === 'removed' && !review.removed) return false;
        if (tab.value === 'flagged' && !review.flagged) return false;
        if (ratingFilter.value && Number(review.rating) !== Number(ratingFilter.value)) return false;
        return range.matches(review.submitted_iso);
    }),
    {
        perPage: 20,
        searchFields: ['comment', 'client', 'user.name', 'user.email', 'job.description'],
        sort: 'date_desc',
        sortMap: { date: 'submitted_iso', rating: 'rating' },
    },
);

watch([tab, statusFilter, ratingFilter, () => range.preset.value], () => {
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

const listUrl = (uid = null) => route('admin.reviews.index', {
    tab: tab.value && tab.value !== 'all' ? tab.value : undefined,
    review: uid || undefined,
});

const replaceListUrl = (href) => {
    window.history.replaceState(window.history.state, '', adminPath(href));
};

const setRowRef = (uid, el) => {
    if (el) rowRefs.set(uid, el);
    else rowRefs.delete(uid);
};

const snapshot = (comment) => {
    const text = String(comment || '').trim();
    if (!text) return 'No written comment.';
    return text.length > 110 ? `${text.slice(0, 110)}…` : text;
};

const completionSeries = computed(() => range.series(props.completion.map((row) => ({ ...row, value: row.rate }))));
const flaggedSeries = computed(() => range.series(props.flagged_trend));
const ratingBuckets = computed(() => {
    const inWindow = rows.value.filter((review) => range.matches(review.submitted_iso));
    return [1, 2, 3, 4, 5].map((star) => ({
        label: String(star),
        value: inWindow.filter((review) => Math.round(Number(review.rating)) === star).length,
    }));
});

const onUpdated = (payload) => {
    const review = payload?.review || payload?.record;
    if (!review?.uid) return;
    rows.value = rows.value.map((row) => (row.uid === review.uid ? { ...row, ...review } : row));
    if (openRow.value?.uid === review.uid) openRow.value = { ...openRow.value, ...review };
    if (payload?.record) {
        const next = { ...(panelCache.get(review.uid) || panel.value || {}), record: payload.record };
        panelCache.set(review.uid, next);
        if (openUid.value === review.uid) panel.value = next;
    }
};

const requestPanel = (uid, { refresh = false } = {}) => {
    if (!refresh && panelCache.has(uid)) return Promise.resolve(panelCache.get(uid));
    if (!refresh && panelInflight.has(uid)) return panelInflight.get(uid);
    const request = axios
        .get(route('admin.reviews.show', uid), { headers: { Accept: 'application/json' } })
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

const prefetchReview = (review) => {
    if (review?.uid) requestPanel(review.uid).catch(() => {});
};

const loadPanel = async ({ refresh = false } = {}) => {
    if (!openUid.value) return;
    const uid = openUid.value;
    const seq = ++panelSeq;
    if (panelCache.has(uid)) panel.value = panelCache.get(uid);
    else if (panel.value?.record?.uid !== uid) panel.value = null;
    try {
        const data = await requestPanel(uid, { refresh });
        if (seq !== panelSeq || openUid.value !== uid) return;
        panel.value = data;
    } catch {
        if (seq === panelSeq) {
            toast({ type: 'error', title: 'Couldn’t load', message: 'The review details did not load.' });
            closeReview();
        }
    }
};

const refreshPanel = () => loadPanel({ refresh: true });

const openReview = (review) => {
    if (!review?.uid) return;
    lastFocusEl.value = rowRefs.get(review.uid) || document.activeElement;
    openUid.value = review.uid;
    openRow.value = review;
    panel.value = panelCache.get(review.uid) || (panel.value?.record?.uid === review.uid ? panel.value : null);
    replaceListUrl(listUrl(review.uid));
    loadPanel();
};

const closeReview = () => {
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
    if (previous && current !== previous && current !== 'health') closeReview();
});

watch(
    () => props.opened_uid,
    (uid) => {
        if (!uid) return;
        openReview(rows.value.find((row) => row.uid === uid) || { uid });
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
