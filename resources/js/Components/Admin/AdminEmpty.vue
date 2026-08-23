<template>
    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-pale text-ink/30">
            <i :class="icon" class="text-xl" aria-hidden="true" />
        </span>
        <p class="mt-4 text-sm font-bold text-ink">{{ title }}</p>
        <p class="mt-1 max-w-sm text-[13px] font-medium text-ink/45">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: String, default: 'ti ti-inbox' },
});
</script>
