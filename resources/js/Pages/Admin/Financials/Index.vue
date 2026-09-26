<template>
    <Head title="Financials" />

    <AdminChrome title="Financials" eyebrow="Revenue & reconciliation" />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <AdminRangePicker class="flex-1" :range="range" />
            <a
                :href="route('admin.financials.export')"
                class="rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-base-hover active:scale-[0.98]"
            >
                Export CSV
            </a>
        </div>

        <div class="mb-4 grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <AdminKpiCard label="All-time" :value="totals.all_time" />
            <AdminKpiCard label="In this range" :value="rangeRevenueLabel" />
            <AdminKpiCard label="Orders" :value="totals.orders" />
            <AdminKpiCard
                label="Annual renewal"
                :value="`${totals.renewal_rate?.rate ?? 0}%`"
                :delta="totals.renewal_rate?.due ? { label: `${totals.renewal_rate.renewed} of ${totals.renewal_rate.due} due`, tone: 'neutral' } : null"
            />
        </div>

        <!-- Credits page conversion (product analytics summaries) -->
        <div v-if="tab === 'conversion'" class="grid gap-4">
            <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] sm:p-6">
                <p class="text-[13px] font-semibold text-ink/45">Credits page → purchase</p>
                <p class="mt-2 text-[2.4rem] font-semibold tracking-tight text-ink tabular-nums sm:text-[2.75rem]">
                    {{ rangeConversion.rate }}%
                </p>
                <p class="mt-2 text-[13px] font-medium text-ink/45">
                    {{ rangeConversion.purchasers.toLocaleString() }} purchasers of
                    {{ rangeConversion.viewers.toLocaleString() }} credits-page viewers in this range
                </p>
                <p class="mt-1 text-[12px] font-medium text-ink/35">
                    {{ credits_conversion.hint || 'From nightly analytics summaries — monetization-page effectiveness, not the signup funnel.' }}
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <AdminFunnelChart
                    title="Interest → action"
                    hint="Credits page views vs completed token purchases"
                    :steps="conversionFunnelSteps"
                />
                <AdminAreaChart
                    title="Conversion rate by week"
                    hint="Weekly credits-page viewers who purchased (summary rollups)"
                    :series="range.series(credits_conversion.weekly_rate || [])"
                />
            </div>
        </div>

        <AdminStackedAreaChart
            v-else-if="tab === 'sources'"
            title="Revenue by source"
            :labels="stacked.labels"
            :layers="stacked.layers"
        />
        <AdminAreaChart
            v-else-if="tab === 'revenue' || !tab"
            title="Revenue trend"
            money
            :series="revenueSeries"
        />

        <AdminBarList
            v-if="tab === 'sources'"
            class="mt-4"
            title="Credit pack sales"
            :items="by_pack"
            money
        />
        <AdminAreaChart
            v-if="tab === 'sources'"
            class="mt-4"
            title="Referral credit liability"
            hint="Foregone revenue from referral tokens, at starter-pack unit value"
            money
            :series="range.series(referral_liability)"
        />

        <div v-if="tab === 'gateways'" class="grid gap-4 lg:grid-cols-2">
            <AdminBarList title="By processor" :items="by_processor" money />
            <AdminDonutChart title="Recurring vs one-off" :items="split" />
        </div>

        <div v-else-if="tab === 'statements'" class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
            <h3 class="text-[15px] font-bold text-ink">Statements</h3>
            <p class="mt-2 text-sm text-ink/50">
                Download a CSV of every token purchase for Paystack / Flutterwave reconciliation.
            </p>
            <a :href="route('admin.financials.export')" class="mt-4 inline-flex rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-base-hover">
                Download ledger
            </a>
        </div>

        <AdminBarList
            v-else-if="tab !== 'gateways' && tab !== 'statements' && tab !== 'sources' && tab !== 'conversion'"
            class="mt-4"
            title="Credit pack sales"
            :items="by_pack"
            money
        />
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminDonutChart from '@/Components/Admin/AdminDonutChart.vue';
import AdminFunnelChart from '@/Components/Admin/AdminFunnelChart.vue';
import AdminKpiCard from '@/Components/Admin/AdminKpiCard.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import AdminStackedAreaChart from '@/Components/Admin/AdminStackedAreaChart.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useDateRange } from '@/Composables/useDateRange';
import { formatNaira, sliceStacked } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    revenue: { type: Array, default: () => [] },
    revenue_daily: { type: Array, default: () => [] },
    by_processor: { type: Array, default: () => [] },
    by_pack: { type: Array, default: () => [] },
    split: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    revenue_by_source: { type: Object, default: () => ({ labels: [], layers: [] }) },
    referral_liability: { type: Array, default: () => [] },
    credits_conversion: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    currency_symbol: { type: String, default: '₦' },
});

const { tab } = useAdminTabs({ tab: props.filters.tab || 'revenue' });
const range = useDateRange('this_month');
const revenueSeries = computed(() => range.series(props.revenue_daily?.length ? props.revenue_daily : props.revenue));
const stacked = computed(() => sliceStacked(props.revenue_by_source, range.bounds.value));
const rangeRevenueLabel = computed(() =>
    formatNaira(revenueSeries.value.reduce((sum, point) => sum + Number(point.value || 0), 0)),
);

const rangeConversion = computed(() => {
    const funnel = props.credits_conversion?.funnel || [];
    const viewersSeries = props.credits_conversion?.viewers_series || [];
    const purchasersSeries = props.credits_conversion?.purchasers_series || [];

    if (viewersSeries.length || purchasersSeries.length) {
        const viewers = range.series(viewersSeries).reduce((sum, p) => sum + (Number(p.value) || 0), 0);
        const purchasers = range.series(purchasersSeries).reduce((sum, p) => sum + (Number(p.value) || 0), 0);
        const rate = viewers > 0 ? Math.round((purchasers / viewers) * 100) : 0;
        return { viewers, purchasers, rate };
    }

    // Fallback to server snapshot (trailing 30d)
    return {
        viewers: Number(props.credits_conversion?.viewers || funnel[0]?.value || 0),
        purchasers: Number(props.credits_conversion?.purchasers || funnel[1]?.value || 0),
        rate: Number(props.credits_conversion?.rate || 0),
    };
});

const conversionFunnelSteps = computed(() => {
    const viewers = rangeConversion.value.viewers;
    const purchasers = rangeConversion.value.purchasers;
    const base = Math.max(viewers, 1);

    return [
        { label: 'Viewed credits', value: viewers, percent: viewers > 0 ? 100 : 0 },
        {
            label: 'Purchased tokens',
            value: purchasers,
            percent: viewers > 0 ? Math.round((purchasers / base) * 100) : 0,
        },
    ];
});
</script>
