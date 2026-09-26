<template>
    <Head title="Analytics" />

    <AdminChrome title="Analytics" eyebrow="How people use the product" />
        <div v-if="tab !== 'live'" class="mb-4">
            <AdminRangePicker :range="range" />
        </div>

        <div
            v-if="!has_summary_data && (tab === 'engagement' || tab === 'retention' || tab === 'product')"
            class="mb-4 rounded-2xl bg-tint/60 px-4 py-3 text-[13px] font-medium text-ink/60 ring-1 ring-ink/[0.05]"
        >
            Nightly product summaries are still catching up. Login and page charts stay sparse until rollups accumulate —
            job-activity reports below still work from operational data.
        </div>

        <!-- Live presence -->
        <div v-if="tab === 'live'" class="grid gap-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-[15px] font-bold tracking-tight text-ink">Who’s online</h2>
                    <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/45">
                        {{ live_presence.hint || 'Live presence from recent activity — not daily engagement charts.' }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full bg-white px-3.5 py-1.5 text-[12px] font-semibold text-ink/60 ring-1 ring-ink/[0.08] transition hover:text-deep"
                    :disabled="refreshing"
                    @click="refreshPresence"
                >
                    {{ refreshing ? 'Refreshing…' : 'Refresh' }}
                </button>
            </div>

            <p class="text-[12px] font-semibold uppercase tracking-wide text-ink/35">Online now</p>
            <div class="grid gap-3 sm:grid-cols-3">
                <AdminKpiCard
                    label="Super admins"
                    :value="live_presence.online_now?.super_admins ?? 0"
                    :delta="{ label: 'Active in the last ~2 minutes', tone: 'neutral' }"
                />
                <AdminKpiCard
                    label="Operations staff"
                    :value="live_presence.online_now?.operations ?? 0"
                    :delta="{ label: 'Active in the last ~2 minutes', tone: 'neutral' }"
                />
                <AdminKpiCard
                    label="Users"
                    :value="live_presence.online_now?.users ?? 0"
                    :delta="{ label: 'Artisans active in the last ~2 minutes', tone: 'neutral' }"
                />
            </div>

            <p class="mt-2 text-[12px] font-semibold uppercase tracking-wide text-ink/35">Seen today</p>
            <div class="grid gap-3 sm:grid-cols-3">
                <AdminKpiCard
                    label="Super admins today"
                    :value="live_presence.online_today?.super_admins ?? 0"
                    :delta="{ label: 'At least one activity ping today', tone: 'neutral' }"
                />
                <AdminKpiCard
                    label="Operations today"
                    :value="live_presence.online_today?.operations ?? 0"
                    :delta="{ label: 'At least one activity ping today', tone: 'neutral' }"
                />
                <AdminKpiCard
                    label="Users today"
                    :value="live_presence.online_today?.users ?? 0"
                    :delta="{ label: 'Artisans with activity today', tone: 'neutral' }"
                />
            </div>
        </div>

        <!-- Geography -->
        <div v-else-if="tab === 'geography'" class="grid gap-4 lg:grid-cols-2">
            <AdminBarList title="Users by state" :items="geography" />
            <AdminBarList title="Users by city / LGA" :items="cities" />
            <AdminBarList class="lg:col-span-2" title="Users by trade" :items="trades" />
        </div>

        <!-- Retention -->
        <div v-else-if="tab === 'retention'" class="grid gap-4">
            <section class="grid gap-3">
                <ReportSection
                    title="Login retention"
                    description="Whether artisans keep signing in after signup — from nightly analytics summaries."
                />
                <AdminCohortTable
                    title="Login retention by signup cohort"
                    hint="Share of each signup month still logging in within 30 / 60 / 90 days."
                    :rows="retention"
                />
            </section>

            <section class="grid gap-3 border-t border-ink/[0.06] pt-6">
                <ReportSection
                    title="Job activity retention"
                    description="Whether artisans keep logging finished work after signup — from work-log activity."
                />
                <AdminCohortTable
                    title="Job retention by signup cohort"
                    hint="Share of each signup month that logged at least one job within 30 / 60 / 90 days."
                    :rows="job_retention"
                />
            </section>
        </div>

        <!-- Product -->
        <div v-else-if="tab === 'product'" class="grid gap-4">
            <ReportSection
                title="Page popularity"
                description="Which in-app pages get opened most — relative view counts in the selected range."
            />
            <AdminBarList
                title="Page views"
                hint="Lowest traffic is often a discoverability clue, not proof of low interest."
                :items="rangePageItems"
                :lowest-key="lowestPageKey"
            />

            <section class="grid gap-3 border-t border-ink/[0.06] pt-6">
                <ReportSection
                    title="Secondary feature usage"
                    description="Whether quieter features are actually used — signals for continued investment, not headline KPIs."
                />
                <div class="grid gap-3 sm:grid-cols-2">
                    <AdminKpiCard
                        :label="feature_usage.export?.label || 'Work log export'"
                        :value="feature_usage.export?.value || '0%'"
                        :delta="featureDelta(feature_usage.export)"
                    />
                    <AdminKpiCard
                        :label="feature_usage.review_messages?.label || 'Review message customization'"
                        :value="feature_usage.review_messages?.value || '0%'"
                        :delta="featureDelta(feature_usage.review_messages)"
                    />
                </div>
                <p class="text-[12px] font-medium text-ink/40">{{ feature_usage.export?.hint }}</p>
                <p class="text-[12px] font-medium text-ink/40">{{ feature_usage.review_messages?.hint }}</p>
            </section>

            <p class="text-[13px] font-medium text-ink/50">
                Credits page → purchase conversion lives with
                <Link :href="route('admin.financials.index', { tab: 'conversion' })" class="font-semibold text-base-action hover:underline">
                    Financials → Conversion
                </Link>
                — monetization-page effectiveness.
            </p>
        </div>

        <!-- Engagement -->
        <div v-else-if="tab === 'engagement'" class="grid gap-4">
            <section class="grid gap-3">
                <ReportSection
                    title="Login engagement"
                    description="How often artisans return and sign in — from nightly analytics summaries."
                />
                <div class="grid gap-3 sm:grid-cols-3">
                    <AdminKpiCard
                        v-for="(card, idx) in [active_kpi.dau, active_kpi.wau, active_kpi.mau]"
                        :key="card?.label || `kpi-${idx}`"
                        :label="card?.label || '—'"
                        :value="card?.value || '0'"
                        :delta="card?.delta"
                    />
                </div>
                <div class="flex rounded-full bg-white p-1 ring-1 ring-ink/[0.06] sm:w-fit">
                    <button
                        v-for="mode in ['dau', 'wau', 'mau']"
                        :key="mode"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-[12px] font-semibold uppercase transition-all duration-150"
                        :class="loginMode === mode ? 'bg-base-action text-white' : 'text-ink/45 hover:text-deep'"
                        @click="loginMode = mode"
                    >
                        {{ mode }}
                    </button>
                </div>
                <AdminAreaChart
                    :title="loginTitle"
                    hint="Distinct artisans with at least one login"
                    :series="loginSeries"
                />
                <AdminColumnChart
                    title="Login frequency (trailing 30 days)"
                    hint="Shape of engagement across the user base"
                    :series="loginFrequencySeries"
                />
            </section>

            <section class="grid gap-3 border-t border-ink/[0.06] pt-6">
                <ReportSection
                    title="Job activity engagement"
                    description="Artisans who logged finished work — depth of product use from work logs."
                />
                <div class="flex rounded-full bg-white p-1 ring-1 ring-ink/[0.06] sm:w-fit">
                    <button
                        v-for="mode in ['dau', 'wau', 'mau']"
                        :key="mode"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-[12px] font-semibold uppercase transition-all duration-150"
                        :class="jobMode === mode ? 'bg-base-action text-white' : 'text-ink/45 hover:text-deep'"
                        @click="jobMode = mode"
                    >
                        {{ mode }}
                    </button>
                </div>
                <AdminAreaChart
                    :title="jobTitle"
                    hint="Artisans who logged at least one job"
                    :series="jobSeries"
                />
                <AdminColumnChart
                    title="Jobs per active user"
                    hint="Average depth of use each week"
                    :series="jobsPerActiveSeries"
                />
            </section>
        </div>

        <!-- Growth -->
        <div v-else class="grid gap-4">
            <ReportSection
                title="Acquisition & growth"
                description="How the artisan base is growing — signup funnel, sources, and volume over time."
            />
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
            <AdminAreaChart
                title="Jobs logged"
                hint="Finished work entries created over time"
                :series="jobsTrendSeries"
            />
        </div>
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminCohortTable from '@/Components/Admin/AdminCohortTable.vue';
import AdminColumnChart from '@/Components/Admin/AdminColumnChart.vue';
import AdminDonutChart from '@/Components/Admin/AdminDonutChart.vue';
import AdminFunnelChart from '@/Components/Admin/AdminFunnelChart.vue';
import AdminKpiCard from '@/Components/Admin/AdminKpiCard.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useDateRange } from '@/Composables/useDateRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, h, onMounted, onUnmounted, ref } from 'vue';

