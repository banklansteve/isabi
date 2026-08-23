<template>
    <header
        class="sticky top-0 z-40 border-b border-ink/10 bg-white/90 shadow-nav backdrop-blur-xl"
    >
        <div
            class="mx-auto flex h-[4.25rem] max-w-5xl items-center justify-between gap-4 px-5 sm:px-8"
            style="padding-top: max(0.75rem, env(safe-area-inset-top))"
        >
            <Link
                :href="route('home')"
                class="shrink-0 font-display text-[1.45rem] font-extrabold tracking-tight text-ink transition-opacity hover:opacity-80"
            >
                Isabi
            </Link>
            <nav class="flex items-center gap-1 sm:gap-1.5">
                <Link
                    :href="route('how-it-works')"
                    class="tap-target hidden items-center rounded-xl px-3.5 py-2.5 text-[0.95rem] font-bold tracking-tight transition-colors sm:inline-flex"
                    :class="navLinkClass('how-it-works')"
                >
                    How it works
                </Link>
                <Link
                    :href="route('faq')"
                    class="tap-target hidden items-center rounded-xl px-3.5 py-2.5 text-[0.95rem] font-bold tracking-tight transition-colors sm:inline-flex"
                    :class="navLinkClass('faq')"
                >
                    FAQ
                </Link>
                <Link
                    v-if="canLogin && !authUser"
                    :href="route('login')"
                    class="tap-target inline-flex items-center rounded-xl px-3.5 py-2.5 text-[0.95rem] font-bold tracking-tight text-ink/70 transition-colors hover:bg-pale hover:text-ink"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister && !authUser"
                    :href="route('register')"
                    class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-4 py-2.5 text-[0.95rem] font-bold text-white transition-colors hover:bg-coral-deep"
                >
                    Get started
                </Link>
                <Link
                    v-else-if="authUser"
                    :href="route('dashboard')"
                    class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-4 py-2.5 text-[0.95rem] font-bold text-white transition-colors hover:bg-coral-deep"
                >
                    Dashboard
                </Link>
            </nav>
        </div>
    </header>
</template>

<script setup>
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
        ? 'bg-tint text-deep'
        : 'text-ink/70 hover:bg-pale hover:text-ink';
};
</script>
