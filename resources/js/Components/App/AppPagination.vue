<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1 pt-4"
        aria-label="Pagination"
    >
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-xl px-2.5 text-[13px] font-semibold transition-colors"
                :class="link.active ? 'bg-base-action text-white shadow-sm' : 'text-ink/50 hover:bg-tint hover:text-base-action'"
                v-html="link.label"
            />
            <span
                v-else
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-xl px-2.5 text-[13px] font-semibold text-ink/25"
                v-html="link.label"
            />
        </template>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>
