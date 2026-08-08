<template>
    <div class="min-h-dvh bg-pale font-app text-ink antialiased">
        <header
            class="sticky top-0 z-40 border-b border-ink/10 bg-white/90 backdrop-blur-xl transition-[box-shadow] duration-300"
            :class="{ 'shadow-nav': scrolled }"
            style="padding-top: env(safe-area-inset-top)"
        >
            <div class="mx-auto flex h-[4.25rem] max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-10">
                <div class="flex min-w-0 items-center gap-8 lg:gap-14 xl:gap-16">
                    <Link
                        :href="route('dashboard')"
                        class="shrink-0 text-[1.35rem] font-bold tracking-tight text-ink transition-opacity duration-200 hover:opacity-80"
                    >
                        Isabi
                    </Link>

                    <nav
                        class="hidden items-center md:flex md:gap-1 lg:gap-2"
                        aria-label="Primary"
                    >
                        <Link
                            v-for="item in primaryNav"
                            :key="item.href"
                            :href="item.href"
                            class="nav-link"
                            :class="isActive(item.match) ? 'nav-link--active' : ''"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <Link
                        :href="route('work-log.create')"
                        class="tap-target hidden items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-[background-color,transform] duration-200 hover:bg-base-hover hover:scale-[1.01] active:scale-[0.99] lg:inline-flex"
                    >
                        <i class="ti ti-plus text-sm" aria-hidden="true" />
                        Log a job
                    </Link>

                    <NotificationBell />
                    <UserProfileMenu />

                    <button
                        type="button"
                        class="tap-target ms-0.5 inline-flex h-10 w-10 items-center justify-center rounded-full text-ink/55 transition-colors duration-200 hover:bg-pale hover:text-ink md:hidden"
                        :aria-expanded="mobileOpen"
                        aria-label="Toggle menu"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <i
                            :class="mobileOpen ? 'ti ti-x' : 'ti ti-menu-2'"
                            class="text-xl transition-transform duration-200"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </div>

            <Transition name="mobile-nav">
                <div
                    v-if="mobileOpen"
                    class="border-t border-ink/10 bg-white md:hidden"
                >
                    <nav class="mx-auto max-w-7xl space-y-1 px-4 py-4 sm:px-6" aria-label="Mobile">
                        <Link
                            v-for="item in primaryNav"
                            :key="`m-${item.href}`"
                            :href="item.href"
                            class="tap-target flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors duration-200"
                            :class="
                                isActive(item.match)
                                    ? 'bg-tint text-deep'
                                    : 'text-ink/70 hover:bg-pale'
                            "
                            @click="mobileOpen = false"
                        >
                            <i :class="[item.icon, 'text-lg text-ink/40']" aria-hidden="true" />
                            {{ item.label }}
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="tap-target flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-medium text-coral-deep transition-colors duration-200 hover:bg-coral-tint/40"
                            @click="mobileOpen = false"
                        >
                            <i class="ti ti-logout text-lg" aria-hidden="true" />
                            Log out
                        </Link>
                    </nav>
                </div>
            </Transition>
        </header>

        <header
            v-if="$slots.header"
            class="border-b border-ink/10 bg-white/60"
        >
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-10">
                <slot name="header" />
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 pb-28 pt-7 sm:px-6 sm:pb-14 sm:pt-9 lg:px-10">
            <slot />
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/10 bg-white/95 backdrop-blur-xl md:hidden"
            style="padding-bottom: env(safe-area-inset-bottom)"
            aria-label="Bottom navigation"
        >
            <div class="mx-auto grid max-w-lg grid-cols-5 gap-0.5 px-2 py-2">
                <Link
                    v-for="item in bottomNav"
                    :key="item.href"
                    :href="item.href"
                    class="tap-target flex flex-col items-center justify-center gap-1 rounded-xl px-1 py-1.5 transition-colors duration-200"
                    :class="isActive(item.match) ? 'bg-tint/80 text-base' : 'text-ink/40'"
                >
                    <i :class="[item.icon, 'text-xl']" aria-hidden="true" />
                    <span class="text-[10px] font-semibold tracking-wide">{{ item.short }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<script setup>
import NotificationBell from '@/Components/App/NotificationBell.vue';
import UserProfileMenu from '@/Components/App/UserProfileMenu.vue';
import { Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const mobileOpen = ref(false);
const scrolled = ref(false);

const primaryNav = [
    { label: 'Home', href: route('dashboard'), match: 'dashboard', icon: 'ti ti-home' },
    { label: 'My page', href: route('page.index'), match: 'page.*', icon: 'ti ti-user-circle' },
    { label: 'Work log', href: route('work-log.index'), match: 'work-log.*', icon: 'ti ti-notebook' },
    { label: 'Credits', href: route('credits.index'), match: 'credits.*', icon: 'ti ti-wallet' },
    { label: 'Referrals', href: route('referrals.index'), match: 'referrals.*', icon: 'ti ti-gift' },
];

const bottomNav = [
    { short: 'Home', href: route('dashboard'), match: 'dashboard', icon: 'ti ti-home' },
    { short: 'Page', href: route('page.index'), match: 'page.*', icon: 'ti ti-user-circle' },
    { short: 'Jobs', href: route('work-log.index'), match: 'work-log.*', icon: 'ti ti-notebook' },
    { short: 'Credits', href: route('credits.index'), match: 'credits.*', icon: 'ti ti-wallet' },
    { short: 'More', href: route('referrals.index'), match: 'referrals.*', icon: 'ti ti-gift' },
];

const isActive = (pattern) => route().current(pattern);

const onScroll = () => {
    scrolled.value = window.scrollY > 4;
};

watch(
    () => route().current(),
    () => {
        mobileOpen.value = false;
    },
);

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});
</script>

<style scoped>
.nav-link {
    @apply relative inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-medium text-ink/55 transition-[color,background-color] duration-200 ease-out lg:px-5;
}

.nav-link:hover {
    @apply bg-pale text-ink;
}

.nav-link--active {
    @apply bg-tint text-deep;
}

.mobile-nav-enter-active {
    transition:
        opacity 0.22s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.mobile-nav-leave-active {
    transition:
        opacity 0.16s ease,
        transform 0.16s ease;
}

.mobile-nav-enter-from,
.mobile-nav-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
