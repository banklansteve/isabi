<template>
    <nav class="mb-4 flex flex-wrap items-center gap-2" aria-label="Support inbox">
        <div class="flex min-w-0 flex-1 gap-1 rounded-xl bg-white p-1 shadow-premium ring-1 ring-ink/[0.05]">
            <Link
                v-for="item in primary"
                :key="item.key"
                :href="item.href"
                class="flex min-w-0 flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-[13px] font-semibold transition-colors"
                :class="item.active ? 'bg-base-action text-white shadow-sm' : 'text-ink/45 hover:bg-pale hover:text-ink'"
            >
                <span>{{ item.label }}</span>
                <span
                    v-if="item.count"
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold leading-none"
                    :class="item.active ? 'bg-white/20 text-white' : 'bg-pale text-ink/50'"
                >
                    {{ item.count }}
                </span>
            </Link>
        </div>
        <Link
            v-if="templatesHref"
            :href="templatesHref"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-white px-3.5 py-2.5 text-[13px] font-semibold text-ink/55 shadow-premium ring-1 ring-ink/[0.05] transition-colors hover:bg-pale hover:text-ink"
            :class="templatesActive ? 'text-deep ring-base/20' : ''"
        >
            <i class="ti ti-message-2-code text-base" aria-hidden="true" />
            Templates
        </Link>
    </nav>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    counts: { type: Object, default: () => ({ active: 0, resolved: 0 }) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuper = computed(() => !!user.value?.is_super_admin);

const current = computed(() => {
    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const status = computed(() => {
    try {
        return new URL(page.url, window.location.origin).searchParams.get('status') || 'open';
    } catch {
        return 'open';
    }
});

const onInbox = computed(() => current.value === 'admin.support.index' || current.value === 'admin.support.show');
const onTemplates = computed(() => current.value === 'admin.support.templates');

const primary = computed(() => [
    {
        key: 'active',
        label: 'Active',
        href: route('admin.support.index', { status: 'open' }),
        active: onInbox.value && status.value !== 'resolved',
        count: props.counts.active ?? 0,
    },
    {
        key: 'resolved',
        label: 'Resolved',
        href: route('admin.support.index', { status: 'resolved' }),
        active: onInbox.value && status.value === 'resolved',
        count: props.counts.resolved ?? 0,
    },
]);

const templatesHref = computed(() => (isSuper.value ? route('admin.support.templates') : null));
const templatesActive = computed(() => onTemplates.value);
</script>
