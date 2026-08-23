<template>
    <div class="relative min-h-dvh overflow-hidden bg-[#071427] text-white">
        <Head title="Page not found" />

        <!-- Atmosphere -->
        <div
            class="pointer-events-none absolute inset-0"
            style="background:
                radial-gradient(ellipse 70% 55% at 12% 18%, rgba(47,111,237,0.32), transparent 58%),
                radial-gradient(ellipse 55% 45% at 88% 12%, rgba(255,106,61,0.22), transparent 52%),
                radial-gradient(ellipse 50% 40% at 70% 90%, rgba(47,111,237,0.14), transparent 55%),
                linear-gradient(180deg, #071427 0%, #0b1f3a 55%, #0c2140 100%);"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -left-24 top-1/3 h-72 w-72 rounded-full bg-base/20 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -right-20 bottom-10 h-64 w-64 rounded-full bg-coral/15 blur-3xl"
            aria-hidden="true"
        />

        <header
            class="relative z-20 mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-8"
            style="padding-top: max(1rem, env(safe-area-inset-top))"
        >
            <Link
                :href="route('home')"
                class="font-display text-[1.35rem] font-extrabold tracking-tight text-white"
            >
                Isabi
            </Link>
            <div class="flex items-center gap-2">
                <Link
                    v-if="canLogin && !user"
                    :href="route('login')"
                    class="tap-target inline-flex items-center px-3 text-sm font-semibold text-white/70 transition-colors hover:text-white"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister && !user"
                    :href="route('register')"
                    class="tap-target inline-flex items-center justify-center rounded-xl bg-coral px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-coral-deep sm:rounded-2xl"
                >
                    Get started
                </Link>
                <Link
                    v-else-if="user"
                    :href="route('dashboard')"
                    class="tap-target inline-flex items-center justify-center rounded-xl bg-coral px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-coral-deep sm:rounded-2xl"
                >
                    Dashboard
                </Link>
            </div>
        </header>

        <main
            class="relative z-10 mx-auto flex min-h-[calc(100dvh-5.5rem)] max-w-6xl flex-col items-center justify-center px-4 pb-16 pt-6 text-center sm:px-8 sm:pb-20"
        >
            <!-- Floating “ghost job card” -->
            <div class="error-float relative mb-8 w-full max-w-sm sm:mb-10" aria-hidden="true">
                <div
                    class="absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-base/35 via-transparent to-coral/25 blur-md"
                />
                <div
                    class="relative overflow-hidden rounded-2xl bg-white/[0.06] p-3 ring-1 ring-white/15 backdrop-blur-sm sm:p-3.5"
                >
                    <div class="grid grid-cols-3 gap-1.5">
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="aspect-[4/3] rounded-lg bg-white/[0.07] ring-1 ring-white/10"
                            :class="n === 2 ? 'error-tile-pulse' : ''"
                        />
                    </div>
                    <div class="mt-3 space-y-2 px-1 text-left">
                        <div class="h-2.5 w-2/3 rounded-full bg-white/20" />
                        <div class="h-2 w-full rounded-full bg-white/10" />
                        <div class="h-2 w-4/5 rounded-full bg-white/10" />
                    </div>

                    <!-- Crossed-out stamp -->
                    <div
                        class="pointer-events-none absolute inset-0 flex items-center justify-center"
                    >
                        <span
                            class="error-stamp rotate-[-12deg] rounded-xl border-2 border-coral/80 px-4 py-2 font-display text-sm font-extrabold uppercase tracking-[0.2em] text-coral shadow-[0_0_40px_-8px_rgba(255,106,61,0.55)]"
                        >
                            Not on the log
                        </span>
                    </div>
                </div>
            </div>

            <p
                class="error-rise text-[11px] font-bold uppercase tracking-[0.22em] text-coral-tint/90"
                style="animation-delay: 60ms"
            >
                Error 404
            </p>

            <h1
                class="error-rise mt-3 font-editorial text-[clamp(2.4rem,8vw,4.25rem)] font-semibold leading-[1.05] tracking-tight text-white"
                style="animation-delay: 120ms"
            >
                This job never got logged
            </h1>

            <p
                class="error-rise mx-auto mt-4 max-w-lg text-[15px] font-medium leading-relaxed text-white/60 sm:text-base"
                style="animation-delay: 180ms"
            >
                The page you’re looking for isn’t here — maybe the link aged out, the URL took a
                wrong turn, or this work never made it onto someone’s Isabi page.
            </p>

            <div
                class="error-rise mt-8 flex w-full max-w-md flex-col gap-2.5 sm:max-w-none sm:flex-row sm:justify-center sm:gap-3"
                style="animation-delay: 240ms"
            >
                <Link
                    :href="route('home')"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-xl bg-coral px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-coral-deep sm:rounded-2xl"
                >
                    <i class="ti ti-home text-lg" aria-hidden="true" />
                    Back to home
                </Link>
                <Link
                    v-if="user"
                    :href="route('dashboard')"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-6 py-3.5 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/15 sm:rounded-2xl"
                >
                    <i class="ti ti-layout-dashboard text-lg" aria-hidden="true" />
                    Open dashboard
                </Link>
                <Link
                    v-else-if="canRegister"
                    :href="route('register')"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-6 py-3.5 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/15 sm:rounded-2xl"
                >
                    <i class="ti ti-sparkles text-lg" aria-hidden="true" />
                    Create your free page
                </Link>
            </div>

            <!-- Quick links -->
            <nav
                class="error-rise mt-10 w-full max-w-lg"
                style="animation-delay: 300ms"
                aria-label="Helpful links"
            >
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/35">
                    Or try one of these
                </p>
                <ul class="mt-3 grid gap-2 sm:grid-cols-3">
                    <li v-for="link in quickLinks" :key="link.href">
                        <Link
                            :href="link.href"
                            class="tap-target group flex items-center gap-3 rounded-xl bg-white/[0.04] px-3.5 py-3 text-left ring-1 ring-white/10 transition-colors hover:bg-white/[0.08] hover:ring-white/20 sm:flex-col sm:items-start sm:gap-2 sm:px-4 sm:py-4"
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10 text-coral transition-transform duration-300 group-hover:scale-105"
                            >
                                <i :class="link.icon" class="text-base" aria-hidden="true" />
                            </span>
                            <span>
                                <span class="block text-sm font-bold text-white">{{ link.label }}</span>
                                <span class="mt-0.5 block text-[11px] font-medium text-white/40">
                                    {{ link.hint }}
                                </span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <p
                class="error-rise mt-12 text-xs font-medium text-white/30"
                style="animation-delay: 360ms"
            >
                Still stuck?
                <Link
                    :href="route('contact')"
                    class="font-bold text-white/55 underline-offset-2 transition-colors hover:text-white hover:underline"
                >
                    Contact support
                </Link>
            </p>
        </main>
    </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
});

