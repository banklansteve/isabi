<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-200 hover:shadow-premium-hover sm:p-6">
        <p class="text-[13px] font-semibold text-ink/45">{{ label }}</p>
        <p class="mt-2 text-[1.75rem] font-semibold tracking-tight text-ink tabular-nums sm:text-[1.95rem]">
            {{ value }}
        </p>
        <p
            v-if="deltaLabel"
            class="mt-2 text-[13px] font-semibold"
            :class="toneClass"
        >
            {{ deltaLabel }}
            <span v-if="deltaSuffix" class="font-medium text-ink/35">{{ deltaSuffix }}</span>
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    delta: { type: Object, default: null },
    deltaSuffix: { type: String, default: '' },
});

const deltaLabel = computed(() => props.delta?.label || '');
const toneClass = computed(() => {
    const tone = props.delta?.tone;
    if (tone === 'up') return 'text-emerald-600';
    if (tone === 'down') return 'text-coral';
    return 'text-ink/40';
});
</script>
