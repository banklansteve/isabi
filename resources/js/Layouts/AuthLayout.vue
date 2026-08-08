<template>
    <div class="min-h-dvh bg-pale text-ink lg:grid lg:grid-cols-2">
        <!-- Brand panel -->
        <aside
            class="relative hidden overflow-hidden bg-gradient-to-br from-[#123B72] via-[#0B1F3A] to-[#0B1F3A] text-white lg:flex lg:flex-col lg:justify-between lg:px-12 lg:py-12 xl:px-16"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(80%_60%_at_20%_20%,rgba(47,111,237,0.28),transparent_55%),radial-gradient(70%_50%_at_90%_80%,rgba(255,106,61,0.16),transparent_50%)]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.22]"
                style="
                    background-image: radial-gradient(rgba(255, 255, 255, 0.09) 0.7px, transparent 0.7px);
                    background-size: 20px 20px;
                "
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -right-8 top-28 font-display text-[8.5rem] font-extrabold leading-none tracking-tight text-white/[0.035]"
                aria-hidden="true"
            >
                Isabi
            </div>

            <div class="relative flex justify-center">
                <Link
                    :href="route('home')"
                    class="font-display text-2xl font-extrabold tracking-tight text-white"
                >
                    Isabi
                </Link>
            </div>

            <div class="relative mx-auto max-w-md text-center">
                <h2
                    class="font-display text-[2.15rem] font-extrabold leading-[1.18] tracking-tight text-white xl:text-[2.55rem]"
                >
                    {{ headline }}
                </h2>
                <p class="mt-7 text-[0.98rem] font-semibold leading-relaxed text-white/70 xl:text-base">
                    {{ support }}
                </p>

                <ul class="mt-12 space-y-5">
                    <li
                        v-for="point in points"
                        :key="point"
                        class="flex items-center justify-center gap-2.5 text-[0.92rem] font-bold leading-snug text-white/90"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/12 text-coral ring-1 ring-white/10"
                        >
                            <i class="ti ti-check text-sm" aria-hidden="true" />
                        </span>
                        <span class="text-left">{{ point }}</span>
                    </li>
                </ul>
            </div>

            <p class="relative text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-white/35">
                Built for Nigerian artisans
            </p>
        </aside>

        <!-- Form panel -->
        <div class="relative flex min-h-dvh flex-col">
            <header
                class="flex items-center justify-between px-5 py-4 sm:px-8 lg:px-10"
                style="padding-top: max(1rem, env(safe-area-inset-top))"
            >
                <Link
                    :href="route('home')"
                    class="font-display text-xl font-extrabold tracking-tight text-ink lg:hidden"
                >
                    Isabi
                </Link>
                <Link
                    :href="route('home')"
                    class="tap-target ml-auto inline-flex items-center gap-1 text-sm font-semibold text-ink/50 transition-colors hover:text-ink"
                >
                    <i class="ti ti-arrow-left text-base" aria-hidden="true" />
                    Back to home
                </Link>
            </header>

            <!-- Mobile brand blurb -->
            <div class="px-5 pb-2 sm:px-8 lg:hidden">
                <div class="mx-auto max-w-md text-center">
                    <h2 class="font-display text-2xl font-extrabold leading-tight tracking-tight text-ink">
                        {{ headline }}
                    </h2>
                    <p class="mt-3.5 text-sm font-semibold leading-relaxed text-ink/55">
                        {{ support }}
                    </p>
                </div>
            </div>

            <main class="flex flex-1 flex-col justify-center px-5 py-8 sm:px-8 lg:px-12 xl:px-16">
                <div class="mx-auto w-full max-w-md">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    headline: {
        type: String,
        default: 'Your work, backed by the people who hired you.',
    },
    support: {
        type: String,
        default:
            'Create a page that turns finished jobs and real client reviews into proof new customers can trust.',
    },
    points: {
        type: Array,
        default: () => [
            'Unlimited job logging on the free plan',
            'Client reviews you can’t write yourself',
            'Share a link or QR anywhere you work',
        ],
    },
});
</script>
