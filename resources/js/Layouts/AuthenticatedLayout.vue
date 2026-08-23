<template>
    <div class="min-h-dvh bg-pale font-app text-ink antialiased">
        <div
            v-if="impersonating"
            class="flex items-center justify-center gap-3 bg-ink px-4 py-2 text-center text-[13px] font-semibold text-white"
        >
            <span>Viewing as {{ impersonating.as }}</span>
            <Link
                :href="route('impersonation.leave')"
                method="post"
                as="button"
                class="rounded-lg bg-white/15 px-2.5 py-1 text-[12px] font-bold hover:bg-white/25"
            >
                Leave
            </Link>
        </div>
        <header
            class="sticky top-0 z-40 border-b border-ink/10 bg-white/90 backdrop-blur-xl transition-[box-shadow] duration-300"
            :class="{ 'shadow-nav': scrolled }"
            style="padding-top: env(safe-area-inset-top)"
        >
            <div class="mx-auto flex h-[4.25rem] max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-10">
                <div class="flex min-w-0 items-center gap-8 lg:gap-14 xl:gap-16">
                    <Link
                        :href="route('dashboard')"
                        class="shrink-0 text-[1.45rem] font-extrabold tracking-tight text-ink transition-opacity duration-200 hover:opacity-80"
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
                        class="tap-target hidden items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-[0.95rem] font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-[background-color,transform] duration-200 hover:bg-base-hover hover:scale-[1.01] active:scale-[0.99] lg:inline-flex"
                    >
                        <i class="ti ti-plus text-sm" aria-hidden="true" />
                        Log a job
                    </Link>

                    <NotificationBell />
                    <ProfileCompletionRing />
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
                            class="tap-target flex items-center gap-3 rounded-xl px-4 py-3 text-[0.95rem] font-bold tracking-tight transition-colors duration-200"
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

        <main
            :class="
                fullBleed
                    ? 'pb-28 sm:pb-14'
                    : 'mx-auto max-w-7xl px-4 pb-28 pt-7 sm:px-6 sm:pb-14 sm:pt-9 lg:px-10'
            "
        >
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
import ProfileCompletionRing from '@/Components/App/ProfileCompletionRing.vue';
import UserProfileMenu from '@/Components/App/UserProfileMenu.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

defineProps({
    /** Edge-to-edge content (e.g. public profile preview) — keeps app nav, drops main padding. */
    fullBleed: { type: Boolean, default: false },
});

const page = usePage();
const impersonating = computed(() => page.props.auth?.impersonating || null);
const mobileOpen = ref(false);
const scrolled = ref(false);

const primaryNav = [
    { label: 'Home', href: route('dashboard'), match: 'dashboard', icon: 'ti ti-home' },
    {
        label: 'My page',
        href: route('page.index'),
        match: ['page.*', 'public.profile'],
        icon: 'ti ti-user-circle',
    },
    { label: 'Work log', href: route('work-log.index'), match: 'work-log.*', icon: 'ti ti-notebook' },
    { label: 'Tokens', href: route('tokens.index'), match: 'tokens.*', icon: 'ti ti-coin' },
    { label: 'Referrals', href: route('referrals.index'), match: 'referrals.*', icon: 'ti ti-gift' },
];

const bottomNav = [
    { short: 'Home', href: route('dashboard'), match: 'dashboard', icon: 'ti ti-home' },
    {
        short: 'Page',
        href: route('page.index'),
        match: ['page.*', 'public.profile'],
        icon: 'ti ti-user-circle',
    },
    { short: 'Jobs', href: route('work-log.index'), match: 'work-log.*', icon: 'ti ti-notebook' },
    { short: 'Tokens', href: route('tokens.index'), match: 'tokens.*', icon: 'ti ti-coin' },
    { short: 'More', href: route('referrals.index'), match: 'referrals.*', icon: 'ti ti-gift' },
];

const isActive = (pattern) => {
    if (Array.isArray(pattern)) {
        return pattern.some((p) => route().current(p));
    }
    return route().current(pattern);
};

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
    @apply relative inline-flex items-center rounded-xl px-4 py-2.5 text-[0.95rem] font-bold tracking-tight text-ink/70 transition-[color,background-color] duration-200 ease-out lg:px-5;
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