const ReportSection = defineComponent({
    name: 'ReportSection',
    props: {
        title: { type: String, required: true },
        description: { type: String, default: '' },
    },
    setup(props) {
        return () =>
            h('div', { class: 'mb-1' }, [
                h('h2', { class: 'text-[15px] font-bold tracking-tight text-ink' }, props.title),
                props.description
                    ? h('p', { class: 'mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/45' }, props.description)
                    : null,
            ]);
    },
});

const props = defineProps({
    user_growth: { type: Array, default: () => [] },
    jobs_trend: { type: Array, default: () => [] },
    daily_signups: { type: Array, default: () => [] },
    cumulative_users: { type: Array, default: () => [] },
    acquisition: { type: Array, default: () => [] },
    active_users: { type: Array, default: () => [] },
    monthly_active: { type: Array, default: () => [] },
    active_daily: { type: Array, default: () => [] },
    active_kpi: { type: Object, default: () => ({}) },
    login_frequency: { type: Array, default: () => [] },
    job_active_daily: { type: Array, default: () => [] },
    job_active_weekly: { type: Array, default: () => [] },
    job_active_monthly: { type: Array, default: () => [] },
    jobs_per_active: { type: Array, default: () => [] },
    job_retention: { type: Array, default: () => [] },
    geography: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    trades: { type: Array, default: () => [] },
    retention: { type: Array, default: () => [] },
    funnel: { type: Array, default: () => [] },
    page_popularity: { type: Object, default: () => ({ items: [], lowest_key: null }) },
    feature_usage: { type: Object, default: () => ({}) },
    credits_conversion: { type: Object, default: () => ({}) },
    has_summary_data: { type: Boolean, default: false },
    live_presence: {
        type: Object,
        default: () => ({
            online_now: { super_admins: 0, operations: 0, users: 0, total: 0 },
            online_today: { super_admins: 0, operations: 0, users: 0, total: 0 },
            hint: '',
        }),
    },
    filters: { type: Object, default: () => ({}) },
});

