<template>
    <Head title="Home" />

    <AdminChrome title="Home" />

    <div
        v-if="restricted"
        class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-20 text-center shadow-premium ring-1 ring-ink/[0.05]"
    >
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-ink/30">
            <i class="ti ti-lock-access text-2xl" aria-hidden="true" />
        </span>
        <p class="mt-4 text-sm font-bold text-ink">No roles assigned yet</p>
        <p class="mt-1 max-w-md text-[13px] font-medium leading-relaxed text-ink/45">
            You can sign in, but a Super Admin still needs to give you a role before you can work in this console.
        </p>
    </div>

    <div v-else class="space-y-6 lg:space-y-8">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
            <div class="min-w-0">
                <h1 class="font-editorial text-[1.85rem] font-semibold leading-[1.12] tracking-tight text-ink sm:text-[2.25rem]">
                    {{ greeting }}, {{ givenName }}.
                </h1>
                <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-ink/35">
                    {{ clockLabel }}
                    <span v-if="role_summary"> · {{ role_summary }}</span>
                </p>
            </div>
            <span
                v-if="duty?.badge"
                class="inline-flex w-fit shrink-0 items-center rounded-full bg-emerald-50 px-3 py-1.5 text-[12px] font-semibold text-emerald-800"
            >
                {{ duty.badge }}
            </span>
        </header>

        <OpsEscalateBanner :escalate="escalate" />

        <OpsQueueGrid :queues="queueShortcuts" />

        <OpsPriorityPanel :groups="priorityGroups" :open-count="attentionOpenCount" />

        <OpsEscalationsCard v-if="escalations.length" :items="escalations" />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import OpsPriorityPanel from '@/Components/Admin/OpsPriorityPanel.vue';
import OpsEscalateBanner from '@/Components/Admin/OpsEscalateBanner.vue';
import OpsEscalationsCard from '@/Components/Admin/OpsEscalationsCard.vue';
import OpsQueueGrid from '@/Components/Admin/OpsQueueGrid.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    greeting: { type: String, default: '' },
    given_name: { type: String, default: '' },
    timezone: { type: String, default: 'Africa/Lagos' },
    roles: { type: Array, default: () => [] },
    role_summary: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    priority_groups: { type: Array, default: () => [] },
    shortcuts: { type: Array, default: () => [] },
    duty: { type: Object, default: null },
    escalations: { type: Array, default: () => [] },
    escalate: { type: Object, default: null },
    open_count: { type: Number, default: 0 },
    unread_count: { type: Number, default: 0 },
    unread_items: { type: Array, default: () => [] },
    restricted: { type: Boolean, default: false },
});

const page = usePage();
const clockLabel = ref('');
let timer = null;

const opsInbox = computed(() => page.props.ops_inbox || {});

const priorityGroups = computed(() => {
    const groups = opsInbox.value.priority_groups;

    return Array.isArray(groups) ? groups : props.priority_groups;
});

const attentionOpenCount = computed(() =>
    typeof opsInbox.value.open_count === 'number' ? opsInbox.value.open_count : props.open_count,
);

const queueShortcuts = computed(() => {
    const live = opsInbox.value.shortcuts;

    return Array.isArray(live) && live.length ? live : props.shortcuts;
});

const givenName = computed(() => {
    if (props.given_name) {
        return props.given_name;
    }

    const user = page.props.auth?.user;

    return user?.first_name || String(user?.name || 'there').split(' ')[0];
});

const tick = () => {
    try {
        const parts = Object.fromEntries(
            new Intl.DateTimeFormat('en-GB', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZone: props.timezone || 'Africa/Lagos',
            }).formatToParts(new Date()).map((part) => [part.type, part.value]),
        );

        clockLabel.value = `${parts.weekday} ${parts.day} ${parts.month} ${parts.year} · ${parts.hour}:${parts.minute}`.toUpperCase();
    } catch {
        clockLabel.value = '';
    }
};

onMounted(() => {
    tick();
    timer = window.setInterval(tick, 30000);
});

onUnmounted(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>
