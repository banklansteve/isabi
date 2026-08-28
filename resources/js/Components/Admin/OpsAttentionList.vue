<template>
    <section class="space-y-4">
        <OpsSectionLabel :label="heading" />

        <OpsFeedSkeleton v-if="loading" />

        <div
            v-else-if="!items.length"
            class="flex items-start gap-4 rounded-2xl bg-white px-5 py-6 shadow-premium ring-1 ring-ink/[0.05] sm:px-6"
        >
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-pale text-ink/35">
                <i class="ti ti-checks text-xl" aria-hidden="true" />
            </span>
            <div class="min-w-0 pt-0.5">
                <p class="text-[15px] font-bold tracking-tight text-ink">You’re clear for now</p>
                <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">
                    New work from your roles will land here first.
                </p>
            </div>
        </div>

        <ul
            v-else
            class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
        >
            <li
                v-for="(item, index) in items"
                :key="item.key"
                :class="index ? 'border-t border-ink/[0.06]' : ''"
            >
                <button
                    type="button"
                    class="group flex min-h-[4.5rem] w-full items-center gap-3 px-4 py-3.5 text-left transition-colors duration-150 hover:bg-pale active:bg-tint sm:gap-4 sm:px-5"
                    @click="open(item)"
                >
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-pale text-ink/40"
                    >
                        <i :class="item.icon || 'ti ti-circle'" class="text-lg" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start gap-2">
                            <span class="block text-[14px] font-bold leading-snug tracking-tight text-ink sm:text-[15px]">
                                {{ item.title }}
                            </span>
                            <span
                                v-if="item.unread"
                                class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-base-action"
                            />
                        </span>
                        <span class="mt-0.5 block truncate text-[12px] font-medium text-ink/40 sm:text-[13px]">
                            {{ item.subtitle }}
                        </span>
                    </span>
                    <span
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold"
                        :class="priorityPillClass(item.priority || item.tone)"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" />
                        {{ priorityLabel(item.priority || item.tone) }}
                    </span>
                </button>
            </li>
        </ul>
    </section>
</template>

<script setup>
import OpsFeedSkeleton from '@/Components/Admin/OpsFeedSkeleton.vue';
import OpsSectionLabel from '@/Components/Admin/OpsSectionLabel.vue';
import { useOpsAttention } from '@/Composables/useOpsAttention';
import { priorityPillClass } from '@/utils/opsStatus';
import { router } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, default: () => [] },
    openCount: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
    heading: { type: String, default: 'Your tasks today' },
});

const priorityLabel = (priority) => {
    const map = {
        high: 'High',
        medium: 'Medium',
        low: 'Low',
        support: 'Medium',
    };

    return map[priority] || 'Medium';
};

const { markRead } = useOpsAttention();

const open = async (item) => {
    if (!item?.href) {
        return;
    }

    await markRead(item);
    router.visit(item.href);
};
</script>
