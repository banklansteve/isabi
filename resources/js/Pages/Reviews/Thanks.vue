<template>
    <div class="min-h-dvh bg-pale text-ink">
        <Head title="Thanks for your review" />

        <header
            class="border-b border-ink/10 bg-white/95"
        >
            <div class="mx-auto flex max-w-lg items-center px-5 py-3">
                <Link
                    :href="route('home')"
                    class="font-display text-[1.35rem] font-extrabold tracking-tight text-ink"
                >
                    Isabi
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-lg px-5 py-14 text-center sm:py-20">
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-emerald-600 ring-1 ring-emerald-100"
            >
                <i class="ti ti-check" aria-hidden="true" />
            </div>
            <h1 class="mt-6 font-display text-3xl font-extrabold tracking-tight text-ink">
                Thank you
            </h1>
            <p class="mt-3 text-sm font-medium leading-relaxed text-ink/55">
                Your review for
                <span class="font-bold text-ink">{{ artisan.name }}</span>
                is live on their public page. Honest feedback helps the next client choose with confidence.
            </p>

            <div class="mt-8 space-y-3">
                <a
                    v-if="artisan.public_url"
                    :href="artisan.public_url"
                    class="tap-target inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-semibold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    View their page
                    <i class="ti ti-arrow-right" aria-hidden="true" />
                </a>
                <Link
                    :href="route('home')"
                    class="tap-target inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-ink ring-1 ring-ink/10 transition-colors hover:bg-pale"
                >
                    Done
                </Link>
            </div>

            <p class="mt-10 text-xs font-medium leading-relaxed text-ink/40">
                Hire artisans often? You can build your own proof-of-work page on Isabi later —
                optional, whenever you’re ready.
            </p>
        </main>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    artisan: { type: Object, required: true },
});
</script>
