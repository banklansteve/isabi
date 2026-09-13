<template>
    <div
        v-if="showFab"
        class="pointer-events-none fixed z-[70] flex flex-col items-end gap-3"
        :class="fabPositionClass"
    >
        <Transition
            enter-active-class="transition duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
            enter-from-class="opacity-0 translate-y-3 scale-[0.96]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-220 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-[0.97]"
        >
            <div
                v-if="panelOpen"
                class="pointer-events-auto w-[min(22.5rem,calc(100vw-2rem))] overflow-hidden rounded-[1.35rem] bg-white shadow-[0_24px_60px_-18px_rgba(7,20,39,0.35)] ring-1 ring-ink/[0.08]"
                role="dialog"
                aria-label="Help and support"
            >
                <div class="border-b border-ink/[0.06] px-5 pb-4 pt-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-editorial text-xl font-semibold tracking-tight text-ink">
                                How can we help?
                            </h2>
                            <p class="mt-1 text-sm font-medium text-ink/50">
                                {{
                                    isAuthenticated
                                        ? 'Browse popular answers or chat with the Kraftrack team.'
                                        : 'Browse popular answers — or create a free page to get started.'
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="tap-target -me-1 -mt-1 flex h-9 w-9 items-center justify-center rounded-xl text-ink/35 transition-colors hover:bg-pale hover:text-ink"
                            aria-label="Close help"
                            @click="panelOpen = false"
                        >
                            <i class="ti ti-x text-lg" aria-hidden="true" />
                        </button>
                    </div>

                    <label class="relative mt-4 block">
                        <i
                            class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35"
                            aria-hidden="true"
                        />
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Search help articles"
                            class="w-full rounded-xl border-0 bg-pale py-3 ps-10 pe-3 text-sm font-medium text-ink outline-none ring-1 ring-ink/[0.06] placeholder:text-ink/35 focus:ring-2 focus:ring-base/20"
                        />
                    </label>
                </div>

                <div class="px-5 py-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                        Popular topics
                    </p>
                    <ul class="mt-2 divide-y divide-ink/[0.05]">
                        <li
                            v-for="topic in filteredTopics"
                            :key="topic.id"
                        >
                            <Link
                                :href="topic.href"
                                class="tap-target flex items-center justify-between gap-3 py-3 text-sm font-semibold text-ink transition-colors hover:text-base-action"
                                @click="panelOpen = false"
                            >
                                <span>{{ topic.label }}</span>
                                <i class="ti ti-chevron-right text-ink/30" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                    <p
                        v-if="!filteredTopics.length"
                        class="py-4 text-sm font-medium text-ink/45"
                    >
                        No matching topics. Try the full FAQ instead.
                    </p>
                    <Link
                        :href="isAuthenticated ? route('help.index') : route('faq')"
                        class="tap-target mt-1 inline-flex items-center gap-1 text-xs font-bold text-deep transition-colors hover:text-base-action"
                        @click="panelOpen = false"
                    >
                        Browse all answers
                        <i class="ti ti-arrow-right" aria-hidden="true" />
                    </Link>
                </div>

                <div class="px-4 pb-4">
                    <Link
                        v-if="isAuthenticated"
                        :href="route('help.chat')"
                        class="tap-target flex w-full items-center gap-3 rounded-2xl bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-4 py-3.5 text-left text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.55)] transition-opacity hover:opacity-95"
                        @click="panelOpen = false"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"
                        >
                            <i class="ti ti-message-circle-2 text-lg" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold">Chat with us</span>
                            <span class="mt-0.5 block text-xs font-medium text-white/65">
                                Usually replies within a few hours
                            </span>
                        </span>
                        <i class="ti ti-arrow-right text-white/60" aria-hidden="true" />
                    </Link>
                    <Link
                        v-else
                        :href="route('register')"
                        class="tap-target flex w-full items-center gap-3 rounded-2xl bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-4 py-3.5 text-left text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.55)] transition-opacity hover:opacity-95"
                        @click="panelOpen = false"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"
                        >
                            <i class="ti ti-sparkles text-lg" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold">Create your free page</span>
                            <span class="mt-0.5 block text-xs font-medium text-white/65">
                                No card required to get started
                            </span>
                        </span>
                        <i class="ti ti-arrow-right text-white/60" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </Transition>

        <button
            type="button"
            class="support-fab pointer-events-auto tap-target relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] text-white shadow-[0_16px_36px_-12px_rgba(26,79,181,0.65)] ring-1 ring-white/15 transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:scale-[1.04] active:scale-[0.97]"
            :aria-expanded="panelOpen"
            :aria-label="panelOpen ? 'Close help' : 'Open help and support'"
            @click="togglePanel"
        >
            <span
                class="pointer-events-none absolute inset-0 rounded-full bg-[radial-gradient(70%_70%_at_30%_20%,rgba(255,255,255,0.22),transparent_55%)]"
                aria-hidden="true"
            />
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-75 rotate-45"
                enter-to-class="opacity-100 scale-100 rotate-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-75 -rotate-45"
            >
                <i
                    :key="panelOpen ? 'close' : 'help'"
                    :class="panelOpen ? 'ti ti-x' : 'ti ti-help-hexagon'"
                    class="relative text-2xl"
                    aria-hidden="true"
                />
            </Transition>
        </button>
    </div>
</template>

<script setup>
import {
    popularTopics,
    searchHelpTopics,
    topicHref,
} from '@/Data/helpTopics';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const panelOpen = ref(false);
const query = ref('');

const isAuthenticated = computed(() => !!page.props.auth?.user);

const showFab = computed(() => {
    try {
        const current = route().current();
        if (!current) return true;
        if (current === 'help.chat') return false;
        if (String(current).startsWith('admin.')) return false;
        // Keep auth forms uncluttered
        if (
            current === 'login' ||
            current === 'register' ||
            String(current).startsWith('password.')
        ) {
            return false;
        }
        return true;
    } catch {
        return true;
    }
});

const fabPositionClass = computed(() => {
    if (isAuthenticated.value) {
        return 'bottom-[max(5.25rem,calc(env(safe-area-inset-bottom)+4.5rem))] right-4 md:bottom-6 md:right-6';
    }
    return 'bottom-[max(1.25rem,calc(env(safe-area-inset-bottom)+1rem))] right-4 md:bottom-6 md:right-6';
});

const context = computed(() => (isAuthenticated.value ? 'auth' : 'guest'));

const defaultTopics = computed(() =>
    popularTopics(context.value, 5).map((item) => ({
        id: item.id,
        label: item.question,
        href: topicHref(item, isAuthenticated.value),
        keywords: item.keywords ?? '',
    })),
);

const filteredTopics = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return defaultTopics.value;
    return searchHelpTopics(q).map((item) => ({
        id: item.id,
        label: item.question,
        href: topicHref(item, isAuthenticated.value),
        keywords: item.keywords ?? '',
    }));
});

const togglePanel = () => {
    panelOpen.value = !panelOpen.value;
    if (panelOpen.value) {
        try {
            localStorage.setItem('kraftrack:support-fab-opened', '1');
        } catch {
            // ignore
        }
    }
};

watch(
    () => {
        try {
            return route().current();
        } catch {
            return null;
        }
    },
    () => {
        panelOpen.value = false;
        query.value = '';
    },
);
</script>

<style scoped>
.support-fab {
    animation: fab-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes fab-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .support-fab {
        animation: none;
    }
}
</style>
