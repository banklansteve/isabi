<template>
    <div
        class="relative overflow-hidden rounded-[1.75rem] bg-white px-6 py-14 text-center shadow-premium ring-1 ring-ink/[0.06] sm:px-8 sm:py-16"
        role="alert"
    >
        <div
            class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-coral/15 blur-3xl"
            aria-hidden="true"
        />

        <span
            class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-coral/10 text-2xl text-coral ring-1 ring-coral/20"
        >
            <i :class="icon" aria-hidden="true" />
        </span>

        <h3 class="relative mt-5 font-editorial text-2xl font-semibold tracking-tight text-ink">
            {{ title }}
        </h3>
        <p class="relative mx-auto mt-2 max-w-sm text-sm font-medium leading-relaxed text-ink/50">
            {{ description }}
        </p>

        <div class="relative mt-7 flex flex-wrap items-center justify-center gap-3">
            <slot name="action">
                <button
                    v-if="retry"
                    type="button"
                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    @click="$emit('retry')"
                >
                    <i class="ti ti-refresh" aria-hidden="true" />
                    Try again
                </button>
                <Link
                    v-if="secondaryHref"
                    :href="secondaryHref"
                    class="tap-target inline-flex items-center gap-2 rounded-2xl border border-ink/10 bg-white px-5 py-3 text-sm font-bold text-ink/70 transition-colors hover:border-ink/20 hover:text-ink"
                >
                    {{ secondaryLabel }}
                </Link>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: { type: String, default: 'ti ti-wifi-off' },
    title: { type: String, default: 'Something went wrong' },
    description: {
        type: String,
        default: 'We couldn’t load this right now. Check your connection and try again.',
    },
    retry: { type: Boolean, default: true },
    secondaryHref: { type: String, default: '' },
    secondaryLabel: { type: String, default: 'Go home' },
});

defineEmits(['retry']);
</script>
