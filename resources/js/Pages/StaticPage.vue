<template>
    <Head :title="title" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar />

        <main class="mx-auto max-w-3xl px-5 pb-16 pt-10 sm:px-8 sm:pb-20 sm:pt-14">
            <section
                class="relative mb-8 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-8 shadow-premium-ink sm:mb-10 sm:px-8 sm:py-10"
                style="
                    background-image: linear-gradient(145deg, #1a4fb5 0%, #123b72 48%, #071427 100%);
                "
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div class="relative">
                    <p
                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                        {{ eyebrow }}
                    </p>
                    <h1
                        class="mt-3 font-editorial text-[2.1rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.6rem]"
                    >
                        {{ title }}
                    </h1>
                    <p class="mt-3 max-w-xl text-sm font-medium leading-relaxed text-white/65 sm:text-base">
                        {{ summary }}
                    </p>
                </div>
            </section>

            <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
                <p class="text-sm font-medium leading-relaxed text-ink/65 sm:text-base">
                    {{ body }}
                </p>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

<script setup>
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head } from '@inertiajs/vue3';

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
