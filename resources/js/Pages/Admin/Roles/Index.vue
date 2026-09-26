<template>
    <Head title="Roles" />

    <AdminChrome title="Duties" eyebrow="Access" />
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[13px] font-medium text-ink/50">
                Assignable duties are lean on purpose — six operations bundles max. Super Admin–only work stays out of staff assignment.
            </p>
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover active:scale-[0.98]"
                @click="edit(null)"
            >
                <i class="ti ti-plus" aria-hidden="true" />
                Create duty
            </button>
        </div>

        <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
            Assignable to operations staff
        </p>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
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
                                Built-in
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
                        <ul
                            v-if="role.includes?.length"
                            class="mt-2 space-y-1"
                        >
                            <li
                                v-for="item in role.includes"
                                :key="item"
                                class="flex items-center gap-1.5 text-[12px] font-medium text-ink/50"
                            >
                                <i class="ti ti-point-filled text-[10px] text-base-action" aria-hidden="true" />
                                {{ item }}
                            </li>
                        </ul>
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
                        Edit
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-red-50 px-3 py-2 text-[12px] font-bold text-red-600"
                        @click="askDelete(role)"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>

        <template v-if="saDuties.length">
            <p class="mb-3 mt-8 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                Super Admin only — not assignable
            </p>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="role in saDuties"
                    :key="role.id"
                    class="flex flex-col rounded-2xl bg-white/80 p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-bold text-ink">{{ role.name }}</h2>
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                            Super Admin
                        </span>
                    </div>
                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">
                        {{ role.description || 'Reserved for Super Admin.' }}
                    </p>
                    <button
                        type="button"
                        class="mt-4 rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60"
                        @click="edit(role)"
                    >
                        View permissions
                    </button>
                </article>
            </div>
        </template>

        <AdminEmpty
            v-if="!rows.length"
            class="mt-3 rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            title="No assignable duties"
            description="Create a duty with grouped permissions, then assign it from a staff member’s page."
            icon="ti ti-id-badge"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-base-hover"
                @click="edit(null)"
            >
                Create duty
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
    super_admin_duties: { type: Array, default: () => [] },
    super_admin: { type: Object, default: () => ({}) },
    permission_groups: { type: Array, default: () => [] },
});

const rows = ref([...props.roles]);
const saDuties = ref([...props.super_admin_duties]);
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

watch(
    () => props.super_admin_duties,
    (value) => {
        saDuties.value = [...value];
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
    if (role.is_assignable === false) {
        const exists = saDuties.value.some((item) => item.id === role.id);
        saDuties.value = exists
            ? saDuties.value.map((item) => (item.id === role.id ? role : item))
            : [...saDuties.value, role];
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
