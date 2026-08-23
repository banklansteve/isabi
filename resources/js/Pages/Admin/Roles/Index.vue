<template>
    <Head title="Roles" />

    <AdminChrome title="Roles" eyebrow="Access" />
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[13px] font-medium text-ink/50">
                Roles are permission bundles. Assign them from a staff member’s page — not only at invite time.
            </p>
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover active:scale-[0.98]"
                @click="edit(null)"
            >
                <i class="ti ti-plus" aria-hidden="true" />
                Create role
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article class="flex flex-col rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-ink">{{ super_admin.name }}</h2>
                            <span class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/40">
                                System
                            </span>
                        </div>
                        <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">{{ super_admin.description }}</p>
                    </div>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-[12px]">
                    <div class="rounded-xl bg-pale px-3 py-2">
                        <dt class="font-semibold text-ink/40">Staff</dt>
                        <dd class="mt-0.5 text-sm font-bold tabular-nums text-ink">{{ super_admin.assigned_count }}</dd>
                    </div>
                    <div class="rounded-xl bg-pale px-3 py-2">
                        <dt class="font-semibold text-ink/40">Permissions</dt>
                        <dd class="mt-0.5 text-sm font-bold tabular-nums text-ink">All</dd>
                    </div>
                </dl>
                <button
                    type="button"
                    class="mt-4 rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60"
                    @click="edit(super_admin)"
                >
                    View permissions
                </button>
            </article>

            <article
                v-for="role in rows"
                :key="role.id"
                class="flex flex-col rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-ink">{{ role.name }}</h2>
                            <span
                                v-if="role.is_system"
                                class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/40"
                            >
                                System
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                :class="role.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-pale text-ink/40'"
                            >
                                {{ role.is_active ? 'Active' : 'Off' }}
                            </span>
                        </div>
                        <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">
                            {{ role.description || 'No description' }}
                        </p>
                    </div>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-[12px]">
                    <div class="rounded-xl bg-pale px-3 py-2">
                        <dt class="font-semibold text-ink/40">Staff</dt>
                        <dd class="mt-0.5 text-sm font-bold tabular-nums text-ink">{{ role.assigned_count }}</dd>
                    </div>
                    <div class="rounded-xl bg-pale px-3 py-2">
                        <dt class="font-semibold text-ink/40">Permissions</dt>
                        <dd class="mt-0.5 text-sm font-bold tabular-nums text-ink">{{ role.permissions_count }}</dd>
                    </div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60"
                        @click="edit(role)"
                    >
                        {{ role.is_system ? 'View' : 'Edit' }}
                    </button>
                    <button
                        v-if="!role.is_system"
                        type="button"
                        class="rounded-xl bg-red-50 px-3 py-2 text-[12px] font-bold text-red-600"
                        @click="askDelete(role)"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>

        <AdminEmpty
            v-if="!rows.length"
            class="mt-3 rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            title="No custom roles"
            description="Create a role with grouped permissions, then assign it from a staff member’s page."
            icon="ti ti-id-badge"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-base-hover"
                @click="edit(null)"
            >
                Create role
            </button>
        </AdminEmpty>

        <AdminRoleDrawer
            :open="drawerOpen"
            :role="editing"
            :groups="permission_groups"
            @close="drawerOpen = false"
            @saved="onSaved"
        />

        <AdminConfirmDialog
            :open="!!deleting"
            title="Delete this role"
            :description="deleteDescription"
            confirm-label="Delete role"
            tone="danger"
            :require-reason="true"
            :processing="busy"
            @close="deleting = null"
            @confirm="destroy"
        >
            <label v-if="deleting?.assigned_count" class="mt-4 block">
                <span class="text-[12px] font-semibold text-ink/50">Reassign those staff (optional)</span>
                <select v-model="reassignTo" class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium">
                    <option value="">Leave them without this role</option>
                    <option v-for="role in reassignOptions" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
            </label>
        </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminRoleDrawer from '@/Components/Admin/AdminRoleDrawer.vue';
import { toast } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    super_admin: { type: Object, default: () => ({}) },
    permission_groups: { type: Array, default: () => [] },
});

const rows = ref([...props.roles]);
const drawerOpen = ref(false);
const editing = ref(null);
const deleting = ref(null);
const reassignTo = ref('');
const busy = ref(false);

watch(
    () => props.roles,
    (value) => {
        rows.value = [...value];
    },
);

const reassignOptions = computed(() => rows.value.filter((role) => role.id !== deleting.value?.id && role.is_active));

const deleteDescription = computed(() => {
    const count = deleting.value?.assigned_count || 0;
    if (count > 0) {
        return `${count} staff currently have this role. Reassign them below, or they will keep their other roles and may see a restricted console if they have none left.`;
    }
    return 'This role will be removed. Staff are not currently assigned to it.';
});

const edit = (role) => {
    editing.value = role;
    drawerOpen.value = true;
};

const onSaved = (role) => {
    if (!role) {
        return;
    }
    const exists = rows.value.some((item) => item.id === role.id);
    rows.value = exists
        ? rows.value.map((item) => (item.id === role.id ? role : item))
        : [...rows.value, role];
};

const askDelete = (role) => {
    deleting.value = role;
    reassignTo.value = '';
};

const destroy = async ({ reason }) => {
    if (!deleting.value) {
        return;
    }
    busy.value = true;
    try {
        const { data } = await axios.delete(route('admin.roles.destroy', deleting.value.id), {
            data: {
                reason,
                reassign_to: reassignTo.value || null,
            },
        });
        toast(data.toast);
        rows.value = rows.value.filter((role) => role.id !== deleting.value.id);
        deleting.value = null;
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t delete',
            message: error.response?.data?.message || 'Try again.',
        });
    } finally {
        busy.value = false;
    }
};
</script>
