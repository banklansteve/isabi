<template>
    <Head title="FAQ" />

    <div class="min-h-dvh bg-pale text-ink">
        <header
            class="sticky top-0 z-40 border-b border-ink/10 bg-white/95 shadow-nav backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-3 sm:px-8"
                style="padding-top: max(0.75rem, env(safe-area-inset-top))"
            >
                <Link
                    href="/"
                    class="font-display text-[1.35rem] font-extrabold tracking-tight text-ink"
                >
                    Isabi
                </Link>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="canLogin && !$page.props.auth.user"
                        :href="route('login')"
                        class="tap-target inline-flex items-center px-3 text-sm font-semibold text-ink/65 transition-colors hover:text-ink"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-4 text-sm font-semibold text-white transition-colors hover:bg-coral-deep"
                    >
                        Get started
                    </Link>
                    <Link
                        v-else-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-4 text-sm font-semibold text-white transition-colors hover:bg-coral-deep"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-5 py-12 sm:px-8 sm:py-16">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-base">Help</p>
            <h1 class="mt-3 font-display text-display-md text-ink">
                Frequently asked questions
            </h1>
            <p class="mt-4 max-w-2xl text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                Clear answers about pricing, reviews, privacy, and how Isabi works — without the
                sales gloss.
            </p>

            <div class="mt-12 space-y-10">
                <section
                    v-for="group in groups"
                    :key="group.title"
                >
                    <h2 class="font-display text-xl font-bold tracking-tight text-ink sm:text-2xl">
                        {{ group.title }}
                    </h2>
                    <div class="mt-5 space-y-4">
                        <article
                            v-for="item in group.items"
                            :id="item.id"
                            :key="item.id"
                            class="scroll-mt-28 rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
                        >
                            <h3 class="font-display text-lg font-bold tracking-tight text-ink sm:text-xl">
                                {{ item.question }}
                            </h3>
                            <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60 sm:text-base">
                                {{ item.answer }}
                            </p>
                        </article>
                    </div>
                </section>
            </div>

            <div
                class="mt-16 flex flex-col items-start justify-between gap-6 rounded-[1.75rem] bg-ink px-6 py-8 text-white sm:flex-row sm:items-center sm:px-10 sm:py-10"
            >
                <div class="max-w-lg">
                    <h2 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">
                        Ready to build your page?
                    </h2>
                    <p class="mt-2 text-sm font-medium text-white/60">
                        Free to start. No card. Your work, backed by real clients.
                    </p>
                </div>
                <Link
                    v-if="canRegister && !$page.props.auth.user"
                    :href="route('register')"
                    class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-coral px-6 text-sm font-semibold text-white transition-colors hover:bg-coral-deep"
                >
                    Create your free page
                </Link>
                <Link
                    v-else-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-coral px-6 text-sm font-semibold text-white transition-colors hover:bg-coral-deep"
                >
                    Open dashboard
                </Link>
                <Link
                    v-else
                    :href="route('home')"
                    class="tap-target inline-flex shrink-0 items-center justify-center rounded-2xl bg-coral px-6 text-sm font-semibold text-white transition-colors hover:bg-coral-deep"
                >
                    Back to home
                </Link>
            </div>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
});

const groups = [
    {
        title: 'Getting started',
        items: [
            {
                id: 'free-to-start',
                question: 'Is Isabi really free to start?',
                answer: 'Yes. Creating your page, logging jobs, and sharing your profile are all free — no card required at signup. You only pay if you want more than 5 client review requests a month, or extras like a custom link.',
            },
            {
                id: 'no-card',
                question: 'Do I need a bank card or debit account to sign up?',
                answer: "No. Signup is free and needs nothing but your email address and a few business details. When you do choose to pay later to unlock more services, you can use bank transfer, USSD, or card — whichever you're comfortable with. We never save your card details or charge you automatically.",
            },
            {
                id: 'few-jobs',
                question: "What if I don't have many jobs yet?",
                answer: 'Start with the next job you finish. A short, honest timeline beats an empty page. Free accounts are meant for building from zero — you don’t need a long history to join.',
            },
        ],
    },
    {
        title: 'Reviews & trust',
        items: [
            {
                id: 'self-reviews',
                question: 'Can I write my own reviews, or ask a client to say something nice?',
                answer: "No — and that's by design. Only clients can submit a review, through a private link tied to a specific logged job. You can't write, edit, or approve what they say. This is what makes reviews on Isabi worth more than a screenshot.",
            },
            {
                id: 'bad-review',
                question: 'Can I remove a bad review?',
                answer: 'No — not just because you dislike it. Fair client feedback stays with the job; that’s what makes the page trustworthy. If a review is abusive, spam, or clearly about the wrong person, you can flag it for our team to check. We won’t delete a genuine rating to protect anyone’s feelings.',
            },
            {
                id: 'client-whatsapp',
                question: "What if my client doesn't have WhatsApp or isn't tech-savvy?",
                answer: "The review link works in any browser — WhatsApp is just the easiest way to send it, since most clients already have it. They don't need to download an app or create an account to leave a review.",
            },
            {
                id: 'client-account',
                question: 'Do my clients need to create an account?',
                answer: 'No. They open the review link, leave a rating and comment, and they’re done — usually in under a minute. No app download, no signup.',
            },
        ],
    },
    {
        title: 'Your page & extras',
        items: [
            {
                id: 'public-profile',
                question: 'Is my profile public? Can anyone see it?',
                answer: "Yes, that's the point. Your page is built to be shared — with a link or QR code — so new customers can check your work and reviews before they contact you. You control what you log; you can't hide reviews you don't like once a client submits them.",
            },
            {
                id: 'qr-cost',
                question: 'How much does the QR card cost?',
                answer: 'You can show and share your QR for free on your page. A high-resolution download for print uses credits (or is included with Annual). Physical cards or stickers are optional extras — typically from about ₦1,500 depending on quantity and finish.',
            },
        ],
    },
];
</script>
