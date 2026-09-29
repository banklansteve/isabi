<template>
    <nav class="mb-4 space-y-2" aria-label="Support inbox">
        <div class="flex flex-wrap items-center gap-2">
            <div class="no-scrollbar flex min-w-0 flex-1 gap-1 overflow-x-auto rounded-xl bg-white p-1 shadow-premium ring-1 ring-ink/[0.05]">
                <button
                    v-for="item in primary"
                    :key="item.key"
                    type="button"
                    class="flex min-w-[4.75rem] shrink-0 flex-1 items-center justify-center gap-1.5 rounded-lg px-2.5 py-2.5 text-[12px] font-semibold transition-colors duration-150 sm:min-w-0 sm:px-3 sm:text-[13px]"
                    :class="item.active ? 'bg-base-action text-white shadow-sm' : 'text-ink/45 hover:bg-pale hover:text-ink'"
                    :aria-current="item.active ? 'page' : undefined"
                    @click="selectStatus(item)"
                >
                    <span class="truncate">{{ item.label }}</span>
                    <span
                        v-if="item.count"
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold leading-none tabular-nums"
                        :class="item.active ? 'bg-white/20 text-white' : 'bg-pale text-ink/50'"
                    >
                        {{ item.count }}
                    </span>
                </button>
            </div>
            <Link
                v-if="templatesHref"
                :href="templatesHref"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-white px-3.5 py-2.5 text-[13px] font-semibold text-ink/55 shadow-premium ring-1 ring-ink/[0.05] transition-colors hover:bg-pale hover:text-ink"
                :class="templatesActive ? 'text-deep ring-base/20' : ''"
            >
                <i class="ti ti-message-2-code text-base" aria-hidden="true" />
                <span class="hidden sm:inline">Templates</span>
            </Link>
        </div>
        <p v-if="historyHint" class="px-1 text-[11px] font-medium text-ink/35">
            {{ historyHint }}
        </p>
    </nav>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    counts: {
        type: Object,
        default: () => ({ active: 0, referred: 0, abandoned: 0, resolved: 0 }),
    },
    historyDays: { type: Number, default: 30 },
});

const page = usePage();
const pending = ref(false);
const optimisticStatus = ref(null);

const current = computed(() => {
    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const status = computed(() => {
    if (optimisticStatus.value) {
        return optimisticStatus.value;
    }

    try {
        const value = new URL(page.url, window.location.origin).searchParams.get('status') || 'active';
        return value === 'open' ? 'active' : value;
    } catch {
        return 'active';
    }
});

watch(
    () => page.url,
    () => {
        if (!pending.value) {
            optimisticStatus.value = null;
        }
    },
);

const onInbox = computed(() => current.value === 'admin.support.index' || current.value === 'admin.support.show');
const onTemplates = computed(() => current.value === 'admin.support.templates');

const primary = computed(() => [
    {
        key: 'active',
        label: 'Active',
        href: route('admin.support.index', { status: 'active', assigned: 'me' }),
        active: onInbox.value && !['resolved', 'abandoned', 'referred'].includes(status.value),
        count: props.counts.active ?? 0,
    },
    {
        key: 'referred',
        label: 'Referred',
        href: route('admin.support.index', { status: 'referred' }),
        active: onInbox.value && status.value === 'referred',
        count: props.counts.referred ?? 0,
    },
    {
        key: 'abandoned',
        label: 'Abandoned',
        href: route('admin.support.index', { status: 'abandoned' }),
        active: onInbox.value && status.value === 'abandoned',
        count: props.counts.abandoned ?? 0,
    },
    {
        key: 'resolved',
        label: 'Closed',
        href: route('admin.support.index', { status: 'resolved' }),
        active: onInbox.value && status.value === 'resolved',
        count: props.counts.resolved ?? 0,
    },
]);

const templatesHref = computed(() => {
    try {
        return route('admin.support.templates');
    } catch {
        return '';
    }
});

const templatesActive = computed(() => onTemplates.value);

const historyHint = computed(() => {
    if (!['resolved', 'abandoned', 'referred'].includes(status.value)) {
        return '';
    }

    return `Showing the last ${props.historyDays} days.`;
});

const selectStatus = (item) => {
    if (item.active || pending.value) {
        return;
    }

    optimisticStatus.value = item.key;
    pending.value = true;

    router.get(item.href, {}, {
        only: ['tickets', 'ticket', 'filters', 'counts', 'agents', 'canned', 'topics', 'moments', 'history_days'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        showProgress: false,
        onFinish: () => {
            pending.value = false;
            optimisticStatus.value = null;
        },
    });
};
</script>
