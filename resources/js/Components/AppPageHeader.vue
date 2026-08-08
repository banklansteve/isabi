<template>
    <header class="app-page-header relative mb-6 sm:mb-8">
        <div
            class="relative overflow-hidden rounded-[1.75rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
        >
            <!-- Atmosphere -->
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white via-pale to-tint/90"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -left-20 -top-24 h-56 w-56 rounded-full bg-base/[0.12] blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -bottom-24 -right-16 h-52 w-52 rounded-full bg-coral/[0.10] blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.35]"
                style="
                    background-image: radial-gradient(rgba(11, 31, 58, 0.07) 0.8px, transparent 0.8px);
                    background-size: 18px 18px;
                "
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-base/40 to-transparent"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-base via-[#4B86F0] to-coral/70"
                aria-hidden="true"
            />

            <div class="relative flex flex-col gap-5 p-5 sm:flex-row sm:items-stretch sm:gap-8 sm:p-7">
                <div class="min-w-0 flex-1">
                    <div
                        v-if="backHref || eyebrow || $slots.eyebrow"
                        class="mb-3.5 flex flex-wrap items-center gap-x-3 gap-y-2"
                    >
                        <Link
                            v-if="backHref"
                            :href="backHref"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold text-ink/50 shadow-sm ring-1 ring-ink/[0.06] transition-colors hover:text-ink"
                        >
                            <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                            {{ backLabel }}
                        </Link>

                        <span
                            v-if="eyebrow || $slots.eyebrow"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-base"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-base" aria-hidden="true" />
                            <slot name="eyebrow">{{ eyebrow }}</slot>
                        </span>
                    </div>

                    <h1
                        class="max-w-xl text-[1.75rem] font-bold leading-[1.12] tracking-tight text-ink sm:text-[2.15rem]"
                    >
                        <slot name="title">{{ title }}</slot>
                    </h1>

                    <p
                        v-if="description || $slots.description"
                        class="mt-2.5 max-w-lg text-sm font-medium leading-relaxed text-ink/50 sm:text-[0.95rem]"
                    >
                        <slot name="description">{{ description }}</slot>
                    </p>

                    <div v-if="$slots.meta" class="mt-4">
                        <slot name="meta" />
                    </div>
                </div>

                <div
                    v-if="icon || $slots.mark || $slots.actions"
                    class="flex shrink-0 items-start justify-between gap-3 sm:flex-col sm:items-end"
                    :class="icon || $slots.mark ? 'sm:justify-between' : 'sm:justify-start'"
                >
                    <div
                        v-if="icon || $slots.mark"
                        class="header-mark relative flex h-16 w-16 items-center justify-center rounded-2xl bg-white/80 text-2xl text-base shadow-[0_10px_30px_-12px_rgba(47,111,237,0.45)] ring-1 ring-base/15 sm:h-[4.5rem] sm:w-[4.5rem] sm:text-[1.75rem]"
                        aria-hidden="true"
                    >
                        <span
                            class="pointer-events-none absolute inset-0 rounded-2xl bg-gradient-to-br from-base/10 to-transparent"
                        />
                        <slot name="mark">
                            <i :class="icon" />
                        </slot>
                    </div>

                    <div v-if="$slots.actions" class="flex flex-wrap items-center justify-end gap-2">
                        <slot name="actions" />
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    icon: { type: String, default: '' },
    backHref: { type: String, default: '' },
    backLabel: { type: String, default: 'Back' },
});
</script>

<style scoped>
.header-mark {
    animation: mark-in 520ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes mark-in {
    from {
        opacity: 0;
        transform: translateY(8px) scale(0.94);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
