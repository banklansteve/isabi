<template>
    <div class="space-y-2" aria-hidden="true">
        <div
            v-for="n in rows"
            :key="n"
            class="flex items-center gap-3 rounded-2xl bg-white px-4 py-4 shadow-premium ring-1 ring-ink/[0.04]"
        >
            <span class="h-10 w-10 shrink-0 animate-pulse rounded-xl bg-pale" />
            <div class="min-w-0 flex-1 space-y-2">
                <span class="block h-3.5 w-2/3 animate-pulse rounded bg-pale" />
                <span class="block h-3 w-1/2 animate-pulse rounded bg-pale/80" />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    rows: { type: Number, default: 3 },
});
</script>
