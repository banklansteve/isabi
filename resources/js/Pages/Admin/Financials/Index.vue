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

        <AdminStackedAreaChart
            v-if="tab === 'sources'"
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

        <AdminBarList v-else-if="tab !== 'gateways' && tab !== 'statements' && tab !== 'sources'" class="mt-4" title="Credit pack sales" :items="by_pack" money />
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminDonutChart from '@/Components/Admin/AdminDonutChart.vue';
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
</script>
