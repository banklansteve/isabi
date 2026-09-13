<template>
    <div
        v-if="pages.length > 1 || tabs.length > 1"
        class="sticky top-16 z-20 border-b border-ink/[0.06] bg-[#F4F6FA]/80 backdrop-blur-xl lg:top-[4.25rem]"
    >
        <div class="mx-auto w-full max-w-[1400px] space-y-2 px-4 py-2.5 sm:px-6 lg:px-8">
            <!-- Hub pages: one segmented control -->
            <nav
                v-if="pages.length > 1"
                class="overflow-hidden rounded-2xl bg-white p-1 shadow-premium ring-1 ring-ink/[0.05]"
                aria-label="Pages"
            >
                <div class="no-scrollbar flex gap-0.5 overflow-x-auto">
                    <button
                        v-for="item in pages"
                        :key="item.key"
                        type="button"
                        class="relative flex min-h-10 min-w-0 shrink-0 items-center justify-center gap-1.5 rounded-xl px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.98] sm:px-4"
                        :class="
                            item.key === activePageKey
                                ? 'bg-base-action text-white shadow-[0_8px_18px_-10px_rgba(26,79,181,0.55)]'
                                : 'text-ink/45 hover:bg-[#F4F6FA] hover:text-ink'
                        "
                        :aria-current="item.key === activePageKey ? 'page' : undefined"
                        @click="$emit('select-page', item)"
                    >
                        <i
                            v-if="item.icon"
                            :class="item.icon"
                            class="hidden text-[1.05rem] sm:inline"
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ item.shortLabel || item.label }}</span>
                    </button>
                </div>
            </nav>

            <!-- Sub-views: quiet chips — only when the page itself has views -->
            <nav
                v-if="tabs.length > 1"
                class="no-scrollbar -mx-1 flex gap-1 overflow-x-auto px-1"
                aria-label="Views"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.label"
                    type="button"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-[12px] font-semibold transition-colors duration-150"
                    :class="
                        tabIsActive(tab, currentRoute, tabQuery)
                            ? 'bg-white text-deep shadow-sm ring-1 ring-ink/[0.06]'
                            : 'text-ink/40 hover:bg-white/70 hover:text-ink'
                    "
                    :aria-current="tabIsActive(tab, currentRoute, tabQuery) ? 'page' : undefined"
                    @click="$emit('select-tab', tab)"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { tabIsActive } from '@/Data/adminNav';

defineProps({
    pages: { type: Array, default: () => [] },
    activePageKey: { type: String, default: '' },
    tabs: { type: Array, default: () => [] },
    currentRoute: { type: String, default: '' },
    tabQuery: { type: Object, default: () => ({}) },
});

defineEmits(['select-page', 'select-tab']);
</script>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
