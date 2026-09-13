<template>
    <header
        class="sticky top-0 z-40 border-b border-ink/[0.07] bg-white/95 shadow-[0_1px_0_rgba(7,20,39,0.04)] backdrop-blur-xl"
        style="padding-top: env(safe-area-inset-top)"
    >
        <div
            class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4 sm:h-[3.75rem] sm:gap-4 sm:px-8"
        >
            <Link
                :href="route('home')"
                class="flex min-w-0 shrink-0 items-center gap-2.5 leading-none transition-opacity hover:opacity-80"
            >
                <BrandMark variant="mark" class="h-8 w-8 shrink-0 sm:h-9 sm:w-9" />
                <span class="truncate font-display text-[1.3rem] font-extrabold leading-none tracking-tight text-ink sm:text-[1.4rem]">
                    Kraftrack
                </span>
            </Link>

            <nav class="flex h-full items-center gap-0.5 sm:gap-1" aria-label="Primary">
                <Link
                    :href="route('how-it-works')"
                    class="hidden items-center rounded-xl px-3 py-2 text-[0.9rem] font-semibold leading-none tracking-tight transition-colors sm:inline-flex"
                    :class="navLinkClass('how-it-works')"
                >
                    How it works
                </Link>
                <Link
                    :href="route('faq')"
                    class="hidden items-center rounded-xl px-3 py-2 text-[0.9rem] font-semibold leading-none tracking-tight transition-colors sm:inline-flex"
                    :class="navLinkClass('faq')"
                >
                    FAQ
                </Link>
                <Link
                    v-if="canLogin && !authUser"
                    :href="route('login')"
                    class="inline-flex items-center rounded-xl px-3 py-2 text-[0.9rem] font-semibold leading-none tracking-tight text-ink/55 transition-colors hover:bg-ink/[0.04] hover:text-ink"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister && !authUser"
                    :href="route('register')"
                    class="ms-0.5 inline-flex min-h-10 items-center justify-center rounded-xl bg-coral px-3.5 text-[0.9rem] font-bold leading-none text-white transition-colors hover:bg-coral-deep sm:ms-1 sm:rounded-2xl sm:px-4"
                >
                    Get started
                </Link>
                <Link
                    v-else-if="authUser"
                    :href="route('dashboard')"
                    class="ms-0.5 inline-flex min-h-10 items-center justify-center rounded-xl bg-coral px-3.5 text-[0.9rem] font-bold leading-none text-white transition-colors hover:bg-coral-deep sm:ms-1 sm:rounded-2xl sm:px-4"
                >
                    Dashboard
                </Link>
            </nav>
        </div>
    </header>
</template>

<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const current = computed(() => {
    try {
        return route().current();
    } catch {
        return null;
    }
});

const navLinkClass = (name) => {
    const active = current.value === name;
    return active
        ? 'bg-ink/[0.06] text-ink'
        : 'text-ink/55 hover:bg-ink/[0.04] hover:text-ink';
};
</script>
