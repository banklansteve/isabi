<template>
    <section v-if="queues.length" class="space-y-3">
        <OpsSectionLabel label="Your queues" />

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
            <Link
                v-for="item in queues"
                :key="item.key"
                :href="item.href"
                prefetch
                cache-for="5m"
                :show-progress="false"
                class="rounded-xl bg-white px-2.5 py-2.5 shadow-premium ring-1 ring-ink/[0.05] transition-colors hover:bg-pale sm:px-3"
            >
                <span class="block truncate text-[11px] font-medium text-ink/50">{{ item.label }}</span>
                <span class="mt-1 flex items-end gap-1">
                    <span class="font-editorial text-[1.45rem] leading-none tracking-tight text-ink">
                        {{ padQueueCount(item.count) }}
                    </span>
                    <span class="mb-px truncate text-[10px] font-medium text-ink/35">
                        {{ item.count ? item.hint : 'clear' }}
                    </span>
                </span>
            </Link>
        </div>
    </section>
</template>

<script setup>
import OpsSectionLabel from '@/Components/Admin/OpsSectionLabel.vue';
import { padQueueCount } from '@/utils/opsStatus';
import { Link } from '@inertiajs/vue3';

defineProps({
    queues: { type: Array, default: () => [] },
});
</script>
