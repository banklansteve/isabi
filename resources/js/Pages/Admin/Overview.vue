<template>
    <Head title="Overview" />

    <AdminChrome title="Overview" eyebrow="Platform pulse" />
        <div
            v-if="restricted"
            class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-16 text-center shadow-premium ring-1 ring-ink/[0.05]"
        >
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-pale text-ink/30">
                <i class="ti ti-lock-access" aria-hidden="true" />
            </span>
            <p class="mt-4 text-sm font-bold text-ink">No roles assigned yet</p>
            <p class="mt-1 max-w-md text-[13px] font-medium text-ink/45">
                You can sign in, but a Super Admin still needs to give you a role before you can work in this console.
            </p>
        </div>
        <template v-else>
            <OpsPriorityPanel
                v-if="priorityGroups.length"
                class="mb-6"
                :groups="priorityGroups"
                :open-count="priorityOpenCount"
                heading="Needs your attention"
                acknowledge-escalations
            />

            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <AdminRangePicker :range="range" />
                <div class="flex rounded-full bg-white p-1 ring-1 ring-ink/[0.06]">
                    <button
                        v-for="mode in ['dau', 'wau', 'mau']"
                        :key="mode"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-[12px] font-semibold capitalize transition-all duration-150 active:scale-[0.97]"
                        :class="activeMode === mode ? 'bg-base-action text-white' : 'text-ink/45 hover:text-deep'"
                        @click="activeMode = mode"
                    >
                        {{ mode }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <AdminKpiCard
                    v-for="kpi in rangeKpis"
                    :key="kpi.key"
                    :label="kpi.label"
                    :value="kpi.value"
                    :delta="kpi.delta"
                    :delta-suffix="kpi.delta_suffix"
                />
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-5">
                <AdminFunnelChart
                    class="xl:col-span-3"
                    title="Signup funnel"
                    hint="Where new artisans stall, as a share of everyone who signed up"
                    :steps="funnel"
                />
                <AdminDonutChart class="xl:col-span-2" title="Users by plan" :items="plans" empty-label="No users yet" />
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                <AdminAreaChart title="Revenue" hint="Completed purchases in this range" money :series="revenueSeries" />
                <AdminAreaChart :title="activeTitle" hint="Artisans who logged a job" :series="activeSeries" />
            </div>

            <!-- Revenue health -->
            <section class="mt-8 grid gap-4">
                <div>
                    <h2 class="text-[15px] font-bold tracking-tight text-ink">Revenue</h2>
                    <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/45">
                        Is the business actually working — trend, sources, plans, ARPU, cohorts, and where money is coming from geographically.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <AdminKpiCard
                        label="ARPU (this month)"
                        :value="arpu_kpi.value || '₦0'"
                        :delta="arpu_kpi.delta"
                        delta-suffix="vs last month"
                    />
                    <AdminKpiCard
                        label="Active payers base"
                        :value="formatCompact(arpu_kpi.active || 0)"
                        :delta="{ label: 'Job-active artisans this month', tone: 'neutral' }"
                    />
                    <AdminKpiCard
                        label="Month revenue (ARPU base)"
                        :value="formatNaira(arpu_kpi.revenue || 0)"
                        :delta="{ label: 'Completed purchases', tone: 'neutral' }"
                    />
                    <AdminKpiCard
                        label="Top revenue state"
                        :value="topStateLabel"
                        :delta="topStateDelta"
                    />
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <AdminAreaChart
                        title="Revenue trend"
                        hint="Completed purchases — use the range picker above"
                        money
                        :series="revenueSeries"
                    />
                    <AdminStackedAreaChart
                        title="Revenue by source"
                        :labels="stackedSource.labels"
                        :layers="stackedSource.layers"
                    />
                </div>

                <div class="grid gap-4 xl:grid-cols-5">
                    <AdminDonutChart
                        class="xl:col-span-2"
                        title="Plan / purchase distribution"
                        :items="purchase_mix"
                        empty-label="No purchases yet"
                    />
                    <AdminAreaChart
                        class="xl:col-span-3"
                        title="ARPU trend"
                        hint="Average revenue per job-active artisan each month"
                        money
                        :series="arpuSeries"
                    />
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <AdminColumnChart
                        title="Lifetime per signup cohort"
                        hint="Lifetime lifetime revenue from artisans who signed up that month"
                        money
                        :series="cohortSeries"
                    />
                    <AdminBarList
                        title="Avg revenue per paying artisan (state)"
                        hint="Highest overall revenue sources by state"
                        money
                        :items="revenue_by_state"
                    />
                </div>

                <AdminBarList
                    title="Avg revenue per paying artisan (city / LGA)"
                    hint="Where local monetization is concentrating"
                    money
                    :items="revenue_by_city"
                />
            </section>
        </template>
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminColumnChart from '@/Components/Admin/AdminColumnChart.vue';
import AdminDonutChart from '@/Components/Admin/AdminDonutChart.vue';
import AdminFunnelChart from '@/Components/Admin/AdminFunnelChart.vue';
import AdminKpiCard from '@/Components/Admin/AdminKpiCard.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import AdminStackedAreaChart from '@/Components/Admin/AdminStackedAreaChart.vue';
import OpsPriorityPanel from '@/Components/Admin/OpsPriorityPanel.vue';
import { useDateRange } from '@/Composables/useDateRange';
import { formatCompact, formatNaira, sliceStacked } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();

const props = defineProps({
    kpis: { type: Array, default: () => [] },
    revenue: { type: Array, default: () => [] },
    revenue_daily: { type: Array, default: () => [] },
    signups_daily: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    funnel: { type: Array, default: () => [] },
    active_users: { type: Array, default: () => [] },
    monthly_active: { type: Array, default: () => [] },
    active_daily: { type: Array, default: () => [] },
    currency_symbol: { type: String, default: '₦' },
    restricted: { type: Boolean, default: false },
    revenue_by_source: { type: Object, default: () => ({ labels: [], layers: [], dates: [] }) },
    purchase_mix: { type: Array, default: () => [] },
    arpu: { type: Array, default: () => [] },
    arpu_kpi: { type: Object, default: () => ({ value: '₦0', active: 0, revenue: 0 }) },
    revenue_cohorts: { type: Array, default: () => [] },
    revenue_by_state: { type: Array, default: () => [] },
    revenue_by_city: { type: Array, default: () => [] },
});

const range = useDateRange('this_month');
const activeMode = ref('wau');

const adminInbox = computed(() => page.props.admin_inbox || null);
const priorityGroups = computed(() => adminInbox.value?.priority_groups || []);
const priorityOpenCount = computed(() => adminInbox.value?.open_count || 0);

const revenueSeries = computed(() => range.series(props.revenue_daily?.length ? props.revenue_daily : props.revenue));
const signupSeries = computed(() => range.series(props.signups_daily || []));
const stackedSource = computed(() => sliceStacked(props.revenue_by_source, range.bounds.value));
const arpuSeries = computed(() => range.series(props.arpu || []));

const cohortSeries = computed(() =>
    (props.revenue_cohorts || []).map((row) => ({
        label: row.label,
        date: row.date,
        value: row.value,
    })),
);

const topState = computed(() => (props.revenue_by_state || [])[0] || null);
const topStateLabel = computed(() => topState.value?.label || '—');
const topStateDelta = computed(() => {
    if (!topState.value) return { label: 'No geo revenue yet', tone: 'neutral' };
    return {
        label: `${formatNaira(topState.value.total)} · ${topState.value.payers} payers`,
        tone: 'neutral',
    };
});

const activeSeries = computed(() => {
    if (activeMode.value === 'mau') {
        return range.series(props.monthly_active);
    }
    if (activeMode.value === 'dau') {
        return range.series(props.active_daily);
    }
    return range.series(props.active_users);
});

const activeTitle = computed(() => {
    if (activeMode.value === 'mau') return 'Monthly active users';
    if (activeMode.value === 'dau') return 'Daily active users';
    return 'Weekly active users';
});

const rangeKpis = computed(() => {
    const revenue = revenueSeries.value.reduce((sum, point) => sum + Number(point.value || 0), 0);
    const signups = signupSeries.value.reduce((sum, point) => sum + Number(point.value || 0), 0);
    const activePeak = activeSeries.value.reduce((max, point) => Math.max(max, Number(point.value || 0)), 0);

    return props.kpis.map((kpi) => {
        if (kpi.key === 'revenue') {
            return {
                ...kpi,
                label: 'Revenue in range',
                value: formatNaira(revenue),
                delta: revenue
                    ? {
                          label: signups ? `+${formatCompact(signups)} signups` : 'Completed purchases',
                          tone: 'up',
                      }
                    : { label: 'No purchases in this window', tone: 'neutral' },
                delta_suffix: '',
            };
        }

        if (kpi.key === 'users') {
            return {
                ...kpi,
                delta: signups
                    ? { label: `+${formatCompact(signups)} in this range`, tone: 'up' }
                    : kpi.delta,
            };
        }

        if (kpi.key === 'jobs' && activePeak) {
            return {
                ...kpi,
                delta: { label: `${formatCompact(activePeak)} peak ${activeMode.value.toUpperCase()}`, tone: 'neutral' },
            };
        }

        return kpi;
    });
});
</script>
