<template>
    <AdminConfirmDialog
        :open="open"
        :title="title"
        :description="description"
        :confirm-label="confirmLabel"
        reason-placeholder="What should they look at…"
        :processing="processing"
        @close="$emit('close')"
        @confirm="onConfirm"
    >
        <label class="mt-4 block">
            <span class="text-[12px] font-semibold text-ink/50">Operations staff</span>
            <select
                v-model="assigneeId"
                required
                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            >
                <option value="" disabled>Pick a staff member</option>
                <option
                    v-for="person in filteredStaff"
                    :key="person.id"
                    :value="String(person.id)"
                >
                    {{ person.name }}
                </option>
            </select>
            <p v-if="assigneeError" class="mt-1.5 text-[12px] font-medium text-coral">{{ assigneeError }}</p>
        </label>

        <label v-if="showQueue" class="mt-3 block">
            <span class="text-[12px] font-semibold text-ink/50">Queue</span>
            <select
                v-model="queue"
                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            >
                <option v-for="option in queueOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </label>
    </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Refer to operations?' },
    description: {
        type: String,
        default: 'This assigns the case to a colleague immediately.',
    },
    confirmLabel: { type: String, default: 'Refer' },
    staff: { type: Array, default: () => [] },
    defaultQueue: { type: String, default: 'general' },
    showQueue: { type: Boolean, default: true },
    processing: { type: Boolean, default: false },
    initialAssigneeId: { type: [String, Number], default: '' },
});

const emit = defineEmits(['close', 'confirm']);

const page = usePage();
const meId = computed(() => Number(page.props.auth?.user?.id || 0));

const assigneeId = ref('');
const queue = ref(props.defaultQueue);
const assigneeError = ref('');

const queueOptions = [
    { value: 'moderation', label: 'Moderation' },
    { value: 'support', label: 'Support' },
    { value: 'patrol', label: 'Patrol' },
    { value: 'general', label: 'General' },
];

const filteredStaff = computed(() =>
    (props.staff || []).filter((person) => Number(person.id) !== meId.value),
);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }
        assigneeId.value = props.initialAssigneeId ? String(props.initialAssigneeId) : '';
        queue.value = props.defaultQueue || 'general';
        assigneeError.value = '';
    },
);

watch(
    () => props.defaultQueue,
    (value) => {
        if (props.open) {
            queue.value = value || 'general';
        }
    },
);

const onConfirm = ({ reason }) => {
    if (!assigneeId.value) {
        assigneeError.value = 'Pick an operations staff member.';
        return;
    }
    assigneeError.value = '';
    emit('confirm', {
        assignee_id: Number(assigneeId.value),
        note: reason,
        queue: queue.value,
    });
};
</script>
