<template>
    <div class="relative min-h-dvh overflow-hidden bg-[#071427] text-white">
        <Head title="Maintenance" />

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
            <span
                class="inline-flex items-center gap-2.5 font-display text-[1.35rem] font-extrabold tracking-tight text-white"
            >
                <BrandMark variant="mark" color="#FFFFFF" class="h-8 w-8 shrink-0" />
                Kraftrack
            </span>
        </header>

        <main
            class="relative z-10 mx-auto flex min-h-[calc(100dvh-5.5rem)] max-w-6xl flex-col items-center justify-center px-4 pb-16 pt-6 text-center sm:px-8 sm:pb-20"
        >
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

                    <div
                        class="pointer-events-none absolute inset-0 flex items-center justify-center"
                    >
                        <span
                            class="error-stamp rotate-[-12deg] rounded-xl border-2 border-coral/80 px-4 py-2 font-display text-sm font-extrabold uppercase tracking-[0.2em] text-coral shadow-[0_0_40px_-8px_rgba(255,106,61,0.55)]"
                        >
                            Maintenance
                        </span>
                    </div>
                </div>
            </div>

            <p
                class="error-rise text-[11px] font-bold uppercase tracking-[0.22em] text-coral-tint/90"
                style="animation-delay: 60ms"
            >
                Back shortly
            </p>

            <h1
                class="error-rise mt-3 font-editorial text-[clamp(2.4rem,8vw,4.25rem)] font-semibold leading-[1.05] tracking-tight text-white"
                style="animation-delay: 120ms"
            >
                We’re tuning things up
            </h1>

            <p
                class="error-rise mx-auto mt-4 max-w-lg text-[15px] font-medium leading-relaxed text-white/60 sm:text-base"
                style="animation-delay: 180ms"
            >
                Kraftrack is offline for scheduled maintenance so we can ship a safer, faster update.
                Your account and work stay exactly where you left them — please check back soon.
            </p>

            <p
                class="error-rise mt-10 max-w-md text-sm font-medium leading-relaxed text-white/40"
                style="animation-delay: 240ms"
            >
                Urgent?
                <a
                    href="mailto:hello@kraftrack.com"
                    class="font-bold text-white/60 underline-offset-2 transition-colors hover:text-white hover:underline"
                >
                    hello@kraftrack.com
                </a>
            </p>

            <button
                type="button"
                class="error-rise mt-8 tap-target inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-3 text-sm font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/15"
                style="animation-delay: 300ms"
                @click="checkAgain"
            >
                <i class="ti ti-refresh text-base" aria-hidden="true" />
                Check if we’re back
            </button>
        </main>
    </div>
</template>

<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import { Head, router } from '@inertiajs/vue3';

const checkAgain = () => {
    // Full visit so a live site can redirect off this holding page to the
    // URL the visitor was on before maintenance started.
    router.visit(route('maintenance.show'), {
        replace: true,
        preserveScroll: false,
    });
};
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
