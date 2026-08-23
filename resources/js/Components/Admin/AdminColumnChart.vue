<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
                <p v-if="hint" class="mt-0.5 text-[12px] font-medium text-ink/40">{{ hint }}</p>
            </div>
        </div>
        <div v-if="!hasData" class="flex h-[200px] items-center justify-center text-sm font-medium text-ink/35">
            No data yet
        </div>
        <div v-else class="relative">
            <svg
                viewBox="0 0 640 220"
                class="h-[200px] w-full overflow-visible"
                role="img"
                :aria-label="title"
                @mouseleave="hover = null"
            >
                <line
                    v-for="g in gridYs"
                    :key="g"
                    x1="8"
                    x2="632"
                    :y1="g"
                    :y2="g"
                    stroke="#0B1F3A"
                    stroke-opacity="0.06"
                    stroke-width="1"
                />
                <rect
                    v-for="(col, i) in columns"
                    :key="i"
                    :x="col.x"
                    :y="col.y"
                    :width="col.width"
                    :height="col.height"
                    :fill="hover === i ? '#154296' : color"
                    rx="4"
                    class="cursor-pointer transition-[fill] duration-150"
                    @mouseenter="hover = i"
                />
                <text
                    v-for="(col, i) in columns"
                    v-show="showAxisLabel(i, columns.length)"
                    :key="'l' + i"
                    :x="col.x + col.width / 2"
                    y="210"
                    text-anchor="middle"
                    fill="#0B1F3A"
                    fill-opacity="0.4"
                    font-size="11"
                    font-weight="500"
                >
                    {{ series[i].label }}
                </text>
            </svg>
            <div
                v-if="hover != null && columns[hover]"
                class="pointer-events-none absolute z-10 rounded-xl bg-ink px-3 py-2 text-white shadow-premium-ink"
                :style="{
                    left: `${(columns[hover].x / 640) * 100}%`,
                    top: `${Math.max(4, (columns[hover].y / 220) * 100 - 12)}%`,
                }"
            >
                <p class="text-[11px] font-medium text-white/60">{{ series[hover].label }}</p>
                <p class="text-[13px] font-bold tabular-nums">{{ formatCompact(series[hover].value) }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatCompact, showAxisLabel } from '@/utils/adminRange';
import { computed, ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    series: { type: Array, default: () => [] },
    color: { type: String, default: '#2F6FED' },
});

const hover = ref(null);
const hasData = computed(() => props.series.some((p) => Number(p.value) > 0));
const gridYs = [20, 70, 120, 170];

const columns = computed(() => {
    const values = props.series.map((p) => Number(p.value) || 0);
    const max = Math.max(...values, 1);
    const n = Math.max(values.length, 1);
    const padX = 28;
    const top = 18;
    const bottom = 178;
    const inner = 640 - padX * 2;
    const slot = inner / n;
    const width = Math.max(8, slot * 0.55);

    return values.map((value, i) => {
        const height = (value / max) * (bottom - top);
        const x = padX + i * slot + (slot - width) / 2;
        return { x, y: bottom - height, width, height: Math.max(height, value > 0 ? 2 : 0) };
    });
});
</script>
