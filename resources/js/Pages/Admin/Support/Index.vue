<template>
    <Head title="Support" />

    <AdminChrome title="Support" eyebrow="Artisan inbox" />
        <div class="mb-4 flex flex-col gap-3">
            <AdminRangePicker :range="range" />
            <input
                v-model="list.q.value"
                type="search"
                placeholder="Search tickets or artisans…"
                class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-md"
            />
        </div>
        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                :title="status === 'resolved' ? 'No resolved tickets' : 'Inbox is clear'"
                description="Help chats from artisans land here as they come in."
                icon="ti ti-headset"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="ticket in list.pageItems.value" :key="ticket.id">
                    <Link
                        :href="route('admin.support.show', ticket.id)"
                        prefetch
                        class="flex items-center gap-3 px-4 py-3.5 transition-colors hover:bg-pale/80 active:bg-tint sm:px-5"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-xs font-bold text-deep"
                        >
                            <img
                                v-if="ticket.user?.avatar_url"
                                :src="ticket.user.avatar_url"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ initials(ticket.user?.name) }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-bold text-ink">{{ ticket.subject }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="ticket.status === 'resolved' ? 'bg-pale text-ink/40' : 'bg-emerald-50 text-emerald-700'"
                                >
                                    {{ ticket.status }}
                                </span>
                            </div>
                            <p class="truncate text-[13px] font-medium text-ink/45">
                                {{ ticket.user?.name || 'Unknown' }} · {{ ticket.messages_count }} messages
                            </p>
                        </div>
                        <p class="hidden shrink-0 text-[12px] font-semibold text-ink/35 sm:block">
                            {{ ticket.last_reply_at }}
                        </p>
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
import { watch } from 'vue';

const props = defineProps({
    tickets: { type: Array, default: () => [] },
});

const { status } = useAdminTabs({ status: 'open' });
const range = useDateRange('all');

const list = useClientList(
    () => props.tickets.filter((ticket) => {
        if (status.value === 'resolved' && ticket.status !== 'resolved') return false;
        if (status.value !== 'resolved' && ticket.status === 'resolved') return false;
        return range.matches(ticket.created_iso);
    }),
    {
        perPage: 24,
        searchFields: ['subject', 'user.name', 'user.email'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([status, () => range.preset.value], () => {
    list.page.value = 1;
});

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
</script>
