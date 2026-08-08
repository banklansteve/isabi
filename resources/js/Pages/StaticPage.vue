<template>
    <Head :title="title" />

    <div class="min-h-dvh bg-pale text-ink">
        <header
            class="sticky top-0 z-40 border-b border-ink/10 bg-white/95 shadow-nav backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-5 py-3 sm:px-8"
                style="padding-top: max(0.75rem, env(safe-area-inset-top))"
            >
                <Link
                    :href="route('home')"
                    class="font-display text-[1.35rem] font-extrabold tracking-tight text-ink"
                >
                    Isabi
                </Link>
                <Link
                    :href="route('home')"
                    class="tap-target inline-flex items-center text-sm font-semibold text-ink/60 transition-colors hover:text-ink"
                >
                    Home
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-5 py-14 sm:px-8 sm:py-20">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-base">
                {{ eyebrow }}
            </p>
            <h1 class="mt-3 font-display text-display-md text-ink">
                {{ title }}
            </h1>
            <p class="mt-5 text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                {{ summary }}
            </p>

            <div class="mt-10 rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
                <p class="text-sm font-medium leading-relaxed text-ink/65 sm:text-base">
                    {{ body }}
                </p>
                <p class="mt-6 text-sm font-semibold text-ink/40">
                    Full page coming soon — this route is live so footer links stay clean while we
                    write the final copy.
                </p>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

<script setup>
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    eyebrow: {
        type: String,
        default: 'Isabi',
    },
    summary: {
        type: String,
        required: true,
    },
    body: {
        type: String,
        required: true,
    },
});
</script>
