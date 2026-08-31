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
            class="flex-1 rounded-lg px-3 py-2.5 text-center text-[13px] font-semibold transition-colors"
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

const current = computed(() => {
    try {
        return route().current() || '';
    } catch {
        return '';
    }
});

const items = computed(() => {
    const list = [
        {
            label: 'Assigned to me',
            href: route('admin.assigned.index', { queue: 'moderation' }),
            active: String(current.value).startsWith('admin.assigned'),
        },
    ];

    if (!user.value?.is_super_admin) {
        list.push({
            label: pendingApprovals.value > 0 ? `My approvals (${pendingApprovals.value})` : 'My approvals',
            href: route('admin.my-approvals.index'),
            active: String(current.value).startsWith('admin.my-approvals'),
        });
    }

    return list;
});
</script>
