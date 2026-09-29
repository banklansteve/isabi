<template>
    <nav
        v-if="items.length > 1"
        class="mb-4 flex gap-1 rounded-xl bg-white p-1 shadow-premium ring-1 ring-ink/[0.05]"
        aria-label="Assigned work"
    >
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            prefetch
            :preserve-scroll="true"
            :preserve-state="false"
            :show-progress="false"
            class="flex-1 rounded-lg px-3 py-2.5 text-center text-[13px] font-semibold transition-colors duration-150"
            :class="item.active ? 'bg-base-action text-white shadow-sm' : 'text-ink/45 hover:bg-pale hover:text-ink'"
        >
            {{ item.label }}
        </Link>
    </nav>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const pendingApprovals = computed(() => Number(page.props.my_approvals_pending || 0));
const desk = computed(() => String(page.props.desk || 'assigned'));

const current = computed(() => {
    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const onAssigned = computed(() => String(current.value).startsWith('admin.assigned'));

const items = computed(() => {
    const isSuper = !!user.value?.is_super_admin;
    const assignedQueue = isSuper ? 'all' : 'moderation';

    const list = [
        {
            label: 'Assigned to me',
            href: route('admin.assigned.index', { desk: 'assigned', queue: assignedQueue }),
            active: onAssigned.value && desk.value !== 'referred',
        },
        {
            label: 'Referred by me',
            href: route('admin.assigned.index', { desk: 'referred', queue: 'all' }),
            active: onAssigned.value && desk.value === 'referred',
        },
    ];

    if (!isSuper) {
        list.push({
            label: pendingApprovals.value > 0 ? `My approvals (${pendingApprovals.value})` : 'My approvals',
            href: route('admin.my-approvals.index'),
            active: String(current.value).startsWith('admin.my-approvals'),
        });
    }

    return list;
});
</script>
