<template>
    <div class="flex h-full flex-col rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
                <p v-if="hint" class="mt-0.5 text-[12px] font-medium text-ink/40">{{ hint }}</p>
            </div>
            <ul class="flex flex-wrap gap-x-4 gap-y-1">
                <li class="flex items-center gap-1.5 text-[11px] font-semibold text-ink/50">
                    <span class="h-0.5 w-3 rounded-full bg-base-action" />
                    {{ primaryLabel }}
                </li>
                <li v-if="hasSecondary" class="flex items-center gap-1.5 text-[11px] font-semibold text-ink/50">
                    <span class="h-0.5 w-3 rounded-full bg-coral" style="border-top: 2px dashed #FF6A3D" />
                    {{ secondaryLabel }}
                </li>
            </ul>
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
                @mousemove="onMove"
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
                <path :d="areaPath" :fill="`url(#${fillId})`" />
                <path :d="linePath" fill="none" stroke="#1A4FB5" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                <path
                    v-if="hasSecondary"
                    :d="secondaryPath"
                    fill="none"
                    stroke="#FF6A3D"
                    stroke-width="2"
                    stroke-dasharray="5 4"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />
                <line
                    v-if="hover"
                    :x1="hover.x"
                    :x2="hover.x"
                    y1="18"
                    y2="178"
                    stroke="#2F6FED"
                    stroke-opacity="0.35"
                    stroke-dasharray="3 4"
                />
                <circle
                    v-for="(pt, i) in points"
                    :key="i"
                    :cx="pt.x"
                    :cy="pt.y"
                    :r="hover?.i === i ? 6 : 0"
                    fill="#fff"
                    stroke="#1A4FB5"
                    stroke-width="2.5"
                />
                <text
                    v-for="(pt, i) in points"
                    v-show="showAxisLabel(i, points.length)"
                    :key="'l' + i"
                    :x="pt.x"
                    y="210"
                    text-anchor="middle"
                    fill="#0B1F3A"
                    fill-opacity="0.4"
                    font-size="11"
                    font-weight="600"
                >
                    {{ series[i].label }}
                </text>
                <defs>
                    <linearGradient :id="fillId" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#2F6FED" stop-opacity="0.28" />
                        <stop offset="100%" stop-color="#2F6FED" stop-opacity="0.02" />
                    </linearGradient>
                </defs>
            </svg>
            <div
                v-if="hover"
                class="pointer-events-none absolute z-10 min-w-[7rem] rounded-xl bg-ink px-3 py-2 text-white shadow-premium-ink"
                :style="tooltipStyle"
            >
                <p class="text-[11px] font-medium text-white/60">{{ hover.label }}</p>
                <p class="text-[13px] font-bold tabular-nums">
                    {{ primaryLabel }}: {{ formatCompact(hover.value) }}
                </p>
                <p v-if="hover.secondary != null" class="text-[12px] font-semibold tabular-nums text-coral-tint">
                    {{ secondaryLabel }}: {{ formatCompact(hover.secondary) }}
                </p>
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
    /** @type {{ label: string, value: number, rolling_avg?: number|null }[]} */
    series: { type: Array, default: () => [] },
    primaryLabel: { type: String, default: 'Daily' },
    secondaryLabel: { type: String, default: '7-day avg' },
    secondaryKey: { type: String, default: 'rolling_avg' },
});

const hover = ref(null);
const fillId = `daf${Math.random().toString(36).slice(2, 9)}`;
const hasData = computed(() => props.series.some((p) => Number(p.value) > 0));
const hasSecondary = computed(() =>
    props.series.some((p) => p[props.secondaryKey] != null && Number(p[props.secondaryKey]) >= 0),
);
const gridYs = [20, 70, 120, 170];

const maxValue = computed(() => {
    const vals = props.series.flatMap((p) => {
        const out = [Number(p.value) || 0];
        if (p[props.secondaryKey] != null) out.push(Number(p[props.secondaryKey]) || 0);
        return out;
    });
    return Math.max(...vals, 1);
});

const points = computed(() => {
    const n = Math.max(props.series.length, 1);
    const padX = 28;
    const top = 18;
    const bottom = 178;
    const height = bottom - top;
    const max = maxValue.value;

    return props.series.map((row, i) => {
        const value = Number(row.value) || 0;
        const secondary = row[props.secondaryKey] != null ? Number(row[props.secondaryKey]) : null;
        const x = n === 1 ? 320 : padX + (i * (640 - padX * 2)) / (n - 1);
        const y = bottom - (value / max) * height;
        const sy = secondary != null ? bottom - (secondary / max) * height : null;
        return { x, y, sy, value, secondary, label: row.label, i };
    });
});

const linePath = computed(() => {
    if (!points.value.length) return '';
    return points.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x} ${p.y}`).join(' ');
});

const secondaryPath = computed(() => {
    const pts = points.value.filter((p) => p.sy != null);
    if (!pts.length) return '';
    return pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x} ${p.sy}`).join(' ');
});

const areaPath = computed(() => {
    if (!points.value.length) return '';
    const first = points.value[0];
    const last = points.value[points.value.length - 1];
    return `${linePath.value} L${last.x} 178 L${first.x} 178 Z`;
});

const onMove = (event) => {
    const svg = event.currentTarget;
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 640;
    let nearest = points.value[0];
    let best = Infinity;
    points.value.forEach((pt) => {
        const dist = Math.abs(pt.x - x);
        if (dist < best) {
            best = dist;
            nearest = pt;
        }
    });
    hover.value = nearest;
};

const tooltipStyle = computed(() => {
    if (!hover.value) return {};
    const left = Math.min(86, Math.max(8, (hover.value.x / 640) * 100));
    return {
        left: `${left}%`,
        top: `${Math.max(8, (hover.value.y / 220) * 100 - 18)}%`,
        transform: 'translateX(-50%)',
    };
});
</script>
