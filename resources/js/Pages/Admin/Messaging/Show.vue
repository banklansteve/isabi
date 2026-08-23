<template>
    <Head :title="announcement.title" />

    <AdminChrome title="Message" :eyebrow="announcement.audience === 'staff' ? 'Staff' : 'Customers'" />
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-lg font-bold tracking-tight text-ink">{{ announcement.title }}</h1>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                        :class="statusClass(announcement.status)"
                    >
                        {{ announcement.status }}
                    </span>
                </div>
                <p class="mt-1 text-[13px] font-medium text-ink/45">
                    {{ announcement.segment_label }}
                    <span class="text-ink/25">·</span>
                    {{ channelLabel(announcement.channels) }}
                    <span v-if="announcement.creator">
                        <span class="text-ink/25">·</span>
                        {{ announcement.creator }}
                    </span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-if="announcement.editable && announcement.status !== 'scheduled'"
                    :href="route('admin.messaging.send', announcement.id)"
                    method="post"
                    as="button"
                    class="rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white"
                >
                    Send now
                </Link>
                <Link
                    v-if="announcement.status === 'scheduled'"
                    :href="route('admin.messaging.cancel', announcement.id)"
                    method="post"
                    as="button"
                    class="rounded-xl bg-pale px-4 py-2.5 text-sm font-semibold text-ink"
                >
                    Cancel schedule
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <AdminKpiCard label="Recipients" :value="announcement.recipient_count" />
            <AdminKpiCard label="Sent" :value="announcement.sent_count" />
            <AdminKpiCard label="Read" :value="announcement.read_count" />
            <AdminKpiCard label="Failed" :value="announcement.failed_count" />
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
                <h2 class="text-[15px] font-bold text-ink">{{ announcement.subject }}</h2>
                <p class="mt-3 whitespace-pre-wrap text-[14px] font-medium leading-relaxed text-ink/70">
                    {{ announcement.body }}
                </p>
                <p v-if="announcement.send_at" class="mt-4 text-[12px] font-semibold text-ink/40">
                    Scheduled {{ announcement.send_at }}
                </p>
                <p v-else-if="announcement.sent_at" class="mt-4 text-[12px] font-semibold text-ink/40">
                    Sent {{ announcement.sent_at }}
                </p>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
                <h2 class="text-[15px] font-bold text-ink">By channel</h2>
                <ul v-if="channelRows.length" class="mt-3 divide-y divide-ink/[0.06]">
                    <li v-for="row in channelRows" :key="row.channel" class="flex items-center justify-between py-3 text-[13px]">
                        <span class="font-semibold text-ink">{{ row.label }}</span>
                        <span class="tabular-nums font-medium text-ink/50">
                            {{ row.sent }} sent · {{ row.read }} read · {{ row.failed }} failed
                        </span>
                    </li>
                </ul>
                <p v-else class="mt-3 text-[13px] font-medium text-ink/40">No deliveries yet.</p>
            </section>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <div class="border-b border-ink/[0.06] px-5 py-4">
                <h2 class="text-[15px] font-bold text-ink">Deliveries</h2>
            </div>
            <AdminEmpty
                v-if="!deliveries.data.length"
                title="Nothing queued yet"
                description="Send or wait for the schedule — then you’ll see sent, read, and failed here."
                icon="ti ti-send"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li
                    v-for="row in deliveries.data"
                    :key="row.id"
                    class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-5"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-ink">{{ row.user?.name || 'Unknown' }}</p>
                        <p class="truncate text-[12px] font-medium text-ink/40">
                            {{ row.user?.email }} · {{ channelName(row.channel) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[12px] font-bold uppercase tracking-wide" :class="deliveryTone(row.status)">
                            {{ row.status }}
                        </p>
                        <p class="text-[12px] font-medium text-ink/35">
                            {{ row.error || row.read_at || row.sent_at || '—' }}
                        </p>
                    </div>
                </li>
            </ul>
        </div>
        <AdminPagination :links="deliveries.links" />
</template>

<script setup>
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminKpiCard from '@/Components/Admin/AdminKpiCard.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    announcement: { type: Object, required: true },
    deliveries: { type: Object, required: true },
});

const names = { in_app: 'In-app', email: 'Email', whatsapp: 'WhatsApp' };

const channelName = (channel) => names[channel] || channel;
const channelLabel = (channels) => (channels || []).map(channelName).join(' · ');

const channelRows = computed(() =>
    Object.entries(props.announcement.channel_stats || {}).map(([channel, stats]) => ({
        channel,
        label: channelName(channel),
        sent: (stats.sent || 0) + (stats.read || 0),
        read: stats.read || 0,
        failed: stats.failed || 0,
    })),
);

const statusClass = (status) => {
    if (status === 'sent') return 'bg-emerald-50 text-emerald-700';
    if (status === 'scheduled') return 'bg-sky-50 text-sky-700';
    if (status === 'sending') return 'bg-amber-50 text-amber-700';
    if (status === 'cancelled') return 'bg-pale text-ink/40';
    return 'bg-pale text-ink/50';
};

const deliveryTone = (status) => {
    if (status === 'read') return 'text-emerald-700';
    if (status === 'sent') return 'text-sky-700';
    if (status === 'failed') return 'text-red-500';
    return 'text-ink/40';
};
</script>
