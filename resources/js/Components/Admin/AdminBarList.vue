<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <h3 class="text-[15px] font-bold tracking-tight text-ink">{{ title }}</h3>
        <div v-if="!items.length" class="mt-8 flex h-[160px] items-center justify-center text-sm font-medium text-ink/35">
            No data yet
        </div>
        <ul v-else class="mt-5 space-y-3">
            <li v-for="item in items" :key="item.label" class="group">
                <div class="mb-1 flex items-baseline justify-between gap-3 text-[13px] font-semibold">
                    <span class="truncate text-ink/70 group-hover:text-ink">{{ item.label }}</span>
                    <span class="tabular-nums text-ink">{{ format(item.value) }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-tint">
                    <div
                        class="h-full rounded-full bg-base-action transition-[width] duration-500 group-hover:bg-base-hover"
                        :style="{ width: `${barWidth(item.value)}%` }"
                    />
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    items: { type: Array, default: () => [] },
    money: { type: Boolean, default: false },
});

const max = computed(() => Math.max(...props.items.map((i) => Number(i.value) || 0), 1));
const barWidth = (value) => Math.max(2, (Number(value) / max.value) * 100);
const format = (value) => {
    const n = Number(value) || 0;
    if (props.money) {
        if (n >= 1_000_000) return `₦${(n / 1_000_000).toFixed(1).replace(/\.0$/, '')}M`;
        return `₦${n.toLocaleString()}`;
    }
    return n.toLocaleString();
};
</script>
