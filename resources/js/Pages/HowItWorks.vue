<template>
    <Head title="How it works" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <div class="mx-auto max-w-5xl px-5 pt-10 sm:px-8 sm:pt-14">
                <section
                    class="hiw-hero relative mb-2 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-8 shadow-premium-ink sm:mb-4 sm:px-8 sm:py-10"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.16),transparent_50%)]"
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute inset-0 opacity-[0.18]"
                        style="
                            background-image: radial-gradient(
                                rgba(255, 255, 255, 0.1) 0.7px,
                                transparent 0.7px
                            );
                            background-size: 18px 18px;
                        "
                        aria-hidden="true"
                    />
                    <h1
                        class="relative font-editorial text-[2.1rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.75rem]"
                    >
                        How Isabi works
                    </h1>
                </section>
            </div>

            <ol class="mx-auto mt-10 max-w-6xl space-y-16 px-5 sm:mt-14 sm:space-y-24 sm:px-8">
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="hiw-step grid items-center gap-8 lg:grid-cols-2 lg:gap-14 xl:gap-20"
                >
                    <div
                        class="min-w-0"
                        :class="index % 2 === 1 ? 'lg:order-2' : 'lg:order-1'"
                    >
                        <span
                            class="inline-flex items-center rounded-full bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-white shadow-[0_8px_20px_-10px_rgba(26,79,181,0.45)]"
                        >
                            Step 0{{ index + 1 }}
                        </span>
                        <h2
                            class="mt-4 font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[2rem]"
                        >
                            {{ step.title }}
                        </h2>
                        <p
                            class="mt-3 max-w-md text-sm font-medium leading-relaxed text-ink/55 sm:text-[0.95rem] sm:leading-relaxed"
                        >
                            {{ step.body }}
                        </p>
                    </div>

                    <div
                        class="relative flex justify-center"
                        :class="index % 2 === 1 ? 'lg:order-1' : 'lg:order-2'"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 -z-0 scale-95 rounded-full bg-[radial-gradient(closest-side,rgba(26,79,181,0.1),transparent)]"
                            aria-hidden="true"
                        />
                        <PhoneFrame
                            :light-status="
                                step.mockup === 'signup' ||
                                step.mockup === 'setup' ||
                                step.mockup === 'log'
                            "
                        >
                            <HowItWorksMockups :variant="step.mockup" />
                        </PhoneFrame>
                    </div>
                </li>
            </ol>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import HowItWorksMockups from '@/Components/Marketing/HowItWorksMockups.vue';
import PhoneFrame from '@/Components/Marketing/PhoneFrame.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const steps = [
    {
        title: 'Sign up',
        body: 'Email, password, WhatsApp number, and trade — your account is ready in under a minute. No card required.',
        mockup: 'signup',
    },
    {
        title: 'Set up your page',
        body: 'Add your service area, plus an optional photo and short bio. This is when your public profile starts to look like you.',
        mockup: 'setup',
    },
    {
        title: 'Log a finished job',
        body: 'A quick note on what was done, with an optional photo. Under 30 seconds — and the start of your track record.',
        mockup: 'log',
    },
    {
        title: 'Send a review request',
        body: 'A WhatsApp link tied to that specific job goes to your client — not a generic “leave me a review” ask.',
        mockup: 'send',
    },
    {
        title: 'The client leaves a review',
        body: 'They tap the link, rate the job, and write in their own words. You can’t write, edit, or approve it — it’s entirely theirs.',
        mockup: 'review',
    },
    {
        title: 'The review lands on your page',
        body: 'It appears next to the job it belongs to — timestamped and unedited — visible to anyone who visits from then on.',
        mockup: 'share',
    },
];
</script>

<style scoped>
.hiw-hero,
.hiw-step {
    animation: rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .hiw-hero,
    .hiw-step {
        animation: none;
    }
}
</style>
