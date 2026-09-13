<template>
    <Head title="About Kraftrack" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <!-- Hero -->
            <div class="mx-auto max-w-5xl px-4 pt-8 sm:px-8 sm:pt-12">
                <section
                    class="relative overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-10 shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-14"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.16),transparent_50%)]"
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute inset-0 opacity-[0.16]"
                        style="
                            background-image: radial-gradient(
                                rgba(255, 255, 255, 0.1) 0.7px,
                                transparent 0.7px
                            );
                            background-size: 18px 18px;
                        "
                        aria-hidden="true"
                    />
                    <div class="relative max-w-2xl">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Company
                        </p>
                        <h1
                            class="mt-4 font-editorial text-[2.15rem] font-semibold leading-[1.08] tracking-tight text-white sm:text-[3rem]"
                        >
                            Proof that travels with the work
                        </h1>
                        <p
                            class="mt-4 max-w-xl text-sm font-medium leading-relaxed text-white/65 sm:text-base sm:leading-relaxed"
                        >
                            Kraftrack helps Nigerian artisans turn finished jobs and real client
                            reviews into a shareable track record — without self-written testimonials
                            or pay-to-look-established shortcuts.
                        </p>
                    </div>
                </section>
            </div>

            <!-- Mission -->
            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <div class="max-w-2xl">
                    <p
                        class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action"
                    >
                        Why we exist
                    </p>
                    <h2
                        class="mt-3 font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[2.1rem]"
                    >
                        Trust should come from the work itself
                    </h2>
                    <p class="mt-4 text-sm font-medium leading-relaxed text-ink/55 sm:text-[0.95rem]">
                        Across Nigeria, skilled tradespeople win jobs through referrals and WhatsApp —
                        then start over with every new client. Folders of photos and verbal promises
                        don’t travel. Self-written “testimonials” don’t either. We built Kraftrack so
                        a finished job and the client’s own words become lasting proof.
                    </p>
                </div>
            </section>

            <!-- Beliefs -->
            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action">
                    What we believe
                </p>
                <h2
                    class="mt-3 max-w-xl font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[2.1rem]"
                >
                    A few rules we won’t break
                </h2>
                <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="belief in beliefs"
                        :key="belief.title"
                        class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-tint text-deep"
                        >
                            <i :class="belief.icon" class="text-lg" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 text-sm font-bold tracking-tight text-ink">
                            {{ belief.title }}
                        </h3>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                            {{ belief.body }}
                        </p>
                    </li>
                </ul>
            </section>

            <!-- How it fits -->
            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <div
                    class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-8 text-white shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-12"
                >
                    <p
                        class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/50"
                    >
                        How it fits together
                    </p>
                    <h2
                        class="mt-3 max-w-lg font-editorial text-[1.55rem] font-semibold tracking-tight sm:text-[1.9rem]"
                    >
                        Log the job. Ask for the review. Share the page.
                    </h2>
                    <ol class="mt-8 grid gap-5 sm:grid-cols-3">
                        <li v-for="(step, i) in steps" :key="step.title" class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-coral">
                                0{{ i + 1 }}
                            </p>
                            <h3 class="mt-2 text-sm font-bold text-white">{{ step.title }}</h3>
                            <p class="mt-1.5 text-sm font-medium leading-relaxed text-white/60">
                                {{ step.body }}
                            </p>
                        </li>
                    </ol>
                    <div class="mt-8">
                        <Link
                            :href="route('how-it-works')"
                            class="tap-target inline-flex items-center gap-2 rounded-2xl bg-white/12 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/18"
                        >
                            See how it works
                            <i class="ti ti-arrow-right" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>

            <!-- For whom -->
            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
                    <div>
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action"
                        >
                            Built for artisans
                        </p>
                        <h2
                            class="mt-3 font-editorial text-[1.55rem] font-semibold tracking-tight text-ink sm:text-[1.85rem]"
                        >
                            Your work already speaks. We help it travel.
                        </h2>
                        <p class="mt-4 text-sm font-medium leading-relaxed text-ink/55">
                            Whether you are a carpenter in Ikeja, an electrician in Enugu, or a
                            fashion designer in Abuja — Kraftrack is a public page clients can trust,
                            built from jobs you actually finished and words you didn’t write yourself.
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action"
                        >
                            Useful for clients
                        </p>
                        <h2
                            class="mt-3 font-editorial text-[1.55rem] font-semibold tracking-tight text-ink sm:text-[1.85rem]"
                        >
                            Hire with evidence, not vibes
                        </h2>
                        <p class="mt-4 text-sm font-medium leading-relaxed text-ink/55">
                            Browse finished work and reviews left by previous clients. Contact via
                            WhatsApp when you’re ready. No account required to leave a review or view
                            a public page.
                        </p>
                        <Link
                            :href="route('public.directory')"
                            class="tap-target mt-5 inline-flex items-center gap-2 text-sm font-bold text-base-action hover:text-base-hover"
                        >
                            Find artisans
                            <i class="ti ti-arrow-right" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <div
                    class="flex flex-col items-start justify-between gap-6 rounded-[1.5rem] bg-white px-5 py-8 shadow-premium ring-1 ring-ink/[0.06] sm:flex-row sm:items-center sm:rounded-[1.75rem] sm:px-10 sm:py-10"
                >
                    <div class="max-w-lg">
                        <h2
                            class="font-editorial text-2xl font-semibold tracking-tight text-ink sm:text-[1.75rem]"
                        >
                            Free to start. No card required.
                        </h2>
                        <p class="mt-2 text-sm font-medium text-ink/50">
                            Create your page, log a finished job, and ask your next client for a
                            review.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                        >
                            Create your free page
                        </Link>
                        <Link
                            :href="route('contact')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-pale px-5 py-3 text-sm font-bold text-ink/70 transition-colors hover:bg-tint"
                        >
                            Contact us
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const beliefs = [
    {
        icon: 'ti ti-shield-check',
        title: 'Clients write the reviews',
        body: 'Artisans cannot invent or edit testimonials. Reviews stay tied to a real job and the client’s own words.',
    },
    {
        icon: 'ti ti-device-mobile',
        title: 'Mobile-first, WhatsApp-native',
        body: 'Most clients already live in WhatsApp. Review requests meet them there — no app download, no account friction.',
    },
    {
        icon: 'ti ti-lock-open',
        title: 'Honest by default',
        body: 'Free to start. No silent auto-renew traps. Private details like amounts and client phones stay off the public page.',
    },
];

const steps = [
    {
        title: 'Log finished work',
        body: 'Capture the job, date, and optional photos so your page shows real output.',
    },
    {
        title: 'Request a review',
        body: 'Send a private link — usually via WhatsApp. The client rates and writes in under a minute.',
    },
    {
        title: 'Share your page',
        body: 'One link or QR for prospects: timeline of jobs plus reviews they can trust.',
    },
];
</script>
