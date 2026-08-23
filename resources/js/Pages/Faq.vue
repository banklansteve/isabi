<template>
    <Head title="FAQ" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="mx-auto max-w-5xl px-5 pb-16 pt-10 sm:px-8 sm:pb-20 sm:pt-14">
            <section
                class="faq-hero relative mb-10 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-8 shadow-premium-ink sm:mb-12 sm:px-8 sm:py-10"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.18]"
                    style="
                        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                        background-size: 18px 18px;
                    "
                    aria-hidden="true"
                />

                <div class="relative max-w-2xl">
                    <p
                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                        Help centre
                    </p>
                    <h1
                        class="mt-3 font-editorial text-[2.1rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.6rem]"
                    >
                        Frequently asked questions
                    </h1>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-white/65 sm:text-base">
                        Clear answers about pricing, reviews, your page, and how Isabi works — without
                        the sales gloss.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="route('how-it-works')"
                            class="tap-target inline-flex items-center gap-2 rounded-2xl bg-white/12 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/18"
                        >
                            How it works
                            <i class="ti ti-arrow-right text-base" aria-hidden="true" />
                        </Link>
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('help.index')"
                            class="tap-target inline-flex items-center gap-2 rounded-2xl bg-coral px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                        >
                            Help & support
                        </Link>
                    </div>
                </div>
            </section>

            <nav class="mb-10 flex gap-2 overflow-x-auto pb-1" aria-label="FAQ sections">
                <a
                    v-for="group in groups"
                    :key="group.id"
                    :href="`#${group.id}`"
                    class="tap-target inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-3.5 py-2 text-xs font-bold text-ink/70 shadow-sm ring-1 ring-ink/[0.06] transition-colors hover:text-deep"
                >
                    <i :class="group.icon" aria-hidden="true" />
                    {{ group.title }}
                </a>
            </nav>

            <FaqAccordion :groups="groups" />

            <div
                class="mt-14 flex flex-col items-start justify-between gap-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-6 py-8 text-white sm:flex-row sm:items-center sm:px-10 sm:py-10"
            >
                <div class="max-w-lg">
                    <h2 class="font-editorial text-2xl font-semibold tracking-tight sm:text-3xl">
                        Still need a hand?
                    </h2>
                    <p class="mt-2 text-sm font-medium text-white/60">
                        Browse how it works, or reach the team — we’re here for artisans building real
                        proof.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('how-it-works')"
                        class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-white/12 px-5 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/18"
                    >
                        How it works
                    </Link>
                    <Link
                        v-if="canRegister && !$page.props.auth?.user"
                        :href="route('register')"
                        class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-coral px-5 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                    >
                        Create your free page
                    </Link>
                    <Link
                        v-else-if="$page.props.auth?.user"
                        :href="route('help.chat')"
                        class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-coral px-5 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                    >
                        Chat with support
                    </Link>
                    <Link
                        v-else
                        :href="route('contact')"
                        class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-coral px-5 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                    >
                        Contact us
                    </Link>
                </div>
            </div>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import FaqAccordion from '@/Components/Help/FaqAccordion.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { helpGroups } from '@/Data/helpTopics';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const groups = helpGroups;
</script>

<style scoped>
.faq-hero {
    animation: rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .faq-hero {
        animation: none;
    }
}
</style>
