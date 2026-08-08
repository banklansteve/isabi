<template>
    <div class="rounded-xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold tracking-tight text-ink sm:text-lg">
                    Jobs logged
                </h2>
                <p class="mt-1 text-sm font-medium text-ink/45">
                    Last 6 months
                </p>
            </div>
            <p v-if="!isEmpty" class="text-sm font-semibold tabular-nums text-ink/55">
                <span class="text-ink">{{ total }}</span>
                total
            </p>
        </div>

        <!-- Empty placeholder -->
        <div
            v-if="isEmpty"
            class="mt-5 flex flex-col items-center rounded-xl border border-dashed border-ink/10 bg-pale/70 px-5 py-10 text-center"
        >
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-ink/30 shadow-sm ring-1 ring-ink/[0.04]">
                <i class="ti ti-chart-bar-off" aria-hidden="true" />
            </span>
            <p class="mt-4 text-sm font-semibold tracking-tight text-ink">
                Nothing to chart yet
            </p>
            <p class="mt-1 max-w-xs text-sm font-medium leading-relaxed text-ink/45">
                When you start logging jobs, your monthly activity will show up here.
            </p>
        </div>

        <!-- Chart -->
        <div
            v-else
            class="relative mt-6 h-44 sm:h-48"
            role="img"
            :aria-label="ariaLabel"
        >
            <div class="pointer-events-none absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between" aria-hidden="true">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="border-t border-dashed border-ink/[0.06]"
                />
            </div>

            <div class="absolute inset-x-0 top-0 bottom-6 flex items-end gap-2.5 sm:gap-4">
                <div
                    v-for="(point, index) in points"
                    :key="point.key"
                    class="group relative flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                >
                    <div
                        class="pointer-events-none absolute -top-7 rounded-md bg-ink px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-sm transition-opacity duration-200 group-hover:opacity-100"
                    >
                        {{ point.count }}
                    </div>
                    <div
                        class="w-full max-w-[2.75rem] origin-bottom rounded-t-lg bg-gradient-to-t from-deep to-base transition-[height,opacity] duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
                        :style="{
                            height: ready ? barHeight(point.count) : '0px',
                            transitionDelay: `${index * 50}ms`,
                            opacity: ready ? 1 : 0,
                        }"
                    />
                </div>
            </div>

            <div class="absolute inset-x-0 bottom-0 flex gap-2.5 sm:gap-4">
                <span
                    v-for="point in points"
                    :key="`l-${point.key}`"
                    class="min-w-0 flex-1 text-center text-[11px] font-medium text-ink/40"
                >
                    {{ point.label }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    points: {
        type: Array,
        default: () => [],
    },
});

const ready = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true;
    });
});

const total = computed(() =>
    props.points.reduce((sum, point) => sum + Number(point.count || 0), 0),
);

const isEmpty = computed(() => total.value === 0);

const max = computed(() => {
    const peak = Math.max(...props.points.map((p) => Number(p.count || 0)), 0);
    return peak > 0 ? peak : 1;
});

const barHeight = (count) => {
    const value = Number(count || 0);
    if (value <= 0) {
        return '3px';
    }
    const pct = Math.max(8, Math.round((value / max.value) * 100));
    return `${pct}%`;
};

const ariaLabel = computed(() => {
    const series = props.points
        .map((p) => `${p.label}: ${p.count}`)
        .join(', ');
    return `Jobs logged per month over the last 6 months. ${series}`;
});
</script>
