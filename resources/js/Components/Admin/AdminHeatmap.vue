<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] sm:p-6">
        <div class="mb-4">
            <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
            <p v-if="hint" class="mt-0.5 text-[12px] font-medium text-ink/40">{{ hint }}</p>
        </div>

        <div v-if="!rows.length" class="flex h-[180px] items-center justify-center text-sm font-medium text-ink/35">
            No cohort data yet
        </div>
        <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[36rem] border-separate border-spacing-1 text-left">
                <thead>
                    <tr>
                        <th class="px-2 py-1.5 text-[11px] font-bold uppercase tracking-wide text-ink/35">
                            Cohort
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col"
                            class="px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wide text-ink/35"
                        >
                            {{ col }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.label">
                        <td class="whitespace-nowrap px-2 py-1 text-[12px] font-semibold text-ink">
                            {{ row.label }}
                            <span class="ms-1 font-medium text-ink/35">· {{ row.signed_up }}</span>
                        </td>
                        <td
                            v-for="(cell, i) in row.cells"
                            :key="`${row.label}-${i}`"
                            class="relative"
                        >
                            <button
                                type="button"
                                class="flex h-9 w-full items-center justify-center rounded-md text-[11px] font-bold tabular-nums transition-transform hover:scale-[1.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-base/30"
                                :style="cellStyle(cell.value)"
                                :title="tooltip(row, columns[i], cell)"
                                @click="selected = { row, col: columns[i], cell }"
                            >
                                {{ cell.label }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p
                v-if="selected"
                class="mt-3 rounded-xl bg-pale px-3 py-2 text-[12px] font-medium text-ink/60"
            >
                <span class="font-bold text-ink">{{ selected.row.label }}</span>
                · {{ selected.col }} —
                {{ selected.cell.value == null ? 'Not enough time elapsed' : `${selected.cell.value}% still active` }}
                ({{ selected.row.signed_up }} signed up that week)
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const selected = ref(null);

const cellStyle = (value) => {
    if (value == null) {
        return { background: '#F3F5F8', color: '#9AA3B2' };
    }
    const t = Math.min(100, Math.max(0, Number(value))) / 100;
    // Navy → action blue intensity by retention %
    const r = Math.round(7 + (26 - 7) * t);
    const g = Math.round(20 + (79 - 20) * t);
    const b = Math.round(39 + (181 - 39) * t);
    const text = t > 0.45 ? '#fff' : '#0B1F3A';
    return {
        background: `rgba(${r},${g},${b},${0.12 + t * 0.88})`,
        color: text,
    };
};

const tooltip = (row, col, cell) => {
    if (cell.value == null) return `${row.label} ${col}: n/a`;
    return `${row.label} ${col}: ${cell.value}% of ${row.signed_up} still active`;
};
</script>
