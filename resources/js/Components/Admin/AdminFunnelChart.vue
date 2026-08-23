<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
                <p v-if="hint" class="mt-1 text-[13px] font-medium text-ink/45">{{ hint }}</p>
            </div>
        </div>

        <div v-if="!steps.length" class="flex h-[180px] items-center justify-center text-sm font-medium text-ink/35">
            No data yet
        </div>

        <ul v-else class="space-y-3.5">
            <li
                v-for="(step, index) in steps"
                :key="step.label"
                class="group"
            >
                <div class="flex items-baseline justify-between gap-3">
                    <p class="text-[13px] font-medium text-ink/70 group-hover:text-ink">{{ step.label }}</p>
                    <p class="shrink-0 text-[13px] font-semibold tabular-nums text-ink/55">
                        {{ step.percent }}%
                        <span class="font-medium text-ink/30">·</span>
                        {{ Number(step.value).toLocaleString() }}
                    </p>
                </div>
                <div class="mt-1.5 h-7 overflow-hidden rounded-md bg-tint">
                    <div
                        class="h-full rounded-md bg-base transition-[width,background-color] duration-500 group-hover:bg-base-action"
                        :style="{ width: `${barWidth(step.percent)}%` }"
                    />
                </div>
                <p v-if="index > 0" class="mt-1 text-[11px] font-medium text-ink/35">
                    {{ dropOff(index) }}
                </p>
            </li>
        </ul>
    </div>
</template>

<script setup>
const props = defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    steps: { type: Array, default: () => [] },
});

const barWidth = (percent) => Math.max(2, Math.min(100, Number(percent) || 0));

const dropOff = (index) => {
    const prev = Number(props.steps[index - 1]?.percent || 0);
    const current = Number(props.steps[index]?.percent || 0);
    const lost = Math.max(0, prev - current);
    return lost ? `${lost}pt drop from previous step` : 'Held from previous step';
};
</script>
