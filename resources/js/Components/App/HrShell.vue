<template>
    <div class="min-h-dvh bg-pale font-app text-ink">
        <header class="border-b border-ink/10 bg-white">
            <div class="mx-auto max-w-6xl px-5 py-4 sm:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">
                            Super Admin
                        </p>
                        <h1 class="text-xl font-semibold tracking-tight text-ink">
                            {{ title }}
                        </h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('admin.staff.index')"
                            class="tap-target inline-flex items-center gap-1.5 text-sm font-semibold text-ink/55 transition-colors hover:text-ink"
                        >
                            <i class="ti ti-shield-lock text-base" aria-hidden="true" />
                            <span class="hidden sm:inline">Access &amp; roles</span>
                        </Link>
                        <Link
                            :href="route('admin.dashboard')"
                            class="tap-target inline-flex items-center text-sm font-semibold text-ink/55 transition-colors hover:text-ink"
                        >
                            Admin home
                        </Link>
                    </div>
                </div>

                <nav class="mt-4 flex gap-1 overflow-x-auto" aria-label="HR sections">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="tap-target inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition-colors"
                        :class="isActive(item)
                            ? 'bg-tint text-deep'
                            : 'text-ink/55 hover:bg-pale hover:text-ink'"
                    >
                        <i :class="item.icon" aria-hidden="true" />
                        {{ item.label }}
                        <span
                            v-if="item.badge"
                            class="ml-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-coral px-1.5 text-[11px] font-bold text-white"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    active: { type: String, default: 'index' },
    pendingLeave: { type: Number, default: 0 },
});

const navItems = computed(() => [
    { key: 'index', route: 'admin.hr.index', label: 'Directory', icon: 'ti ti-users' },
    { key: 'leave', route: 'admin.hr.leave.index', label: 'Leave', icon: 'ti ti-calendar-stats', badge: props.pendingLeave || 0 },
    { key: 'calendar', route: 'admin.hr.calendar', label: 'Calendar', icon: 'ti ti-calendar-month' },
    { key: 'reports', route: 'admin.hr.reports.index', label: 'Reports', icon: 'ti ti-chart-bar' },
    { key: 'settings', route: 'admin.hr.settings', label: 'Settings', icon: 'ti ti-settings' },
]);

const isActive = (item) => item.key === props.active;
</script>
