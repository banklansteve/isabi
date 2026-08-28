<template>
    <Dropdown v-if="queues.length" align="right" width="72" content-classes="p-1.5">
        <template #trigger="{ open }">
            <button
                type="button"
                class="tap-target relative flex h-10 w-10 items-center justify-center rounded-full text-ink/55 transition-colors hover:bg-pale hover:text-ink"
                :aria-expanded="open"
                aria-label="Queues"
            >
                <i class="ti ti-layout-grid text-xl" aria-hidden="true" />
                <span
                    v-if="openCount > 0"
                    class="absolute right-1 top-1 h-1.5 w-1.5 rounded-full bg-coral"
                />
            </button>
        </template>
        <template #content>
            <Link
                v-for="item in queues"
                :key="item.key"
                :href="item.href"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-pale"
            >
                <i :class="item.icon || 'ti ti-circle'" class="text-lg text-ink/40" aria-hidden="true" />
                <span class="min-w-0 flex-1 truncate text-[13px] font-semibold text-ink">{{ item.label }}</span>
                <span class="tabular-nums text-[12px] font-bold text-ink/40">
                    {{ padQueueCount(item.count) }}
                </span>
            </Link>
        </template>
    </Dropdown>
</template>

<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { padQueueCount } from '@/utils/opsStatus';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    queues: { type: Array, default: () => [] },
});

const openCount = computed(() =>
    props.queues.reduce((sum, item) => sum + Number(item.count || 0), 0),
);
</script>
