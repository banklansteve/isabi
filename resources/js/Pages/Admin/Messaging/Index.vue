<template>
    <Head title="Announcements" />

    <AdminChrome title="Announcements" :eyebrow="eyebrow" />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="max-w-xl text-[13px] font-medium leading-relaxed text-ink/50">
                {{
                    audience === 'staff'
                        ? 'Internal notes for ops — in-app and email only.'
                        : 'Lifecycle messages for artisans. Segment by plan, trade, or city, then track what actually landed.'
                }}
            </p>
            <Link
                :href="route('admin.messaging.create', { audience: audience || 'users' })"
                class="inline-flex items-center justify-center rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-base-hover active:scale-[0.98]"
            >
                Compose
            </Link>
        </div>

        <div class="mb-4 flex flex-col gap-3">
            <AdminRangePicker :range="range" />
            <input
                v-model="list.q.value"
                type="search"
                placeholder="Search titles…"
                class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-md"
            />
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                title="No messages yet"
                :description="
                    audience === 'staff'
                        ? 'Compose a briefing for the operations team.'
                        : 'Compose a welcome, renewal reminder, or a one-off announcement.'
                "
                icon="ti ti-megaphone"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="item in list.pageItems.value" :key="item.id">
                    <Link
                        :href="route('admin.messaging.show', item.id)"
                        prefetch
                        class="flex items-start gap-3 px-4 py-3.5 transition-colors hover:bg-pale/80 active:bg-tint sm:px-5"
                    >
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-deep"
                        >
                            <i class="ti ti-megaphone text-lg" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-bold text-ink">{{ item.title }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="statusClass(item.status)"
                                >
                                    {{ item.status }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-[13px] font-medium text-ink/45">
                                {{ item.segment_label }}
                                <span class="text-ink/25">·</span>
                                {{ channelLabel(item.channels) }}
                            </p>
                        </div>
                        <div class="hidden shrink-0 text-right text-[12px] font-semibold text-ink/40 sm:block">
                            <p>{{ item.sent_count }} sent · {{ item.read_count }} read</p>
                            <p>{{ item.send_at || item.created_at }}</p>
                        </div>
                    </Link>
                </li>
            </ul>
        </div>
        <AdminClientPager
            :page="list.page.value"
            :pages="list.pageCount.value"
            :total="list.total.value"
            :per-page="list.perPage"
            @update:page="list.page.value = $event"
        />
</template>

<script setup>
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    messages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { audience } = useAdminTabs({ audience: props.filters.audience || 'users' });
const range = useDateRange('all');

const eyebrow = computed(() =>
    audience.value === 'staff' ? 'Staff announcements' : 'Customer announcements',
);

const list = useClientList(
    () => props.messages.filter((item) => {
        if (item.audience !== (audience.value || 'users')) return false;
        return range.matches(item.created_iso);
    }),
    {
        perPage: 20,
        searchFields: ['title', 'segment_label'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([audience, () => range.preset.value], () => {
    list.page.value = 1;
});

const channelLabel = (channels) =>
    (channels || [])
        .map((channel) =>
            ({ in_app: 'In-app', email: 'Email', whatsapp: 'WhatsApp' })[channel] || channel,
        )
        .join(' · ');

const statusClass = (status) => {
    if (status === 'sent') return 'bg-emerald-50 text-emerald-700';
    if (status === 'scheduled') return 'bg-sky-50 text-sky-700';
    if (status === 'sending') return 'bg-amber-50 text-amber-700';
    if (status === 'cancelled') return 'bg-pale text-ink/40';
    return 'bg-pale text-ink/50';
};
</script>
