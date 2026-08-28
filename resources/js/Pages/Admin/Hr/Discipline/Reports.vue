<template>
    <Head title="Disciplinary reports" />

    <AdminChrome title="Disciplinary reports" eyebrow="Aggregate only — no names" />

    <form class="mb-5 flex flex-wrap items-end gap-2" @submit.prevent="applyFilters">
        <label class="text-[12px] font-semibold text-ink/50">
            From
            <input v-model="form.from" type="date" class="mt-1 block rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium" />
        </label>
        <label class="text-[12px] font-semibold text-ink/50">
            To
            <input v-model="form.to" type="date" class="mt-1 block rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium" />
        </label>
        <FormButton type="submit" variant="secondary" label="Apply" />
        <a :href="exportHref" class="text-xs font-semibold text-base-action hover:text-base-hover">Export CSV</a>
    </form>

    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">{{ card.label }}</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-ink">{{ card.value }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
            <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">By category</h2>
            <p v-if="!report.by_category.length" class="mt-4 text-sm font-medium text-ink/45">No cases in this period.</p>
            <ul v-else class="mt-4 space-y-2">
                <li v-for="row in report.by_category" :key="row.label" class="flex items-center justify-between text-sm font-medium text-ink/70">
                    <span>{{ row.label }}</span>
                    <span class="font-semibold text-ink">{{ row.count }}</span>
                </li>
            </ul>
        </section>
        <section class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
            <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Volume</h2>
            <ul class="mt-4 space-y-2">
                <li v-for="row in report.volume" :key="row.label" class="flex items-center justify-between text-sm font-medium text-ink/70">
                    <span>{{ row.label }}</span>
                    <span class="font-semibold text-ink">{{ row.count }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { visitAdmin } from '@/utils/adminVisit';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    report: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const form = useForm({
    from: props.filters.from || '',
    to: props.filters.to || '',
});

const cards = computed(() => [
    { label: 'Cases', value: props.report.total },
    { label: 'Open', value: props.report.open },
    { label: 'Avg. days to resolution', value: props.report.average_days_to_resolution ?? '—' },
    { label: 'Appeal rate', value: `${props.report.appeal_rate}%` },
]);

const exportHref = computed(() => route('admin.hr.discipline.reports.export', {
    from: form.from || undefined,
    to: form.to || undefined,
}));

const applyFilters = () => {
    visitAdmin(route('admin.hr.discipline.reports', {
        from: form.from || undefined,
        to: form.to || undefined,
    }), { preserveState: true, replace: true });
};
</script>
