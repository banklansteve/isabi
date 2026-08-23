<template>
    <Head title="Analytics" />

    <AdminChrome title="Analytics" eyebrow="How the platform is moving" />
        <div class="mb-4">
            <AdminRangePicker :range="range" />
        </div>

        <div v-if="tab === 'geography'" class="grid gap-4 lg:grid-cols-2">
            <AdminBarList title="Users by state" :items="geography" />
            <AdminBarList title="Users by city / LGA" :items="cities" />
            <AdminBarList class="lg:col-span-2" title="Users by trade" :items="trades" />
        </div>

        <AdminCohortTable
            v-else-if="tab === 'retention'"
            :rows="retention"
        />

        <div v-else-if="tab === 'engagement'" class="grid gap-4">
            <div class="flex rounded-full bg-white p-1 ring-1 ring-ink/[0.06] sm:w-fit">
                <button
                    v-for="mode in ['dau', 'wau', 'mau']"
                    :key="mode"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-[12px] font-semibold uppercase transition-all duration-150"
                    :class="activeMode === mode ? 'bg-base-action text-white' : 'text-ink/45 hover:text-deep'"
                    @click="activeMode = mode"
                >
                    {{ mode }}
                </button>
            </div>
            <AdminAreaChart :title="activeTitle" hint="Artisans who logged at least one job" :series="activeSeries" />
            <AdminColumnChart title="Jobs per active user" hint="Average depth of use each week" :series="jobsSeries" />
        </div>

        <div v-else class="grid gap-4">
            <div class="grid gap-4 xl:grid-cols-5">
                <AdminFunnelChart
                    class="xl:col-span-3"
                    title="Signup funnel"
                    hint="Share of all signups that reached each milestone"
                    :steps="funnel"
                />
                <AdminDonutChart class="xl:col-span-2" title="Acquisition source" :items="acquisition" />
            </div>
            <div class="grid gap-4 xl:grid-cols-2">
                <AdminAreaChart title="New signups" :series="signupSeries" />
                <AdminAreaChart title="Cumulative users" hint="Running total of artisan accounts" :series="cumulative_users" />
            </div>
            <AdminAreaChart title="Monthly signups" :series="growthSeries" />
        </div>
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminCohortTable from '@/Components/Admin/AdminCohortTable.vue';
import AdminColumnChart from '@/Components/Admin/AdminColumnChart.vue';
import AdminDonutChart from '@/Components/Admin/AdminDonutChart.vue';
import AdminFunnelChart from '@/Components/Admin/AdminFunnelChart.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useDateRange } from '@/Composables/useDateRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    user_growth: { type: Array, default: () => [] },
    daily_signups: { type: Array, default: () => [] },
    cumulative_users: { type: Array, default: () => [] },
    acquisition: { type: Array, default: () => [] },
    active_users: { type: Array, default: () => [] },
    monthly_active: { type: Array, default: () => [] },
    active_daily: { type: Array, default: () => [] },
    jobs_per_active: { type: Array, default: () => [] },
    geography: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    trades: { type: Array, default: () => [] },
    retention: { type: Array, default: () => [] },
    funnel: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { tab } = useAdminTabs({ tab: props.filters.tab || 'growth' });
const range = useDateRange('last_30');
const activeMode = ref('wau');

const signupSeries = computed(() => range.series(props.daily_signups));
const growthSeries = computed(() => range.series(props.user_growth));
const jobsSeries = computed(() => range.series(props.jobs_per_active));
const activeSeries = computed(() => {
    if (activeMode.value === 'mau') return range.series(props.monthly_active);
    if (activeMode.value === 'dau') return range.series(props.active_daily);
    return range.series(props.active_users);
});
const activeTitle = computed(() => {
    if (activeMode.value === 'mau') return 'Monthly active users';
    if (activeMode.value === 'dau') return 'Daily active users';
    return 'Weekly active users';
});
</script>