const user = computed(() => usePage().props.auth?.user ?? null);

const quickLinks = [
    {
        href: route('faq'),
        label: 'FAQ',
        hint: 'How Isabi works',
        icon: 'ti ti-help',
    },
    {
        href: route('about'),
        label: 'About',
        hint: 'Why we built this',
        icon: 'ti ti-building-store',
    },
    {
        href: '/#pricing',
        label: 'Pricing',
        hint: 'Simple & clear',
        icon: 'ti ti-tag',
    },
];
</script>

<style scoped>
.error-rise {
    animation: error-rise 560ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.error-float {
    animation: error-float 5.5s ease-in-out infinite;
}

.error-stamp {
    animation: error-stamp 700ms cubic-bezier(0.22, 1, 0.36, 1) 280ms both;
}

.error-tile-pulse {
    animation: error-tile 2.8s ease-in-out infinite;
}

@keyframes error-rise {
    from {
        opacity: 0;
        transform: translate3d(0, 14px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@keyframes error-float {
    0%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(0, -8px, 0);
    }
}

@keyframes error-stamp {
    from {
        opacity: 0;
        transform: rotate(-12deg) scale(1.12);
    }
    to {
        opacity: 1;
        transform: rotate(-12deg) scale(1);
    }
}

@keyframes error-tile {
    0%,
    100% {
        background-color: rgba(255, 255, 255, 0.07);
    }
    50% {
        background-color: rgba(255, 106, 61, 0.18);
    }
}

@media (prefers-reduced-motion: reduce) {
    .error-rise,
    .error-float,
    .error-stamp,
    .error-tile-pulse {
        animation: none;
    }
}
</style>
