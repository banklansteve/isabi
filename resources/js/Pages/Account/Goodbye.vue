<template>
    <Head title="We’re sorry to see you go" />

    <div class="min-h-dvh bg-pale text-ink">
        <div
            class="relative isolate overflow-hidden bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 pb-16 pt-10 text-white sm:px-8 sm:pb-20 sm:pt-14"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_15%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(50%_60%_at_100%_100%,rgba(255,106,61,0.16),transparent_50%)]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.16]"
                style="
                    background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                    background-size: 18px 18px;
                "
                aria-hidden="true"
            />

            <div class="relative mx-auto max-w-2xl text-center">
                <Link
                    :href="route('home')"
                    class="font-display text-[1.45rem] font-extrabold tracking-tight text-white"
                >
                    Kraftrack
                </Link>
                <p
                    class="mt-8 inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                    Account closed
                </p>
                <h1
                    class="mt-3 font-editorial text-[2.15rem] font-semibold leading-[1.12] tracking-tight sm:text-[2.75rem]"
                >
                    We’re sorry to see you go
                </h1>
                <p class="mx-auto mt-4 max-w-lg text-sm font-medium leading-relaxed text-white/70 sm:text-[15px]">
                    Your page is gone, but the craft isn’t. Whenever you’re ready to show clients real
                    work again, Kraftrack will be here — same calm tools, same honest proof.
                </p>
            </div>
        </div>

        <main class="relative z-[1] -mt-8 px-4 pb-16 sm:-mt-10 sm:px-6">
            <div
                class="mx-auto max-w-xl overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-9"
            >
                <div class="space-y-5">
                    <div class="flex gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-tint text-base text-deep"
                        >
                            <i class="ti ti-sparkles" aria-hidden="true" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-ink">Your next chapter still counts</p>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-ink/55">
                                Every finished job is worth remembering. When you’re ready, rebuild your
                                page in minutes and let clients vouch for you again.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-tint text-base text-deep"
                        >
                            <i class="ti ti-heart-handshake" aria-hidden="true" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-ink">Door’s open</p>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-ink/55">
                                Changed your mind? Create a new account anytime — no pressure, no
                                judgment. We’ll help you look trusted from day one.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="tap-target inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        Come back to Kraftrack
                        <i class="ti ti-arrow-right" aria-hidden="true" />
                    </Link>
                    <Link
                        :href="route('home')"
                        class="tap-target inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-ink/10 bg-white px-5 py-3.5 text-sm font-bold text-ink/70 transition-colors hover:border-ink/20 hover:text-ink"
                    >
                        Back to home
                    </Link>
                </div>

                <p class="mt-6 text-center text-xs font-medium text-ink/35">
                    Need help with something else?
                    <a href="mailto:hello@kraftrack.com" class="font-bold text-base-action hover:text-base-hover">
                        hello@kraftrack.com
                    </a>
                </p>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: true },
});
</script>
