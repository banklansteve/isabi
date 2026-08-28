<template>
    <div class="flex items-center gap-3">
        <h2 class="shrink-0 text-[11px] font-semibold uppercase tracking-[0.16em] text-ink/35">
            {{ label }}
        </h2>
        <span class="h-px min-w-8 flex-1 bg-ink/[0.08]" />
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
});
</script>
