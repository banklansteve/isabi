<template>
    <Head title="Moderation" />
    <div class="p-8 text-sm font-medium text-ink/50">Redirecting…</div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

onMounted(() => {
    router.replace(route('admin.assigned.index', { queue: 'moderation' }));
});
</script>
