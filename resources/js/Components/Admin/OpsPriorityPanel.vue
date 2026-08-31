<template>
    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <OpsSectionLabel :label="heading" />
                <p v-if="openCount > 0" class="mt-1 text-[13px] font-medium text-ink/45">
                    {{ openCount }} {{ openCount === 1 ? 'item needs' : 'items need' }} your attention
                </p>
            </div>
            <span
                v-if="openCount > 0"
                class="inline-flex w-fit items-center rounded-full bg-base-action/10 px-3 py-1 text-[12px] font-bold text-base-action"
            >
                Priority
            </span>
        </div>

        <OpsFeedSkeleton v-if="loading" />

        <div
            v-else-if="!groups.length"
            class="overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-white via-white to-[#F4F6FA] px-5 py-8 shadow-premium ring-1 ring-ink/[0.05] sm:px-6"
        >
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i class="ti ti-checks text-xl" aria-hidden="true" />
                </span>
                <div class="min-w-0 pt-0.5">
                    <p class="text-[15px] font-bold tracking-tight text-ink">You’re clear for now</p>
                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">
                        Patrol flags, customer chats, and team messages will surface here first.
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="space-y-4">
            <article
                v-for="group in groups"
                :key="group.key"
                class="overflow-hidden rounded-[1.35rem] bg-white shadow-premium ring-1 ring-ink/[0.05]"
            >
                <header class="flex items-center justify-between gap-3 border-b border-ink/[0.06] bg-[#FAFBFD] px-4 py-3.5 sm:px-5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tint text-base-action">
                            <i :class="group.icon" class="text-base" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[14px] font-bold tracking-tight text-ink">{{ group.label }}</p>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink/35">
                                {{ group.count }} open
                            </p>
                        </div>
                    </div>
                </header>

                <ul>
                    <li
                        v-for="(item, index) in group.items"
                        :key="item.key"
                        :class="index ? 'border-t border-ink/[0.06]' : ''"
                    >
                        <button
                            type="button"
                            class="group flex min-h-[4.25rem] w-full items-center gap-3 px-4 py-3.5 text-left transition-colors duration-150 hover:bg-pale active:bg-tint sm:gap-4 sm:px-5"
                            @click="open(item)"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pale text-ink/40">
                                <i :class="item.icon || group.icon" class="text-lg" aria-hidden="true" />
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
                            <i class="ti ti-chevron-right shrink-0 text-sm text-ink/20 group-hover:text-ink/40" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </article>
        </div>
    </section>
</template>

<script setup>
import OpsFeedSkeleton from '@/Components/Admin/OpsFeedSkeleton.vue';
import OpsSectionLabel from '@/Components/Admin/OpsSectionLabel.vue';
import { useOpsAttention } from '@/Composables/useOpsAttention';
import { priorityPillClass } from '@/utils/opsStatus';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    openCount: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
    heading: { type: String, default: 'Needs your attention' },
    acknowledgeEscalations: { type: Boolean, default: false },
});

const page = usePage();

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

const markEscalationRead = (item) => {
    const inbox = page.props.admin_inbox;
    if (!inbox?.priority_groups || !item?.referral_id) {
        return;
    }

    inbox.priority_groups = inbox.priority_groups.map((group) => ({
        ...group,
        items: (group.items || []).map((entry) =>
            entry.referral_id === item.referral_id ? { ...entry, unread: false } : entry,
        ),
    }));

    if (item.unread) {
        inbox.unread_count = Math.max(0, Number(inbox.unread_count || 0) - 1);
    }
};

const open = async (item) => {
    if (!item?.href) {
        return;
    }

    if (props.acknowledgeEscalations && item.referral_id) {
        markEscalationRead(item);
        try {
            await axios.post(route('admin.escalations.acknowledge', item.referral_id));
        } catch {
            // Navigation still proceeds; state reconciles on reload.
        }
    } else {
        await markRead(item);
    }

    router.visit(item.href);
};
</script>
