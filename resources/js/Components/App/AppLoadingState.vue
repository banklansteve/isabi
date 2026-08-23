<template>
    <div
        class="relative overflow-hidden rounded-[1.75rem] bg-white px-6 py-14 text-center shadow-premium ring-1 ring-ink/[0.06] sm:px-8 sm:py-16"
        role="status"
        aria-live="polite"
        aria-busy="true"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-40"
            style="
                background-image: linear-gradient(
                    110deg,
                    transparent 0%,
                    rgba(26, 79, 181, 0.04) 45%,
                    transparent 70%
                );
                background-size: 200% 100%;
                animation: shimmer 1.6s ease-in-out infinite;
            "
            aria-hidden="true"
        />

        <span
            class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.55)]"
            style="background-image: linear-gradient(145deg, #1a4fb5, #123b72 55%, #071427)"
        >
            <i class="ti ti-loader-2 animate-spin text-2xl" aria-hidden="true" />
        </span>

        <p class="relative mt-5 font-editorial text-xl font-semibold tracking-tight text-ink">
            {{ title }}
        </p>
        <p class="relative mx-auto mt-1.5 max-w-xs text-sm font-medium text-ink/45">
            {{ description }}
        </p>

        <div
            v-if="showSkeleton"
            class="relative mx-auto mt-8 max-w-md space-y-3 text-left"
            aria-hidden="true"
        >
            <div
                v-for="n in skeletonRows"
                :key="n"
                class="h-14 animate-pulse rounded-2xl bg-pale ring-1 ring-ink/[0.04]"
            />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Loading…' },
    description: { type: String, default: 'Just a moment while we fetch the latest.' },
    showSkeleton: { type: Boolean, default: true },
    skeletonRows: { type: Number, default: 3 },
});
</script>

<style scoped>
@keyframes shimmer {
    0% {
        background-position: 100% 0;
    }
    100% {
        background-position: -100% 0;
    }
}
</style>
