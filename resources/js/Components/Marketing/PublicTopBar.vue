<template>
    <header
        class="sticky top-0 z-40 border-b border-ink/[0.07] bg-white/95 shadow-[0_1px_0_rgba(7,20,39,0.04)] backdrop-blur-xl"
        style="padding-top: env(safe-area-inset-top)"
    >
        <div
            class="mx-auto flex h-14 w-full max-w-7xl items-center justify-between gap-4 px-2.5 sm:h-[3.75rem] sm:px-6 lg:px-8"
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

            <nav class="flex h-full items-center gap-1 sm:gap-2 lg:gap-3" aria-label="Primary">
                <Link
                    v-if="!authUser"
                    :href="route('public.directory')"
                    class="nav-item hidden sm:inline-flex"
                    :class="navLinkClass(['public.directory', 'public.directory.trade', 'public.directory.trade-state'])"
                >
                    Find artisans
                </Link>
                <Link
                    :href="route('how-it-works')"
                    class="nav-item hidden sm:inline-flex"
                    :class="navLinkClass('how-it-works')"
                >
                    How it works
                </Link>
                <Link
                    :href="route('faq')"
                    class="nav-item hidden sm:inline-flex"
                    :class="navLinkClass('faq')"
                >
                    FAQ
                </Link>
                <Link
                    v-if="canLogin && !authUser"
                    :href="route('login')"
                    class="nav-item"
                    :class="navLinkClass('login')"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister && !authUser"
                    :href="route('register')"
                    class="nav-cta"
                >
                    Get started
                </Link>
                <Link
                    v-else-if="authUser"
                    :href="route('dashboard')"
                    class="nav-cta"
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
    const names = Array.isArray(name) ? name : [name];
    const active = names.some((n) => current.value === n);
    return active
        ? 'bg-ink/[0.06] text-ink'
        : 'text-ink/55 hover:bg-ink/[0.04] hover:text-ink';
};
</script>

<style scoped>
.nav-item {
    @apply items-center justify-center rounded-2xl px-4 py-2.5 text-[0.9rem] font-semibold leading-none tracking-tight transition-colors sm:min-h-10 sm:px-5;
}

.nav-cta {
    @apply ms-0.5 inline-flex min-h-10 items-center justify-center rounded-2xl bg-coral px-4 text-[0.9rem] font-bold leading-none text-white transition-colors hover:bg-coral-deep sm:ms-1 sm:px-5;
}
</style>
