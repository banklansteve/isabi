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
        <div v-else class="flex h-[200px] items-end gap-2 sm:gap-3">
            <button
                v-for="bucket in buckets"
                :key="bucket.label"
                type="button"
                class="group flex min-w-0 flex-1 flex-col items-center gap-2"
            >
                <p class="text-[11px] font-semibold tabular-nums text-ink/40 transition-colors group-hover:text-base-action">
                    {{ bucket.value }}
                </p>
                <div class="flex h-[150px] w-full items-end justify-center">
                    <div
                        class="w-full max-w-[3.5rem] rounded-t-md bg-base transition-all duration-150 group-hover:bg-base-hover group-hover:shadow-[0_8px_18px_-8px_rgba(26,79,181,0.65)]"
                        :style="{ height: `${barHeight(bucket.value)}%` }"
                    />
                </div>
                <p class="text-[12px] font-semibold text-ink/50">{{ bucket.label }}★</p>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    buckets: { type: Array, default: () => [] },
});

const hasData = computed(() => props.buckets.some((b) => Number(b.value) > 0));
const max = computed(() => Math.max(...props.buckets.map((b) => Number(b.value) || 0), 1));
const barHeight = (value) => Math.max(Number(value) > 0 ? 6 : 0, (Number(value) / max.value) * 100);
</script>
