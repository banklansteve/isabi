<template>
    <div class="relative min-h-dvh overflow-hidden bg-[#071427] text-white">
        <Head title="Page unavailable" />

        <div
            class="pointer-events-none absolute inset-0"
            style="
                background:
                    radial-gradient(ellipse 70% 55% at 12% 18%, rgba(47, 111, 237, 0.28), transparent 58%),
                    radial-gradient(ellipse 55% 45% at 88% 12%, rgba(255, 106, 61, 0.18), transparent 52%),
                    linear-gradient(180deg, #071427 0%, #0b1f3a 55%, #0c2140 100%);
            "
            aria-hidden="true"
        />

        <header
            class="relative z-20 mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-8"
            style="padding-top: max(1rem, env(safe-area-inset-top))"
        >
            <Link
                :href="route('home')"
                class="inline-flex items-center gap-2.5 font-display text-[1.35rem] font-extrabold tracking-tight text-white"
            >
                <BrandMark variant="mark" color="#FFFFFF" class="h-8 w-8 shrink-0" />
                Kraftrack
            </Link>
            <Link
                :href="route('public.directory')"
                class="tap-target inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/16"
            >
                Browse artisans
            </Link>
        </header>

        <main class="relative z-10 mx-auto flex max-w-lg flex-col items-center px-5 pb-16 pt-16 text-center sm:pt-24">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15"
                aria-hidden="true"
            >
                <i class="ti ti-eye-off text-2xl text-white/80" />
            </span>
            <h1 class="mt-6 font-editorial text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                Page unavailable
            </h1>
            <p class="mt-3 text-sm font-medium leading-relaxed text-white/60 sm:text-base">
                {{ message }}
            </p>
            <Link
                :href="route('public.directory')"
                class="mt-8 tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.55)] transition-colors hover:bg-base-hover"
            >
                Find other artisans
                <i class="ti ti-arrow-right text-base" aria-hidden="true" />
            </Link>
        </main>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BrandMark from '@/Components/BrandMark.vue';

defineProps({
    message: {
        type: String,
        default: 'This artisan’s public page is turned off right now.',
    },
});
</script>
