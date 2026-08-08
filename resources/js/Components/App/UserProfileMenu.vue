<template>
    <Dropdown align="right" width="72" content-classes="overflow-hidden rounded-2xl p-0">
        <template #trigger="{ open }">
            <button
                type="button"
                class="tap-target group flex items-center gap-2 rounded-full py-1 pe-1.5 ps-1 outline-none transition-colors duration-200 hover:bg-pale focus-visible:ring-2 focus-visible:ring-base/30"
                :aria-expanded="open"
                aria-label="Open profile menu"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-tint text-sm font-semibold tracking-tight text-deep ring-2 ring-white shadow-sm transition-shadow duration-200 group-hover:shadow-md sm:h-10 sm:w-10"
                >
                    {{ user.initials || 'I' }}
                </span>
                <i
                    class="ti ti-chevron-down me-0.5 text-base text-ink/40 transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                    :class="{ 'rotate-180 text-ink/70': open }"
                    aria-hidden="true"
                />
            </button>
        </template>

        <template #content>
            <div class="w-72">
                <div class="flex items-start gap-3 px-4 pb-3.5 pt-4">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-tint text-sm font-semibold tracking-tight text-deep"
                    >
                        {{ user.initials || 'I' }}
                    </span>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="truncate text-sm font-semibold tracking-tight text-ink">
                            {{ user.name }}
                        </p>
                        <p class="truncate text-xs font-medium text-ink/45">
                            {{ user.email }}
                        </p>
                    </div>
                    <Link
                        :href="route('profile.edit')"
                        class="tap-target -me-1 -mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-ink/35 transition-colors duration-200 hover:bg-pale hover:text-ink"
                        aria-label="Account options"
                    >
                        <i class="ti ti-dots text-lg" aria-hidden="true" />
                    </Link>
                </div>

                <div class="mx-3 border-t border-ink/10" />

                <div class="p-2">
                    <Link
                        :href="route('page.index')"
                        class="menu-row"
                    >
                        <i class="ti ti-user-circle text-lg text-ink/45" aria-hidden="true" />
                        View my page
                    </Link>
                </div>

                <div class="mx-3 border-t border-ink/10" />

                <div class="p-2">
                    <Link
                        v-for="item in menuItems"
                        :key="item.href"
                        :href="item.href"
                        class="menu-row"
                    >
                        <i :class="[item.icon, 'text-lg text-ink/45']" aria-hidden="true" />
                        {{ item.label }}
                    </Link>
                    <button
                        type="button"
                        class="menu-row"
                        @click="openCookiePreferences"
                    >
                        <i class="ti ti-cookie text-lg text-ink/45" aria-hidden="true" />
                        Cookie preferences
                    </button>
                </div>

                <div class="mx-3 border-t border-ink/10" />

                <div class="p-2 pb-2.5">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="tap-target flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-coral-deep transition-colors duration-200 hover:bg-coral-tint/50"
                    >
                        <i class="ti ti-logout text-lg" aria-hidden="true" />
                        Log out
                    </Link>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const menuItems = [
    { label: 'Account settings', icon: 'ti ti-settings', href: route('profile.edit') },
    { label: 'Credits & plan', icon: 'ti ti-wallet', href: route('credits.index') },
    { label: 'Referrals', icon: 'ti ti-gift', href: route('referrals.index') },
    { label: 'Help & support', icon: 'ti ti-help-circle', href: route('help.index') },
];

const openCookiePreferences = () => {
    window.dispatchEvent(new CustomEvent('isabi:open-cookie-consent'));
};
</script>

<style scoped>
.menu-row {
    @apply flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink transition-colors duration-200 hover:bg-pale;
}
</style>
