<template>
    <Head title="Referrals" />

    <AdminChrome title="Referrals" eyebrow="Growth program" />
        <div class="mb-4">
            <AdminRangePicker :range="range" />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <AdminKpiCard label="Invites" :value="rangeTotals.signed_up" />
            <AdminKpiCard label="Rewarded" :value="rangeTotals.rewarded" />
            <AdminKpiCard label="Tokens paid" :value="rangeTotals.tokens" />
            <AdminKpiCard
                label="Referral → active"
                :value="`${insights.conversion_rate ?? 0}%`"
                :delta="insights.referred ? { label: `${insights.active} of ${insights.referred} logged a job`, tone: 'neutral' } : null"
            />
        </div>

        <div v-if="tab === 'signals'" class="mt-4 rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
            <h3 class="text-[15px] font-bold text-ink">Fraud signals</h3>
            <p class="mt-1 text-[13px] text-ink/45">8+ referrals from one account in a single day.</p>
            <AdminEmpty
                v-if="!signals.length"
                title="No spikes"
                description="Nothing looks unusually clustered right now."
                icon="ti ti-shield-check"
            />
            <ul v-else class="mt-4 divide-y divide-ink/[0.06]">
                <li v-for="(row, i) in signals" :key="i" class="flex justify-between py-3 text-sm">
                    <span class="font-semibold">{{ row.user?.name }} · {{ row.user?.email }}</span>
                    <span class="text-coral">{{ row.total }} on {{ row.day }}</span>
                </li>
            </ul>
        </div>

        <div v-else class="mt-4 grid gap-4 lg:grid-cols-2">
            <AdminAreaChart class="lg:col-span-2" title="Referral signups" :series="range.series(insights.trend || [])" />
            <AdminBarList title="Top referrers" :items="top.map((row) => ({ label: row.user?.name || 'Unknown', value: row.total }))" />
            <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-[15px] font-bold text-ink">Recent</h3>
                    <input
                        v-model="list.q.value"
                        type="search"
                        placeholder="Search names…"
                        class="w-full rounded-xl border border-ink/10 px-3 py-2 text-[13px] font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:w-48"
                    />
                </div>
                <AdminEmpty
                    v-if="!list.pageItems.value.length"
                    title="No referrals in this range"
                    description="Invite activity will show up here."
                    icon="ti ti-gift"
                />
                <ul v-else class="mt-3 divide-y divide-ink/[0.06]">
                    <li v-for="row in list.pageItems.value" :key="row.id" class="py-3 text-[13px]">
                        <span class="font-bold text-ink">{{ row.referrer?.name }}</span>
                        <span class="text-ink/40"> → </span>
                        <span class="font-semibold text-ink/70">{{ row.referred?.name }}</span>
                        <span class="ml-2 text-ink/35">{{ row.status }} · {{ row.when }}</span>
                    </li>
                </ul>
                <AdminClientPager
                    :page="list.page.value"
                    :pages="list.pageCount.value"
                    :total="list.total.value"
                    :per-page="list.perPage"
                    @update:page="list.page.value = $event"
                />
            </div>
        </div>
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminKpiCard from '@/Components/Admin/AdminKpiCard.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    totals: { type: Object, required: true },
    top: { type: Array, default: () => [] },
    signals: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    insights: { type: Object, default: () => ({}) },
});

const { tab } = useAdminTabs({ tab: '' });
const range = useDateRange('all');

const inRangeRecent = computed(() => props.recent.filter((row) => range.matches(row.created_iso)));

const rangeTotals = computed(() => {
    const rows = inRangeRecent.value;
    if (range.preset.value === 'all') {
        return props.totals;
    }

    return {
        signed_up: rows.length,
        rewarded: rows.filter((row) => row.status === 'rewarded').length,
        tokens: rows.reduce((sum, row) => sum + Number(row.tokens || 0), 0),
    };
});

const list = useClientList(
    () => inRangeRecent.value,
    {
        perPage: 12,
        searchFields: ['referrer.name', 'referred.name', 'status'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch(() => range.preset.value, () => {
    list.page.value = 1;
});
</script>
