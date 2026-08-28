<template>
    <nav
        v-if="items.length > 1"
        class="mb-4 flex gap-1 rounded-xl bg-white p-1 shadow-premium ring-1 ring-ink/[0.05]"
        aria-label="My work"
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
const abilities = computed(() => user.value?.abilities || []);
const canSupport = computed(() =>
    abilities.value.includes('admin.support.manage') || !!user.value?.is_super_admin,
);

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
            label: 'My stats',
            href: route('admin.insights.index'),
            active: String(current.value).startsWith('admin.insights'),
        },
    ];

    if (canSupport.value) {
        list.push({
            label: 'Support reports',
            href: route('admin.support.reports'),
            active: current.value === 'admin.support.reports',
        });
    }

    return list;
});
</script>
