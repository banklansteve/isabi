<template>
    <div class="overflow-x-auto rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] sm:p-6">
        <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
        <p v-if="hint" class="mt-1 text-[13px] font-medium text-ink/45">{{ hint }}</p>
        <table class="mt-4 w-full min-w-[32rem] text-left text-sm">
            <thead class="text-[11px] font-semibold uppercase tracking-wide text-ink/35">
                <tr>
                    <th class="py-2 font-semibold">Cohort</th>
                    <th class="py-2 font-semibold">Signed up</th>
                    <th class="py-2 font-semibold">30d</th>
                    <th class="py-2 font-semibold">60d</th>
                    <th class="py-2 font-semibold">90d</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.month" class="border-t border-ink/[0.06]">
                    <td class="py-2.5 font-medium">{{ row.month }}</td>
                    <td class="tabular-nums text-ink/70">{{ row.signed_up }}</td>
                    <td>
                        <span class="inline-flex min-w-[3.25rem] justify-center rounded-md px-2 py-1 text-[12px] font-semibold tabular-nums" :style="cell(row.rate_30)">
                            {{ row.rate_30 }}%
                        </span>
                    </td>
                    <td>
                        <span class="inline-flex min-w-[3.25rem] justify-center rounded-md px-2 py-1 text-[12px] font-semibold tabular-nums" :style="cell(row.rate_60)">
                            {{ row.rate_60 }}%
                        </span>
                    </td>
                    <td>
                        <span class="inline-flex min-w-[3.25rem] justify-center rounded-md px-2 py-1 text-[12px] font-semibold tabular-nums" :style="cell(row.rate_90)">
                            {{ row.rate_90 }}%
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Cohort retention' },
    hint: { type: String, default: 'Share of a signup month that logged a job within 30 / 60 / 90 days.' },
    rows: { type: Array, default: () => [] },
});

const cell = (rate) => {
    const n = Number(rate) || 0;
    const alpha = 0.08 + (n / 100) * 0.42;
    return {
        background: `rgba(47, 111, 237, ${alpha})`,
        color: n >= 45 ? '#fff' : '#0B1F3A',
    };
};
</script>
