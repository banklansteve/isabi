<template>
    <Head title="Content & support" />

    <AdminChrome title="Content & support" eyebrow="Is the help system actually helping?" />

    <OpsPerformanceTabs />

    <div class="mb-4">
        <AdminRangePicker :range="range" />
    </div>

    <div class="space-y-8">
        <section class="grid gap-4">
            <div>
                <h2 class="text-[15px] font-bold tracking-tight text-ink">Help views vs support tickets</h2>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/45">
                    {{ help_vs_tickets.hint }}
                </p>
            </div>

            <AdminDualLineChart
                v-if="help_vs_tickets.ready"
                title="Help opens vs tickets"
                hint="Solid = help / help-chat views · dashed = tickets opened"
                primary-label="Help views"
                secondary-label="Tickets"
                secondary-key="tickets"
                :series="helpSeries"
            />
            <div
                v-else
                class="rounded-2xl bg-tint/50 px-4 py-6 text-center text-[13px] font-medium text-ink/50 ring-1 ring-ink/[0.05]"
            >
                No help-page or ticket activity in the last 90 days yet.
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] sm:p-6">
                <h3 class="text-[15px] font-semibold tracking-tight text-ink">FAQ / topic effectiveness</h3>
                <p class="mt-0.5 text-[12px] font-medium text-ink/40">
                    Ticket volume by topic vs canned-reply coverage. High volume + covered = article may not be working; high volume + gap = write the FAQ.
                </p>
                <ul v-if="faq_effectiveness.length" class="mt-4 divide-y divide-ink/[0.06]">
                    <li
                        v-for="row in faq_effectiveness"
                        :key="row.key"
                        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[13px] font-bold text-ink">{{ row.label }}</p>
                                <span
                                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1"
                                    :class="topicBadge(row)"
                                >
                                    {{ row.covered ? (row.tone === 'watch' ? 'Weak FAQ' : 'Covered') : 'FAQ gap' }}
                                </span>
                            </div>
                            <p class="mt-1 text-[12px] font-medium text-ink/45">{{ row.detail }}</p>
                        </div>
                        <p class="shrink-0 text-[13px] font-bold tabular-nums text-ink">
                            {{ row.tickets }} ticket{{ row.tickets === 1 ? '' : 's' }}
                        </p>
                    </li>
                </ul>
                <p v-else class="mt-6 text-center text-sm font-medium text-ink/35">No ticket topics in this window</p>
            </div>
        </section>

        <section class="grid gap-4 border-t border-ink/[0.06] pt-8">
            <div>
                <h2 class="text-[15px] font-bold tracking-tight text-ink">Support ticket health</h2>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/45">
                    Volume and resolution time — operational health that often mirrors product confusion.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <article
                    v-for="kpi in support_health.kpis || []"
                    :key="kpi.label"
                    class="rounded-2xl bg-white px-4 py-3.5 shadow-premium ring-1 ring-ink/[0.05]"
                >
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">{{ kpi.label }}</p>
                    <p class="mt-1.5 font-editorial text-2xl font-semibold tracking-tight text-ink sm:text-3xl">
                        {{ kpi.value }}
                    </p>
                    <p v-if="kpi.hint" class="mt-0.5 text-[12px] font-medium text-ink/40">{{ kpi.hint }}</p>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <AdminAreaChart
                    title="Ticket volume"
                    hint="Tickets opened per day"
                    :series="ticketVolumeSeries"
                />
                <AdminAreaChart
                    title="Avg resolution time (minutes)"
                    hint="Days with no resolutions show as zero"
                    :series="resolutionSeries"
                />
            </div>

            <AdminBarList
                title="Tickets by topic"
                hint="A spike on one topic usually means something in the product broke or confused people"
                :items="support_health.topics || []"
            />
        </section>

        <section class="grid gap-4 border-t border-ink/[0.06] pt-8">
            <div>
                <h2 class="text-[15px] font-bold tracking-tight text-ink">Zero-result searches</h2>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/45">
                    {{ zero_result_searches.hint }}
                </p>
            </div>

            <AdminBarList
                v-if="zero_result_searches.ready"
                title="Searches with no matches"
                hint="Unmet demand — recruit or catalogue these trades next"
                :items="zero_result_searches.items || []"
            />
            <div
                v-else
                class="rounded-2xl bg-white px-5 py-10 text-center shadow-premium ring-1 ring-ink/[0.05]"
            >
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-pale text-ink/35">
                    <i class="ti ti-search-off text-lg" aria-hidden="true" />
                </span>
                <p class="mt-3 text-sm font-bold text-ink">Coming with discovery search</p>
                <p class="mx-auto mt-1 max-w-md text-[13px] font-medium text-ink/45">
                    When directory search logs empty results server-side, queries like “generator repair” with no artisans will land here.
                </p>
            </div>
        </section>
    </div>
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminBarList from '@/Components/Admin/AdminBarList.vue';
import AdminDualLineChart from '@/Components/Admin/AdminDualLineChart.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import OpsPerformanceTabs from '@/Components/Admin/OpsPerformanceTabs.vue';
import { useDateRange } from '@/Composables/useDateRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    help_vs_tickets: {
        type: Object,
        default: () => ({ ready: false, hint: '', series: [] }),
    },
    faq_effectiveness: { type: Array, default: () => [] },
    support_health: {
        type: Object,
        default: () => ({ kpis: [], volume: [], resolution: [], topics: [] }),
    },
    zero_result_searches: {
        type: Object,
        default: () => ({ ready: false, hint: '', items: [] }),
    },
});

const range = useDateRange('last_30');

const helpSeries = computed(() => range.series(props.help_vs_tickets?.series || []));
const ticketVolumeSeries = computed(() => range.series(props.support_health?.volume || []));
const resolutionSeries = computed(() => range.series(props.support_health?.resolution || []));

const topicBadge = (row) => {
    if (!row.covered) return 'bg-rose-50 text-rose-700 ring-rose-200/80';
    if (row.tone === 'watch') return 'bg-amber-50 text-amber-800 ring-amber-200/80';
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200/80';
};
</script>
