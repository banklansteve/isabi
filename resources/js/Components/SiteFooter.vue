<template>
    <footer
        class="relative overflow-hidden bg-pale text-ink"
        :class="padForMobileCta ? 'pb-[calc(6.5rem+env(safe-area-inset-bottom))] lg:pb-0' : ''"
    >
        <!-- Soft transition from previous section -->
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-white/80 to-transparent"
            aria-hidden="true"
        />

        <div class="relative mx-auto max-w-7xl px-5 pt-16 sm:px-8 lg:px-10 lg:pt-20">
            <!-- Brand masthead -->
            <div class="relative overflow-hidden rounded-[2rem] bg-ink px-6 py-8 text-white sm:px-10 sm:py-10">
                <div
                    class="pointer-events-none absolute -right-8 top-0 font-display text-[6.5rem] font-extrabold leading-none text-white/[0.04] sm:text-[8rem]"
                    aria-hidden="true"
                >
                    Isabi
                </div>
                <div
                    class="pointer-events-none absolute -right-10 -top-16 h-48 w-48 rounded-full bg-coral/20 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute -bottom-20 left-10 h-40 w-40 rounded-full bg-base/30 blur-3xl"
                    aria-hidden="true"
                />

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-lg">
                        <Link
                            :href="route('home')"
                            class="font-display text-2xl font-extrabold tracking-tight text-white"
                        >
                            Isabi
                        </Link>
                        <p class="mt-3 font-voice text-xl leading-snug text-white/85 sm:text-2xl">
                            Proof of work for skilled trades.
                        </p>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-white/50">
                            Built around real clients, real jobs, and reviews you can’t fake.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            v-if="!$page.props.auth?.user && canRegister"
                            :href="route('register')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-5 text-sm font-bold text-white transition-colors duration-300 hover:bg-coral-deep"
                        >
                            Create your free page
                        </Link>
                        <Link
                            v-else-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-5 text-sm font-bold text-white transition-colors duration-300 hover:bg-coral-deep"
                        >
                            Open dashboard
                        </Link>
                        <a
                            href="mailto:hello@isabi.dev"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-white/10 px-5 text-sm font-semibold text-white ring-1 ring-white/15 transition-colors duration-300 hover:bg-white/15"
                        >
                            hello@isabi.dev
                        </a>
                    </div>
                </div>
            </div>

            <!-- Link grid -->
            <div class="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                <div
                    v-for="column in columns"
                    :key="column.title"
                >
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-ink/35">
                        {{ column.title }}
                    </p>
                    <ul class="mt-4 space-y-2.5">
                        <li
                            v-for="link in column.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.type === 'route'"
                                :href="route(link.name)"
                                class="tap-target group inline-flex items-center gap-1.5 text-sm font-semibold text-ink/65 transition-colors duration-300 hover:text-ink"
                            >
                                {{ link.label }}
                                <i
                                    class="ti ti-arrow-up-right text-sm opacity-0 transition-opacity duration-300 group-hover:opacity-60"
                                    aria-hidden="true"
                                />
                            </Link>
                            <button
                                v-else-if="link.type === 'action' && link.action === 'cookies'"
                                type="button"
                                class="tap-target group inline-flex items-center gap-1.5 text-left text-sm font-semibold text-ink/65 transition-colors duration-300 hover:text-ink"
                                @click="openCookiePreferences"
                            >
                                {{ link.label }}
                            </button>
                            <a
                                v-else-if="link.type === 'hash'"
                                :href="homeHash(link.hash)"
                                class="tap-target group inline-flex items-center gap-1.5 text-sm font-semibold text-ink/65 transition-colors duration-300 hover:text-ink"
                            >
                                {{ link.label }}
                                <i
                                    class="ti ti-arrow-up-right text-sm opacity-0 transition-opacity duration-300 group-hover:opacity-60"
                                    aria-hidden="true"
                                />
                            </a>
                            <a
                                v-else-if="link.type === 'external'"
                                :href="link.href"
                                :target="link.href.startsWith('http') ? '_blank' : undefined"
                                :rel="link.href.startsWith('http') ? 'noopener noreferrer' : undefined"
                                class="tap-target group inline-flex items-center gap-1.5 text-sm font-semibold text-ink/65 transition-colors duration-300 hover:text-ink"
                            >
                                {{ link.label }}
                                <i
                                    class="ti ti-arrow-up-right text-sm opacity-0 transition-opacity duration-300 group-hover:opacity-60"
                                    aria-hidden="true"
                                />
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div
                class="mt-12 flex flex-col gap-4 border-t border-ink/10 py-6 sm:flex-row sm:items-center sm:justify-between lg:mt-14"
            >
                <p class="text-xs font-medium text-ink/40">
                    © {{ currentYear }} Isabi. All rights reserved.
                </p>
                <p class="text-xs font-medium text-ink/40">
                    Made for artisans across Nigeria who let the work speak.
                </p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    /**
     * Extra bottom padding for pages with a mobile sticky CTA (e.g. landing).
     */
    padForMobileCta: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const currentYear = new Date().getFullYear();

const homeHash = (hash) => `/#${hash}`;

const openCookiePreferences = () => {
    window.dispatchEvent(new CustomEvent('isabi:open-cookie-consent'));
};

const columns = [
    {
        title: 'Product',
        links: [
            { type: 'hash', hash: 'how-it-works', label: 'How it works' },
            { type: 'hash', hash: 'on-your-page', label: "What's on your page" },
            { type: 'hash', hash: 'pricing', label: 'Pricing' },
            { type: 'hash', hash: 'samples', label: 'Sample profiles' },
            { type: 'route', name: 'faq', label: 'FAQ' },
        ],
    },
    {
        title: 'Company',
        links: [
            { type: 'route', name: 'about', label: 'About' },
            { type: 'route', name: 'contact', label: 'Contact' },
            { type: 'route', name: 'careers', label: 'Careers' },
            { type: 'external', href: 'mailto:hello@isabi.dev', label: 'hello@isabi.dev' },
        ],
    },
    {
        title: 'Legal',
        links: [
            { type: 'route', name: 'terms', label: 'Terms of use' },
            { type: 'route', name: 'privacy', label: 'Privacy policy' },
            { type: 'route', name: 'cookies', label: 'Cookie policy' },
            { type: 'action', action: 'cookies', label: 'Manage cookies' },
            { type: 'route', name: 'acceptable-use', label: 'Acceptable use' },
        ],
    },
    {
        title: 'Connect',
        links: [
            { type: 'external', href: 'https://wa.me/', label: 'WhatsApp' },
            { type: 'external', href: 'https://instagram.com/', label: 'Instagram' },
            { type: 'external', href: 'https://x.com/', label: 'X / Twitter' },
            { type: 'route', name: 'login', label: 'Sign in' },
        ],
    },
];
</script>
