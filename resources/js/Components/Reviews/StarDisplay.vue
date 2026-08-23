<template>
    <div
        class="inline-flex items-center gap-0.5"
        role="img"
        :aria-label="`${value.toFixed(1)} out of 5 stars`"
    >
        <svg
            v-for="n in 5"
            :key="n"
            class="shrink-0"
            :class="sizeClass"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <defs>
                <clipPath :id="`sd-${uid}-${n}`">
                    <rect x="0" y="0" width="12" height="24" />
                </clipPath>
            </defs>

            <path
                :d="STAR_PATH"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linejoin="round"
                :class="emptyClass"
            />
            <path
                v-if="value >= n - 0.5"
                :d="STAR_PATH"
                fill="currentColor"
                class="text-[#F5A524]"
                :clip-path="value >= n ? undefined : `url(#sd-${uid}-${n})`"
            />
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const STAR_PATH =
    'M12 2.6l2.75 5.93 6.47.74-4.8 4.4 1.33 6.37L12 16.96l-5.75 3.08 1.33-6.37-4.8-4.4 6.47-.74L12 2.6z';

const props = defineProps({
    rating: { type: [Number, String], default: 0 },
    size: { type: String, default: 'sm' }, // sm | md | lg
    /** Tailwind text-color class for the outline of unfilled stars */
    emptyClass: { type: String, default: 'text-ink/20' },
});

const uid = Math.random().toString(36).slice(2, 9);

const value = computed(() => Number(props.rating) || 0);

const sizeClass = computed(() => {
    if (props.size === 'lg') {
        return 'h-6 w-6';
    }
    if (props.size === 'md') {
        return 'h-5 w-5';
    }
    return 'h-4 w-4';
});
</script>
