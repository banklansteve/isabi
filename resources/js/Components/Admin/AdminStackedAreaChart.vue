<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
            <ul class="flex flex-wrap gap-x-4 gap-y-1">
                <li
                    v-for="layer in layers"
                    :key="layer.key"
                    class="flex items-center gap-1.5 text-[12px] font-medium text-ink/55"
                >
                    <span class="h-2 w-2 rounded-sm" :style="{ background: layer.color }" />
                    {{ layer.label }}
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
                <path
                    v-for="layer in stacked"
                    :key="layer.key"
                    :d="layer.area"
                    :fill="layer.color"
                    :fill-opacity="hover == null || hover === layer.key ? 0.9 : 0.35"
                    class="cursor-pointer transition-[fill-opacity] duration-150"
                    @mouseenter="hover = layer.key"
                />
                <text
                    v-for="(label, i) in labels"
                    v-show="showAxisLabel(i, labels.length)"
                    :key="label + i"
                    :x="xAt(i)"
                    y="210"
                    text-anchor="middle"
                    fill="#0B1F3A"
                    fill-opacity="0.4"
                    font-size="11"
                    font-weight="500"
                >
                    {{ label }}
                </text>
            </svg>
            <div
                v-if="tooltip"
                class="pointer-events-none absolute z-10 min-w-[9rem] rounded-xl bg-ink px-3 py-2 text-white shadow-premium-ink"
                :style="tooltip.style"
            >
                <p class="text-[11px] font-medium text-white/60">{{ tooltip.label }}</p>
                <p
                    v-for="row in tooltip.rows"
                    :key="row.key"
                    class="mt-0.5 flex justify-between gap-4 text-[12px] font-semibold"
                >
                    <span>{{ row.label }}</span>
                    <span class="tabular-nums">{{ formatNaira(row.value) }}</span>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatNaira, showAxisLabel } from '@/utils/adminRange';
import { computed, ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    labels: { type: Array, default: () => [] },
    layers: { type: Array, default: () => [] },
});

const hover = ref(null);
const indexHover = ref(null);
const gridYs = [20, 70, 120, 170];
const padX = 28;
const top = 18;
const bottom = 178;

const hasData = computed(() =>
    props.layers.some((layer) => (layer.values || []).some((v) => Number(v) > 0)),
);

const xAt = (i) => {
    const n = Math.max(props.labels.length, 1);
    return n === 1 ? 320 : padX + (i * (640 - padX * 2)) / (n - 1);
};

const stacked = computed(() => {
    const n = props.labels.length;
    const totals = Array.from({ length: n }, (_, i) =>
        props.layers.reduce((sum, layer) => sum + Number(layer.values?.[i] || 0), 0),
    );
    const max = Math.max(...totals, 1);
    const height = bottom - top;

    let running = Array(n).fill(0);
    const result = [];

    props.layers.forEach((layer) => {
        const topPts = [];
        const botPts = [];
        for (let i = 0; i < n; i++) {
            const prev = running[i];
            const next = prev + Number(layer.values?.[i] || 0);
            running[i] = next;
            const x = xAt(i);
            topPts.push({ x, y: bottom - (next / max) * height });
            botPts.push({ x, y: bottom - (prev / max) * height });
        }
        const area =
            topPts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x} ${p.y}`).join(' ') +
            ' ' +
            [...botPts].reverse().map((p) => `L${p.x} ${p.y}`).join(' ') +
            ' Z';
        result.push({ key: layer.key, color: layer.color, area });
    });

    return result;
});

const onMove = (event) => {
    const svg = event.currentTarget;
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 640;
    let nearest = 0;
    let best = Infinity;
    props.labels.forEach((_, i) => {
        const dist = Math.abs(xAt(i) - x);
        if (dist < best) {
            best = dist;
            nearest = i;
        }
    });
    indexHover.value = nearest;
};

const tooltip = computed(() => {
    if (indexHover.value == null || !props.labels[indexHover.value]) {
        return null;
    }
    const i = indexHover.value;
    return {
        label: props.labels[i],
        rows: props.layers.map((layer) => ({
            key: layer.key,
            label: layer.label,
            value: Number(layer.values?.[i] || 0),
        })),
        style: {
            left: `${Math.min(78, Math.max(8, (xAt(i) / 640) * 100))}%`,
            top: '12%',
            transform: 'translateX(-50%)',
        },
    };
});
</script>
