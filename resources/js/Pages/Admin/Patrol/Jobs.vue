<template>
    <Head title="Job logs patrol" />

    <AdminChrome title="Job logs patrol" :eyebrow="eyebrow" />

    <PatrolQueue
        queue="jobs"
        :cases="cases"
        :filters="filters"
        :stats="stats"
        :options="options"
        :can="can"
        :staff="staff"
        :opened_id="opened_id"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import PatrolQueue from '@/Components/Admin/PatrolQueue.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    cases: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    options: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
    staff: { type: Array, default: () => [] },
    opened_id: { type: Number, default: null },
});

const eyebrow = computed(() => {
    const next = [];
    if (props.stats.new) {
        next.push(`${props.stats.new} new`);
    }
    if (props.stats.pending_approval) {
        next.push(`${props.stats.pending_approval} pending approval`);
    }
    return next.length ? next.join(', ') : 'No open flags';
});
</script>
