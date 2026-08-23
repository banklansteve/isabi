<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1 pt-2"
    >
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-[13px] font-semibold"
                :class="link.active ? 'bg-ink text-white' : 'text-ink/50 hover:bg-ink/[0.05] hover:text-ink'"
                v-html="link.label"
            />
            <span
                v-else
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-[13px] font-semibold text-ink/25"
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
