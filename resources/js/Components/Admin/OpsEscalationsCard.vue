<template>
    <section class="rounded-2xl bg-white px-5 py-5 shadow-premium ring-1 ring-ink/[0.05] sm:px-6">
        <h2 class="text-[15px] font-bold tracking-tight text-ink">Referred to admin</h2>

        <p v-if="!items.length" class="mt-4 text-[13px] font-medium leading-relaxed text-ink/40">
            Nothing waiting upstairs yet.
        </p>

        <ul v-else class="mt-3 divide-y divide-ink/[0.06]">
            <li v-for="item in items" :key="`${item.label}-${item.status}`">
                <Link
                    :href="item.href"
                    class="flex items-baseline justify-between gap-3 py-3"
                >
                    <span class="min-w-0 truncate text-[13px] font-semibold text-ink">{{ item.label }}</span>
                    <span
                        class="shrink-0 font-editorial text-[15px] font-semibold tracking-tight"
                        :class="escalationStatusClass(item.tone)"
                    >
                        {{ item.status }}
                    </span>
                </Link>
            </li>
        </ul>
    </section>
</template>

<script setup>
import { escalationStatusClass } from '@/utils/opsStatus';
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, default: () => [] },
});
</script>
