<template>
    <AdminConfirmDialog
        :open="open"
        :title="title"
        :description="description"
        confirm-label="Escalate to Super Admin"
        reason-placeholder="What should Super Admin know…"
        :processing="processing"
        @close="$emit('close')"
        @confirm="onConfirm"
    />
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Escalate to Super Admin?' },
    description: {
        type: String,
        default: 'This sends the case upstairs with your note. Super Admin gets notified immediately.',
    },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const onConfirm = ({ reason }) => {
    emit('confirm', { note: reason });
};
</script>
