<template>
    <Head title="Tasks" />

    <AdminChrome title="Tasks" />

    <div class="mx-auto max-w-4xl">
        <OpsAttentionList :items="items" :open-count="open_count" />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import OpsAttentionList from '@/Components/Admin/OpsAttentionList.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    greeting: { type: String, default: '' },
    roles: { type: Array, default: () => [] },
    role_summary: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    shortcuts: { type: Array, default: () => [] },
    open_count: { type: Number, default: 0 },
    unread_count: { type: Number, default: 0 },
    unread_items: { type: Array, default: () => [] },
    restricted: { type: Boolean, default: false },
});
</script>
