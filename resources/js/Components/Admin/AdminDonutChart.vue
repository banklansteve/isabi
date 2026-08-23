<template>
    <div class="flex h-full min-h-[28rem] flex-col rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <h3 class="text-[15px] font-bold tracking-tight text-ink">{{ title }}</h3>
        <div v-if="!hasData" class="mt-8 flex flex-1 items-center justify-center text-sm font-medium text-ink/35">
            {{ emptyLabel }}
        </div>
        <div v-else class="mt-3 flex min-h-0 flex-1 flex-col items-center justify-center">
            <div class="relative w-full max-w-[24rem]">
                <svg viewBox="0 0 200 200" class="aspect-square w-full" role="img" :aria-label="title">
                    <circle cx="100" cy="100" r="78" fill="none" stroke="#E3ECFC" stroke-width="26" />
                    <circle
                        v-for="seg in segments"
                        :key="seg.label"
                        cx="100"
                        cy="100"
                        r="78"
                        fill="none"
                        :stroke="seg.color"
                        :stroke-width="hover === seg.label ? 32 : 26"
                        :stroke-dasharray="seg.dash"
                        :stroke-dashoffset="seg.offset"
                        stroke-linecap="butt"
                        class="cursor-pointer transition-[stroke-width] duration-150"
                        transform="rotate(-90 100 100)"
                        @mouseenter="hover = seg.label"
                        @mouseleave="hover = null"
                    />
                </svg>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/40">
                        {{ active.label }}
                    </p>
                    <p class="mt-1 text-[1.65rem] font-semibold tabular-nums tracking-tight text-ink">
                        {{ active.percent }}%
                    </p>
                    <p class="text-[12px] font-medium tabular-nums text-ink/45">
                        {{ Number(active.value).toLocaleString() }}
                    </p>
                </div>
            </div>
            <ul class="mt-5 flex w-full flex-wrap justify-center gap-x-4 gap-y-2">
                <li
                    v-for="item in legend"
                    :key="item.label"
                    class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold text-ink/60 transition-colors"
                    :class="hover === item.label ? 'text-ink' : ''"
                    @mouseenter="hover = item.label"
                    @mouseleave="hover = null"
                >
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: item.color }" />
                    {{ item.label }}
                    <span class="tabular-nums text-ink/35">{{ item.percent }}%</span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    items: { type: Array, default: () => [] },
    emptyLabel: { type: String, default: 'No data yet' },
});

const hover = ref(null);
const hasData = computed(() => props.items.some((i) => Number(i.value) > 0));
const circumference = 2 * Math.PI * 78;
const total = computed(() => props.items.reduce((sum, i) => sum + Number(i.value || 0), 0));
const legend = computed(() =>
    props.items.map((item) => ({
        ...item,
        percent: total.value > 0 ? Math.round((Number(item.value || 0) / total.value) * 100) : 0,
    })),
);

const active = computed(() => {
    const match = legend.value.find((item) => item.label === hover.value);
    if (match) {
        return match;
    }
    const top = [...legend.value].sort((a, b) => Number(b.value) - Number(a.value))[0];
    return top || { label: 'Total', percent: 0, value: 0 };
});

const segments = computed(() => {
    const sum = total.value || 1;
    let cursor = 0;

    return props.items.map((item) => {
        const frac = Number(item.value || 0) / sum;
        const dash = `${frac * circumference} ${circumference}`;
        const offset = -cursor * circumference;
        cursor += frac;
        return {
            label: item.label,
            color: item.color,
            dash,
            offset,
            value: item.value,
        };
    });
});
</script>
