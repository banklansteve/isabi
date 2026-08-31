<template>
    <Head title="Support reports" />

    <AdminChrome title="Support reports" eyebrow="Last 30 days" />

    <OpsPerformanceTabs />

    <SupportWorkspaceNav />

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="kpi in kpis"
            :key="kpi.label"
            class="rounded-2xl bg-white px-5 py-4 shadow-premium ring-1 ring-ink/[0.05]"
        >
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">{{ kpi.label }}</p>
            <p class="mt-2 font-editorial text-3xl font-semibold tracking-tight text-ink">{{ kpi.value }}</p>
        </article>
    </div>

    <section class="mt-5 rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
        <h2 class="text-[15px] font-bold text-ink">Volume</h2>
        <p v-if="!report.series?.length" class="mt-3 text-[13px] font-medium text-ink/40">No conversations in this window.</p>
        <ul v-else class="mt-4 space-y-2">
            <li v-for="row in report.series" :key="row.day" class="flex items-center gap-3">
                <span class="w-24 shrink-0 text-[12px] font-semibold text-ink/40">{{ row.day }}</span>
                <span class="h-2 flex-1 overflow-hidden rounded-full bg-pale">
                    <span class="block h-full rounded-full bg-base-action" :style="{ width: bar(row.total) }" />
                </span>
                <span class="w-8 text-right text-[12px] font-bold text-ink">{{ row.total }}</span>
            </li>
        </ul>
    </section>

    <section class="mt-5 rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
        <h2 class="text-[15px] font-bold text-ink">By topic</h2>
        <p class="mt-1 text-[13px] font-medium text-ink/45">
            Use this to decide which FAQ answers or product fixes are worth doing next.
        </p>
        <AdminEmpty
            v-if="!report.topics?.length"
            title="No tagged conversations yet"
            description="Tags from chat starters and the inbox will land here."
            icon="ti ti-tags"
        />
        <ul v-else class="mt-4 divide-y divide-ink/[0.06]">
            <li v-for="topic in report.topics" :key="topic.key" class="flex items-center justify-between py-3">
                <span class="text-sm font-semibold text-ink">{{ topic.label }}</span>
                <span class="text-sm font-bold text-ink/50">{{ topic.total }}</span>
            </li>
        </ul>
    </section>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsPerformanceTabs from '@/Components/Admin/OpsPerformanceTabs.vue';
import SupportWorkspaceNav from '@/Components/Admin/SupportWorkspaceNav.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    report: { type: Object, required: true },
});

const kpis = computed(() => [
    { label: 'Conversations', value: props.report.volume ?? 0 },
    { label: 'Avg first reply', value: formatMinutes(props.report.avg_first_response_minutes) },
    { label: 'Avg resolution', value: formatMinutes(props.report.avg_resolution_minutes) },
    { label: 'CSAT', value: props.report.csat_average != null ? `${props.report.csat_average} / 5` : '—' },
]);

const maxVolume = computed(() =>
    Math.max(1, ...(props.report.series || []).map((row) => Number(row.total || 0))),
);

const bar = (total) => `${Math.max(8, Math.round((Number(total) / maxVolume.value) * 100))}%`;

const formatMinutes = (value) => {
    if (value == null) return '—';
    if (value < 60) return `${value}m`;
    return `${Math.round(value / 60)}h`;
};
</script>
