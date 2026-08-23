<template>
    <div
        class="relative overflow-hidden rounded-[1.75rem] bg-white px-6 py-14 text-center shadow-premium ring-1 ring-ink/[0.06] sm:px-8 sm:py-16"
        role="status"
    >
        <div
            class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-tint/80 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-14 -left-10 h-36 w-36 rounded-full bg-coral/10 blur-3xl"
            aria-hidden="true"
        />

        <span
            class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl text-2xl text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.55)]"
            style="background-image: linear-gradient(145deg, #1a4fb5, #123b72 55%, #071427)"
        >
            <i :class="icon" aria-hidden="true" />
        </span>

        <h3 class="relative mt-5 font-editorial text-2xl font-semibold tracking-tight text-ink">
            {{ title }}
        </h3>
        <p class="relative mx-auto mt-2 max-w-sm text-sm font-medium leading-relaxed text-ink/50">
            {{ description }}
        </p>

        <div
            v-if="$slots.action || ctaLabel"
            class="relative mt-7 flex flex-wrap items-center justify-center gap-3"
        >
            <slot name="action">
                <Link
                    v-if="ctaHref && ctaLabel"
                    :href="ctaHref"
                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    {{ ctaLabel }}
                    <i class="ti ti-arrow-right" aria-hidden="true" />
                </Link>
                <button
                    v-else-if="ctaLabel"
                    type="button"
                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    @click="$emit('action')"
                >
                    {{ ctaLabel }}
                </button>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: { type: String, default: 'ti ti-folder-open' },
    title: { type: String, required: true },
    description: { type: String, required: true },
    ctaLabel: { type: String, default: '' },
    ctaHref: { type: String, default: '' },
});

defineEmits(['action']);
</script>
