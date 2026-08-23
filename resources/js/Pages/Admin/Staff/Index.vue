<template>
    <Head title="Admin & staff" />

    <AdminChrome title="Admin & staff" :eyebrow="`${list.total.value.toLocaleString()} people`" />
        <div class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="list.q.value"
                        type="search"
                        placeholder="Search name or email…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover active:scale-[0.98]"
                    @click="inviteOpen = true"
                >
                    <i class="ti ti-user-plus" aria-hidden="true" />
                    Invite staff
                </button>
            </div>

            <div class="mt-3 no-scrollbar flex gap-1.5 overflow-x-auto border-t border-ink/[0.05] pt-3">
                <select v-model="statusFilter" class="chip-select" :class="statusFilter ? 'chip-select--on' : ''">
                    <option value="">All statuses</option>
                    <option value="invited">Invited</option>
                    <option value="active">Active</option>
                    <option value="suspended">Disabled</option>
                </select>
                <select v-model="roleFilter" class="chip-select" :class="roleFilter ? 'chip-select--on' : ''">
                    <option value="">All roles</option>
                    <option value="super_admin">Super Admin</option>
                    <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!rows.length"
                title="No staff yet"
                :description="`Invite your first operations teammate. They’ll get a link by email that expires in ${invite_ttl_hours} hours.`"
                icon="ti ti-shield-lock"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover"
                    @click="inviteOpen = true"
                >
                    <i class="ti ti-user-plus" aria-hidden="true" />
                    Invite staff
                </button>
            </AdminEmpty>
            <AdminEmpty
                v-else-if="!list.pageItems.value.length"
                title="No matching staff"
                description="Try a different name, status, or role."
                icon="ti ti-search"
            />
            <template v-else>
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[880px] text-left text-[13px]">
                        <thead class="border-b border-ink/[0.06] bg-pale/60 text-[11px] font-bold uppercase tracking-wide text-ink/40">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Roles</th>
                                <th class="px-3 py-3">Last login</th>
                                <th class="px-3 py-3">Joined</th>
                                <th class="px-4 py-3 text-right"> </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/[0.06]">
                            <tr
                                v-for="person in list.pageItems.value"
                                :key="person.id"
                                class="group cursor-pointer transition-colors duration-150"
                                :class="open?.id === person.id ? 'bg-tint/80' : 'hover:bg-pale/80'"
                                @click="openStaff(person)"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-tint text-[11px] font-bold text-deep">
                                            {{ person.initials || initials(person.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="truncate font-bold text-ink">{{ person.name }}</p>
                                                <span
                                                    v-if="person.on_leave"
                                                    class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800"
                                                >
                                                    On leave
                                                </span>
                                                <span
                                                    v-else-if="person.exited"
                                                    class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500"
                                                >
                                                    Former
                                                </span>
                                            </div>
                                            <p class="truncate text-[12px] text-ink/40">{{ person.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="statusClass(person.status)">
                                        {{ person.status_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div v-if="person.roles.length" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in person.roles"
                                            :key="role.id"
                                            class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold text-ink/55"
                                        >
                                            {{ role.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-ink/35">None</span>
                                </td>
                                <td class="px-3 py-3 font-medium text-ink/50">{{ person.last_login || '—' }}</td>
                                <td class="px-3 py-3 font-medium text-ink/50">{{ person.joined }}</td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <AdminStaffKebab
                                        :person="person"
                                        :open="menuId === person.id"
                                        :is-self="isSelf(person)"
                                        @toggle="toggleMenu(person.id)"
                                        @view="openStaff(person)"
                                        @resend="resend(person)"
                                        @action="startAction"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <ul class="divide-y divide-ink/[0.06] lg:hidden">
                    <li
                        v-for="person in list.pageItems.value"
                        :key="person.id"
                        class="px-4 py-3.5 transition-colors duration-150"
                        :class="open?.id === person.id ? 'bg-tint/80' : ''"
                    >
                        <div class="flex items-start gap-3">
                            <button type="button" class="flex min-w-0 flex-1 items-start gap-3 text-left" @click="openStaff(person)">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-tint text-xs font-bold text-deep">
                                    {{ person.initials || initials(person.name) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-bold text-ink">{{ person.name }}</p>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                            :class="statusClass(person.status)"
                                        >
                                            {{ person.status_label }}
                                        </span>
                                        <span
                                            v-if="person.on_leave"
                                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800"
                                        >
                                            On leave
                                        </span>
                                    </div>
                                    <p class="mt-0.5 truncate text-[13px] font-medium text-ink/45">{{ person.email }}</p>
                                    <p class="mt-1 text-[12px] font-medium text-ink/40">
                                        {{ person.roles.map((role) => role.name).join(' · ') || 'No roles' }}
                                    </p>
                                    <p class="mt-1 text-[12px] text-ink/35">
                                        Last login {{ person.last_login || 'never' }} · Joined {{ person.joined }}
                                    </p>
                                </div>
                            </button>
                            <AdminStaffKebab
                                :person="person"
                                :open="menuId === person.id"
                                :is-self="isSelf(person)"
                                @toggle="toggleMenu(person.id)"
                                @view="openStaff(person)"
                                @resend="resend(person)"
                                @action="startAction"
                            />
                        </div>
                    </li>
                </ul>
            </template>
        </div>

        <AdminClientPager
            :page="list.page.value"
            :pages="list.pageCount.value"
            :total="list.total.value"
            :per-page="list.perPage"
            @update:page="list.page.value = $event"
        />

        <AdminStaffDrawer
            :person="open"
            :panel="panel"
            :loading="panelLoading"
            :roles="roles"
            @close="closeStaff"
            @updated="onUpdated"
            @deleted="onDeleted"
            @refresh="loadPanel"
            @resend="resend"
        />

        <AdminDrawer :open="inviteOpen" title="Invite staff" eyebrow="New teammate" @close="closeInvite">
            <form class="space-y-4" @submit.prevent="invite">
                <label class="block">
                    <span class="text-[12px] font-semibold text-ink/50">Work email</span>
                    <input
                        v-model="inviteForm.email"
                        type="email"
                        required
                        autocomplete="off"
                        placeholder="you@isabi.dev"
                        class="mt-1.5 w-full rounded-xl border px-3 py-2.5 text-sm font-medium outline-none focus:ring-4"
                        :class="inviteErrors.email ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : 'border-ink/10 focus:border-base focus:ring-base/15'"
                    />
                    <p v-if="inviteErrors.email" class="mt-1.5 text-xs font-semibold text-red-500">{{ inviteErrors.email }}</p>
                </label>
                <label class="block">
                    <span class="text-[12px] font-semibold text-ink/50">Name <span class="font-medium text-ink/35">(optional)</span></span>
                    <input
                        v-model="inviteForm.name"
                        type="text"
                        placeholder="They can fill this in when they accept"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </label>
                <label class="block">
                    <span class="text-[12px] font-semibold text-ink/50">Suggested role <span class="font-medium text-ink/35">(optional)</span></span>
                    <select
                        v-model="inviteForm.suggested_role_id"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base"
                    >
                        <option value="">None — assign later</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                    <p class="mt-1.5 text-[12px] font-medium text-ink/40">
                        Not applied until they accept. You can assign or change roles anytime after.
                    </p>
                </label>
                <p class="rounded-xl bg-pale px-3 py-2.5 text-[13px] font-medium text-ink/55">
                    The invite link expires in {{ invite_ttl_hours }} hours.
                </p>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/50 hover:bg-pale" @click="closeInvite">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover disabled:opacity-50"
                        :disabled="inviteBusy || !inviteForm.email"
                        @click="invite"
                    >
                        {{ inviteBusy ? 'Sending…' : 'Send invite' }}
                    </button>
                </div>
            </template>
        </AdminDrawer>

        <AdminConfirmDialog
            :open="!!rowAction"
            :title="rowDialogMeta.title"
            :description="rowDialogMeta.description"
            :confirm-label="rowDialogMeta.confirmLabel"
            :tone="rowDialogMeta.tone"
            :require-reason="true"
            :confirm-phrase="rowAction?.type === 'remove' ? rowAction.person.name : ''"
            :processing="busy"
            @close="rowAction = null"
            @confirm="runRowAction"
        />
</template>

<script setup>
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminStaffDrawer from '@/Components/Admin/AdminStaffDrawer.vue';
import AdminStaffKebab from '@/Components/Admin/AdminStaffKebab.vue';
import { useClientList } from '@/Composables/useClientList';
import { toast } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    staff: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    invite_ttl_hours: { type: Number, default: 48 },
    opened_id: { type: Number, default: null },
});

const page = usePage();
const meId = computed(() => page.props.auth?.user?.id);
const rows = ref([...props.staff]);
const statusFilter = ref('');
const roleFilter = ref('');
const open = ref(null);
const panel = ref(null);
const panelLoading = ref(false);
const inviteOpen = ref(false);
const inviteBusy = ref(false);
const inviteForm = ref({ email: '', name: '', suggested_role_id: '' });
const inviteErrors = ref({ email: '' });
const menuId = ref(null);
const rowAction = ref(null);
const busy = ref(false);
let panelSeq = 0;

const list = useClientList(
    () => rows.value.filter((person) => {
        if (statusFilter.value && person.status !== statusFilter.value) return false;
        if (roleFilter.value && !(person.roles || []).some((role) => String(role.id) === String(roleFilter.value))) return false;
        return true;
    }),
    {
        perPage: 24,
        searchFields: ['name', 'email'],
        sort: 'name_asc',
        sortMap: { name: 'name' },
    },
);

watch(
    () => props.staff,
    (value) => {
        rows.value = [...value];
    },
);

watch([statusFilter, roleFilter], () => {
    list.page.value = 1;
});

const isSelf = (person) => person.id === meId.value;

const toggleMenu = (id) => {
    menuId.value = menuId.value === id ? null : id;
};

const onDocClick = () => {
    menuId.value = null;
};

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));

const replaceListUrl = (path) => {
    window.history.replaceState(window.history.state, '', path);
};

const onUpdated = (user) => {
    rows.value = rows.value.map((row) => (row.id === user.id ? { ...row, ...user } : row));
    if (open.value?.id === user.id) {
        open.value = { ...open.value, ...user };
    }
};

const loadPanel = async () => {
    if (!open.value) {
        return;
    }
    const id = open.value.id;
    const seq = ++panelSeq;
    panelLoading.value = true;
    try {
        const { data } = await axios.get(route('admin.staff.show', id), {
            headers: { Accept: 'application/json' },
        });
        if (seq !== panelSeq || open.value?.id !== id) {
            return;
        }
        panel.value = data;
        if (data.staff) {
            onUpdated({ ...open.value, ...data.staff });
        }
    } catch {
        if (seq === panelSeq) {
            toast({ type: 'error', title: 'Couldn’t load', message: 'Staff details did not load.' });
        }
    } finally {
        if (seq === panelSeq) {
            panelLoading.value = false;
        }
    }
};

const openStaff = async (person) => {
    menuId.value = null;
    open.value = person;
    panel.value = null;
    replaceListUrl(route('admin.staff.show', person.id));
    await loadPanel();
};

const closeStaff = () => {
    open.value = null;
    panel.value = null;
    replaceListUrl(route('admin.staff.index'));
};

watch(
    () => props.opened_id,
    (id) => {
        if (!id) {
            return;
        }
        const person = rows.value.find((row) => row.id === id);
        if (person) {
            openStaff(person);
        }
    },
    { immediate: true },
);

const onDeleted = (id) => {
    rows.value = rows.value.filter((row) => row.id !== id);
    closeStaff();
};

const closeInvite = () => {
    inviteOpen.value = false;
    inviteErrors.value = { email: '' };
};

const invite = async () => {
    inviteBusy.value = true;
    inviteErrors.value = { email: '' };
    try {
        const { data } = await axios.post(route('admin.staff.store'), {
            email: inviteForm.value.email,
            name: inviteForm.value.name || null,
            suggested_role_id: inviteForm.value.suggested_role_id || null,
        });
        toast(data.toast);
        if (data.staff) {
            rows.value = [data.staff, ...rows.value.filter((row) => row.id !== data.staff.id)];
        }
        inviteForm.value = { email: '', name: '', suggested_role_id: '' };
        closeInvite();
    } catch (error) {
        inviteErrors.value.email =
            error.response?.data?.errors?.email?.[0] || error.response?.data?.message || 'Could not send that invite.';
    } finally {
        inviteBusy.value = false;
    }
};

const resend = async (person) => {
    menuId.value = null;
    try {
        const { data } = await axios.post(route('admin.staff.resend', person.id));
        toast(data.toast);
        if (data.staff) {
            onUpdated(data.staff);
        }
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t resend',
            message: error.response?.data?.message || error.response?.data?.errors?.email?.[0] || 'Try again shortly.',
        });
    }
};

const startAction = ({ type, person }) => {
    menuId.value = null;
    rowAction.value = { type, person };
};

const rowDialogMeta = computed(() => {
    const type = rowAction.value?.type;
    if (type === 'reinstate') {
        return {
            title: 'Reinstate access',
            description: 'They can sign in again.',
            confirmLabel: 'Reinstate',
            tone: 'default',
        };
    }
    if (type === 'revoke') {
        return {
            title: 'Revoke invite',
            description: 'The pending invite is removed. They never become active staff.',
            confirmLabel: 'Revoke',
            tone: 'danger',
        };
    }
    if (type === 'remove') {
        return {
            title: 'Remove this staff account',
            description: 'Type their name to confirm. Sessions are invalidated immediately.',
            confirmLabel: 'Remove',
            tone: 'danger',
        };
    }
    return {
        title: 'Disable this account',
        description: rowAction.value?.person?.is_super
            ? 'They will be signed out everywhere. Acting on another Super Admin is logged as a high-trust change.'
            : 'They will be signed out everywhere and cannot sign in until reinstated.',
        confirmLabel: 'Disable',
        tone: 'danger',
    };
});

const runRowAction = async ({ reason, confirmation }) => {
    if (!rowAction.value) {
        return;
    }
    const { type, person } = rowAction.value;
    busy.value = true;
    try {
        const urls = {
            disable: route('admin.staff.disable', person.id),
            reinstate: route('admin.staff.reinstate', person.id),
            revoke: route('admin.staff.revoke', person.id),
            remove: route('admin.staff.destroy', person.id),
        };
        const { data } = await axios.post(urls[type], { reason, confirmation });
        toast(data.toast);
        if (data.deleted_id) {
            onDeleted(data.deleted_id);
        } else if (data.staff) {
            onUpdated(data.staff);
        }
        rowAction.value = null;
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t complete',
            message: error.response?.data?.message || error.response?.data?.errors?.confirmation?.[0] || 'Try again.',
        });
    } finally {
        busy.value = false;
    }
};

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const statusClass = (status) => {
    if (status === 'active') return 'bg-emerald-50 text-emerald-700';
    if (status === 'suspended') return 'bg-red-50 text-red-600';
    return 'bg-pale text-ink/50';
};
</script>

<style scoped>
.chip-select {
    @apply shrink-0 appearance-none rounded-full bg-white py-1.5 pl-3 pr-8 text-[12px] font-semibold text-ink/50 ring-1 ring-ink/[0.06] outline-none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-position: right 0.65rem center;
    background-repeat: no-repeat;
}
.chip-select--on {
    @apply bg-tint text-deep ring-transparent;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
