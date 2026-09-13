<template>
    <AdminDrawer :open="open" size="md" :title="title" :eyebrow="eyebrow" @close="$emit('close')">
        <form class="space-y-5" @submit.prevent="save">
            <p v-if="readOnly" class="rounded-xl bg-pale px-3 py-3 text-[13px] font-medium text-ink/55">
                Super Admin has full platform control and cannot be edited or deleted. It exists to protect the platform.
            </p>
            <p v-else-if="isBuiltIn" class="rounded-xl bg-amber-50 px-3 py-3 text-[13px] font-medium text-amber-900">
                This is a built-in duty. Editing its permissions changes access for everyone assigned to it. The duty’s slug stays fixed so smart pages keep working.
            </p>

            <label class="block">
                <span class="text-[12px] font-semibold text-ink/50">Name</span>
                <input
                    v-model="form.name"
                    type="text"
                    required
                    :disabled="readOnly"
                    placeholder="e.g. Support"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 disabled:bg-pale"
                />
                <p v-if="errors.name" class="mt-1 text-xs font-semibold text-red-500">{{ errors.name }}</p>
            </label>

            <label class="block">
                <span class="text-[12px] font-semibold text-ink/50">Description</span>
                <textarea
                    v-model="form.description"
                    rows="3"
                    :disabled="readOnly"
                    placeholder="What this role is trusted to do"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 disabled:bg-pale"
                />
            </label>

            <div>
                <p class="mb-2 text-[12px] font-semibold text-ink/50">Permissions</p>
                <AdminPermissionPicker v-model="form.permissions" :groups="groups" :disabled="readOnly" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/50 hover:bg-pale"
                    @click="$emit('close')"
                >
                    {{ readOnly ? 'Close' : 'Cancel' }}
                </button>
                <button
                    v-if="!readOnly"
                    type="button"
                    class="rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-base-hover disabled:opacity-50"
                    :disabled="busy || !form.name.trim()"
                    @click="save"
                >
                    {{ busy ? 'Saving…' : role ? 'Save role' : 'Create role' }}
                </button>
            </div>
        </template>
    </AdminDrawer>
</template>

<script setup>
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminPermissionPicker from '@/Components/Admin/AdminPermissionPicker.vue';
import { toast } from '@/utils/adminRange';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    role: { type: Object, default: null },
    groups: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const busy = ref(false);
const errors = reactive({ name: '' });
const form = reactive({
    name: '',
    description: '',
    permissions: [],
});

const readOnly = computed(() => props.role?.id === 'super_admin');
const isBuiltIn = computed(() => !!props.role?.is_system && !readOnly.value);
const title = computed(() => {
    if (readOnly.value) return props.role?.name || 'System role';
    return props.role ? props.role.name : 'Create a duty';
});
const eyebrow = computed(() => {
    if (readOnly.value) return 'System role';
    if (isBuiltIn.value) return 'Built-in duty';
    return props.role ? 'Edit duty' : 'New duty';
});

watch(
    () => [props.open, props.role],
    () => {
        errors.name = '';
        form.name = props.role?.name || '';
        form.description = props.role?.description || '';
        form.permissions = [...(props.role?.permissions || [])];
    },
);

const save = async () => {
    if (readOnly.value) {
        return;
    }
    busy.value = true;
    errors.name = '';
    try {
        const payload = {
            name: form.name.trim(),
            description: form.description.trim() || null,
            permissions: form.permissions,
        };
        const url = props.role ? route('admin.roles.update', props.role.id) : route('admin.roles.store');
        const { data } = props.role
            ? await axios.patch(url, payload)
            : await axios.post(url, payload);
        toast(data.toast);
        emit('saved', data.role);
        emit('close');
    } catch (error) {
        errors.name = error.response?.data?.errors?.name?.[0] || '';
        toast({
            type: 'error',
            title: 'Couldn’t save',
            message: error.response?.data?.message || errors.name || 'Check the role name and try again.',
        });
    } finally {
        busy.value = false;
    }
};
</script>