const { tab } = useAdminTabs({ tab: props.filters.tab || 'live' });
const range = useDateRange('last_30');
const loginMode = ref('dau');
const jobMode = ref('wau');
const refreshing = ref(false);
let presenceTimer = null;

const refreshPresence = () => {
    if (refreshing.value) return;
    refreshing.value = true;
    router.reload({
        only: ['live_presence'],
        preserveScroll: true,
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

onMounted(() => {
    presenceTimer = window.setInterval(() => {
        if (tab.value === 'live') {
            refreshPresence();
        }
    }, 30000);
});

onUnmounted(() => {
    if (presenceTimer) {
        window.clearInterval(presenceTimer);
        presenceTimer = null;
    }
});

const signupSeries = computed(() => range.series(props.daily_signups));
const growthSeries = computed(() => range.series(props.user_growth));
const jobsTrendSeries = computed(() => range.series(props.jobs_trend));
const jobsPerActiveSeries = computed(() => range.series(props.jobs_per_active));

const loginSeries = computed(() => {
    if (loginMode.value === 'mau') return range.series(props.monthly_active);
    if (loginMode.value === 'wau') return range.series(props.active_users);
    return range.series(props.active_daily);
});
const loginTitle = computed(() => {
    if (loginMode.value === 'mau') return 'Monthly active users (logins)';
    if (loginMode.value === 'wau') return 'Weekly active users (logins)';
    return 'Daily active users (logins)';
});

const jobSeries = computed(() => {
    if (jobMode.value === 'mau') return range.series(props.job_active_monthly);
    if (jobMode.value === 'dau') return range.series(props.job_active_daily);
    return range.series(props.job_active_weekly);
});
const jobTitle = computed(() => {
    if (jobMode.value === 'mau') return 'Monthly active users (jobs logged)';
    if (jobMode.value === 'dau') return 'Daily active users (jobs logged)';
    return 'Weekly active users (jobs logged)';
});

const loginFrequencySeries = computed(() =>
    (props.login_frequency || []).map((row) => ({
        label: row.label,
        date: row.label,
        value: row.value,
    })),
);

const rangePageItems = computed(() => {
    const seriesMap = props.page_popularity?.series || {};
    const keys = ['my_page', 'work_log', 'credits', 'help', 'help_chat', 'referrals'];
    const labels = {
        my_page: 'My page',
        work_log: 'Work log',
        credits: 'Credits',
        help: 'Help',
        help_chat: 'Help chat',
        referrals: 'Referrals',
    };

    const items = keys.map((key) => {
        const points = range.series(seriesMap[key] || []);
        const value = points.reduce((sum, p) => sum + (Number(p.value) || 0), 0);
        return { key, label: labels[key], value };
    });

    items.sort((a, b) => b.value - a.value);
    return items;
});

const lowestPageKey = computed(() => {
    if (!rangePageItems.value.length) return null;
    return [...rangePageItems.value].sort((a, b) => a.value - b.value)[0]?.key ?? null;
});

const featureDelta = (card) => {
    if (!card) return null;
    if (card.delta) {
        return {
            ...card.delta,
            label: `${card.delta.label} · ${card.count} of ${card.active} active`,
        };
    }
    return {
        label: `${card.count ?? 0} of ${card.active ?? 0} active`,
        tone: 'neutral',
    };
};
</script>
