<template>
    <span
        class="relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint font-semibold tracking-tight text-deep"
        :class="sizeClass"
        :aria-hidden="alt ? undefined : true"
        :aria-label="alt || undefined"
        role="img"
    >
        <img
            v-if="src"
            :src="src"
            :alt="alt || ''"
            class="h-full w-full object-cover"
            loading="lazy"
            @error="failed = true"
        />
        <span v-else>{{ displayInitials }}</span>
    </span>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    src: { type: String, default: '' },
    initials: { type: String, default: 'I' },
    alt: { type: String, default: '' },
    /** sm | md | lg | xl | hero */
    size: { type: String, default: 'md' },
});

const failed = ref(false);

watch(
    () => props.src,
    () => {
        failed.value = false;
    },
);

const src = computed(() => (!failed.value && props.src ? props.src : ''));

const displayInitials = computed(() => {
    const value = String(props.initials || 'I').trim();
    return value.slice(0, 2).toUpperCase() || 'I';
});

const sizeClass = computed(() => {
    const map = {
        sm: 'h-8 w-8 text-xs',
        md: 'h-9 w-9 text-sm sm:h-10 sm:w-10',
        lg: 'h-11 w-11 text-sm',
        xl: 'h-12 w-12 text-base',
        hero: 'h-[4.25rem] w-[4.25rem] text-xl sm:h-[4.75rem] sm:w-[4.75rem] sm:text-[1.4rem]',
    };
    return map[props.size] || map.md;
});
</script>
