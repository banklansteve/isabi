<template>
    <AdminDrawer :open="!!person" size="lg" @close="$emit('close')">
        <template #header>
            <div v-if="shown" class="flex items-start gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-tint text-sm font-bold text-deep">
                    {{ shown.initials || initials(shown.name) }}
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-[17px] font-bold tracking-tight text-ink">{{ shown.name }}</h2>
                    <p class="mt-0.5 truncate text-[13px] font-medium text-ink/45">{{ shown.email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="statusClass(shown.status)"
                        >
                            {{ shown.status_label }}
                        </span>
                        <span
                            v-if="shown.on_leave"
                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800"
                        >
                            On leave
                        </span>
                        <span class="text-[12px] font-medium text-ink/40">
                            {{ shown.password_set ? `Joined ${shown.joined}` : `Invited ${shown.joined}` }}
                        </span>
                    </div>
                </div>
                <div ref="menuRoot" class="relative shrink-0">
                    <button
                        type="button"
                        class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/40 hover:bg-pale hover:text-ink"
                        aria-label="More actions"
                        :aria-expanded="menu"
                        @click="menu = !menu"
                    >
                        <i class="ti ti-dots text-lg" aria-hidden="true" />
                    </button>
                    <AdminSlideMenu :open="menu">
                        <button
                            v-if="shown.status === 'invited'"
                            type="button"
                            class="menu-item"
                            @click="menu = false; $emit('resend', shown)"
                        >
                            Resend invite
                        </button>
                        <button
                            v-if="shown.status === 'invited'"
                            type="button"
                            class="menu-item text-red-600"
                            @click="ask('revoke')"
                        >
                            Revoke invite
                        </button>
                        <button
                            v-if="shown.status === 'active' && !isSelf"
                            type="button"
                            class="menu-item text-red-600"
                            @click="ask('disable')"
                        >
                            Disable access
                        </button>
                        <button
                            v-if="shown.status === 'suspended' && !isSelf"
                            type="button"
                            class="menu-item"
                            @click="ask('reinstate')"
                        >
                            Reinstate
                        </button>
                        <button
                            v-if="shown.status !== 'invited' && !isSelf"
                            type="button"
                            class="menu-item text-red-600"
                            @click="ask('remove')"
                        >
                            Remove account
                        </button>
                        <p v-if="isSelf" class="px-3 py-2 text-[12px] font-medium text-ink/40">
                            You cannot change your own access here.
                        </p>
                    </AdminSlideMenu>
                </div>
                <button
                    type="button"
                    class="tap-target flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-ink/40 hover:bg-pale hover:text-ink"
                    aria-label="Close"
                    @click="$emit('close')"
                >
                    <i class="ti ti-x text-lg" aria-hidden="true" />
                </button>
            </div>
        </template>

        <div v-if="shown" class="space-y-5">
            <div class="flex rounded-full bg-pale p-1">
                <button
                    v-for="item in tabs"
                    :key="item.id"
                    type="button"
                    class="flex-1 rounded-full px-3 py-1.5 text-[13px] font-semibold transition-all duration-200 ease-[cubic-bezier(0.32,0.72,0,1)]"
                    :class="tab === item.id ? 'bg-white text-ink shadow-sm' : 'text-ink/40 hover:text-ink'"
                    @click="tab = item.id"
                >
                    {{ item.label }}
                </button>
            </div>

            <div v-if="loading" class="space-y-3" aria-hidden="true">
                <div class="h-4 w-32 animate-pulse rounded bg-pale" />
                <div class="h-10 animate-pulse rounded-xl bg-pale" />
                <div class="h-24 animate-pulse rounded-xl bg-pale" />
            </div>

            <Transition v-if="!loading" name="admin-pane" mode="out-in">
            <div v-if="tab === 'overview'" key="overview" class="space-y-6">
                <section>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-[13px] font-bold text-ink">Roles</h3>
                        <div ref="assignRoot" class="relative">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-base-action px-2.5 py-1.5 text-[12px] font-bold text-white shadow-[0_8px_18px_-8px_rgba(26,79,181,0.55)] transition-colors duration-150 hover:bg-base-hover disabled:opacity-50"
                                :aria-expanded="assignOpen"
                                :disabled="assigning"
                                @click="assignOpen = !assignOpen"
                            >
                                <i class="ti ti-plus text-sm" aria-hidden="true" />
                                Assign role
                            </button>
                            <AdminSlideMenu :open="assignOpen" width-class="mt-1.5 w-72" role="listbox">
                                <div class="border-b border-ink/[0.06] p-2">
                                    <input
                                        v-model="roleQuery"
                                        type="search"
                                        placeholder="Search roles…"
                                        class="w-full rounded-lg border border-ink/10 px-2.5 py-2 text-[13px] font-medium outline-none focus:border-base"
                                        aria-label="Search roles"
                                    />
                                </div>
                                <ul class="max-h-64 overflow-y-auto py-1">
                                    <li v-if="!assignable.length" class="px-3 py-3 text-[13px] font-medium text-ink/40">
                                        No matching roles.
                                    </li>
                                    <li v-for="role in assignable" :key="role.id">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-[13px] font-semibold text-ink/75 transition-colors duration-150 hover:bg-pale"
                                            @click="assignRole(role)"
                                        >
                                            <span>{{ role.name }}</span>
                                            <span v-if="role.system" class="text-[10px] font-bold uppercase tracking-wide text-ink/35">
                                                System
                                            </span>
                                        </button>
                                    </li>
                                </ul>
                            </AdminSlideMenu>
                        </div>
                    </div>

                    <div v-if="currentRoles.length" class="mt-3 flex flex-wrap gap-1.5">
                        <span
                            v-for="role in currentRoles"
                            :key="role.id"
                            class="inline-flex items-center gap-1 rounded-full bg-tint px-2.5 py-1 text-[12px] font-semibold text-deep"
                        >
                            {{ role.name }}
                            <button
                                type="button"
                                class="flex h-4 w-4 items-center justify-center rounded-full text-deep/50 hover:bg-white hover:text-deep"
                                :aria-label="`Remove ${role.name}`"
                                :disabled="assigning || (isSelf && role.id === 'super_admin')"
                                @click="removeRole(role)"
                            >
                                <i class="ti ti-x text-[11px]" aria-hidden="true" />
                            </button>
                        </span>
                    </div>
                    <p v-else class="mt-3 rounded-xl bg-pale px-3 py-3 text-[13px] font-medium text-ink/50">
                        No roles assigned. They can sign in, but the console stays restricted until you give them a role.
                    </p>
                    <p v-if="shown.is_super && !isSelf" class="mt-2 text-[12px] font-medium text-ink/40">
                        Changing Super Admin access is logged as a high-trust action.
                    </p>
                </section>

                <dl class="grid gap-3 text-[13px] sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-ink/40">Last login</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ shown.last_login || 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">Invite expiry</dt>
                        <dd class="mt-0.5 font-medium text-ink">
                            <template v-if="shown.status === 'invited'">
                                {{ shown.invite_expired ? 'Expired' : shown.invite_expires_at || '—' }}
                            </template>
                            <template v-else>—</template>
                        </dd>
                    </div>
                    <div v-if="detail.invited_by">
                        <dt class="font-semibold text-ink/40">Invited by</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.invited_by.name }}</dd>
                    </div>
                    <div v-if="detail.suggested_role">
                        <dt class="font-semibold text-ink/40">Suggested role</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.suggested_role }}</dd>
                    </div>
                    <div v-if="detail.suspension_reason" class="sm:col-span-2">
                        <dt class="font-semibold text-ink/40">Disabled because</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.suspension_reason }}</dd>
                    </div>
                </dl>

                <Link
                    v-if="canSeeHr"
                    :href="route('admin.hr.staff.show', shown.id)"
                    class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-base-action hover:text-base-hover"
                >
                    <i class="ti ti-id-badge-2" aria-hidden="true" />
                    Open HR profile
                </Link>

                <section v-if="!isSelf && shown.status !== 'invited'" class="rounded-2xl border border-red-100 bg-red-50/40 p-4">
                    <h3 class="text-[13px] font-bold text-red-700">Danger zone</h3>
                    <p class="mt-1 text-[13px] font-medium text-ink/50">
                        Disable immediately signs them out. Removing the account requires typing their name.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-if="shown.status === 'active'"
                            type="button"
                            class="rounded-xl border border-red-200 bg-white px-3 py-2 text-[12px] font-bold text-red-600"
                            @click="ask('disable')"
                        >
                            Disable account
                        </button>
                        <button
                            v-else
                            type="button"
                            class="rounded-xl border border-emerald-200 bg-white px-3 py-2 text-[12px] font-bold text-emerald-700"
                            @click="ask('reinstate')"
                        >
                            Reinstate account
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-red-600 px-3 py-2 text-[12px] font-bold text-white"
                            @click="ask('remove')"
                        >
                            Remove account
                        </button>
                    </div>
                </section>
            </div>

            <div v-else key="activity" class="space-y-5">
                <div>
                    <h3 class="text-[13px] font-bold text-ink">What they’ve done</h3>
                    <ul v-if="panel?.activity?.logins?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.logins" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.title }}</p>
                            <p class="text-ink/40">{{ row.when }}<span v-if="row.ip"> · {{ row.ip }}</span></p>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No sign-ins recorded yet.</p>
                </div>
                <div>
                    <h3 class="text-[13px] font-bold text-ink">What was done to this account</h3>
                    <ul v-if="panel?.activity?.admin_actions?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.admin_actions" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.summary }}</p>
                            <p class="text-ink/40">{{ row.actor || 'System' }} · {{ row.when }}</p>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No access changes logged yet.</p>
                </div>
            </div>
            </Transition>
        </div>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="!!dialog"
        :title="dialogMeta.title"
        :description="dialogMeta.description"
        :confirm-label="dialogMeta.confirmLabel"
        :tone="dialogMeta.tone"
        :require-reason="true"
        :confirm-phrase="dialog === 'remove' ? shown?.name : ''"
        :processing="busy"
        @close="dialog = null"
        @confirm="runDialog"
    />
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminSlideMenu from '@/Components/Admin/AdminSlideMenu.vue';
import { toast } from '@/utils/adminRange';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    person: { type: Object, default: null },
    panel: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    roles: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated', 'deleted', 'refresh', 'resend']);

const page = usePage();
const tab = ref('overview');
const menu = ref(false);
const menuRoot = ref(null);
const assignRoot = ref(null);
const assignOpen = ref(false);
const roleQuery = ref('');
const assigning = ref(false);
const dialog = ref(null);
const busy = ref(false);
const lastPerson = ref(null);

const shown = computed(() => props.person || lastPerson.value);
const detail = computed(() => props.panel?.staff || shown.value || {});
const meId = computed(() => page.props.auth?.user?.id);
const isSelf = computed(() => !!shown.value && shown.value.id === meId.value);
const canSeeHr = computed(() => {
    const user = page.props.auth?.user;
    return !!user?.is_super_admin || (user?.abilities || []).includes('hr.view');
});

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'activity', label: 'Activity' },
];

const currentRoles = computed(() => shown.value?.roles || []);

const assignable = computed(() => {
    const assigned = new Set(currentRoles.value.map((role) => String(role.id)));
    const extras = [{ id: 'super_admin', name: 'Super Admin', system: true }];
    const catalog = [...extras, ...props.roles.filter((role) => role.is_active !== false)];
    const q = roleQuery.value.trim().toLowerCase();

    return catalog.filter((role) => {
        if (assigned.has(String(role.id))) {
            return false;
        }
        if (q && !String(role.name).toLowerCase().includes(q)) {
            return false;
        }
        return true;
    });
});

const dialogMeta = computed(() => {
    const map = {
        disable: {
            title: 'Disable this account',
            description: shown.value?.is_super
                ? 'They will be signed out everywhere. Acting on another Super Admin is logged as a high-trust change.'
                : 'They will be signed out everywhere and cannot sign in until reinstated.',
            confirmLabel: 'Disable',
            tone: 'danger',
        },
        reinstate: {
            title: 'Reinstate access',
            description: 'They can sign in again with their existing password.',
            confirmLabel: 'Reinstate',
            tone: 'default',
        },
        revoke: {
            title: 'Revoke invite',
            description: 'The pending invite is removed. They never become active staff.',
            confirmLabel: 'Revoke',
            tone: 'danger',
        },
        remove: {
            title: 'Remove this staff account',
            description: 'Type their name to confirm. Sessions are invalidated immediately.',
            confirmLabel: 'Remove',
            tone: 'danger',
        },
    };
    return map[dialog.value] || { title: 'Confirm', description: '', confirmLabel: 'Confirm' };
});

watch(
    () => props.person?.id,
    (id, previous) => {
        if (props.person) {
            lastPerson.value = props.person;
        }
        if (id === previous) {
            return;
        }
        tab.value = 'overview';
        menu.value = false;
        assignOpen.value = false;
        roleQuery.value = '';
        dialog.value = null;
    },
);

watch(
    () => props.person,
    (person) => {
        if (person) {
            lastPerson.value = person;
        }
    },
);

const ask = (type) => {
    menu.value = false;
    dialog.value = type;
};

const assignRole = async (role) => {
    if (!shown.value) {
        return;
    }
    const person = shown.value;
    const previousRoles = [...(person.roles || [])];
    assignOpen.value = false;
    roleQuery.value = '';
    assigning.value = true;

    emit('updated', {
        ...person,
        roles: [...previousRoles, { id: role.id, key: role.slug || role.id, name: role.name, system: !!role.system }],
        is_super: role.id === 'super_admin' ? true : person.is_super,
    });

    try {
        const payload = role.id === 'super_admin' ? { role: 'super_admin' } : { role_id: role.id };
        const { data } = await axios.post(route('admin.staff.roles.store', person.id), payload);
        toast(data.toast);
        if (data.staff) {
            emit('updated', { ...person, ...data.staff });
        }
        emit('refresh');
    } catch (error) {
        emit('updated', { ...person, roles: previousRoles, is_super: previousRoles.some((item) => item.id === 'super_admin') });
        toast({
            type: 'error',
            title: 'Couldn’t assign',
            message: error.response?.data?.message || error.response?.data?.errors?.role?.[0] || 'Try again.',
        });
    } finally {
        assigning.value = false;
    }
};

const removeRole = async (role) => {
    if (!shown.value || (isSelf.value && role.id === 'super_admin')) {
        return;
    }
    const person = shown.value;
    const previousRoles = [...(person.roles || [])];
    assigning.value = true;
    emit('updated', {
        ...person,
        roles: previousRoles.filter((item) => String(item.id) !== String(role.id)),
        is_super: role.id === 'super_admin' ? false : person.is_super,
    });

    try {
        const { data } = await axios.delete(route('admin.staff.roles.destroy', [person.id, role.id]));
        toast(data.toast);
        if (data.staff) {
            emit('updated', { ...person, ...data.staff });
        }
        emit('refresh');
    } catch (error) {
        emit('updated', {
            ...person,
            roles: previousRoles,
            is_super: previousRoles.some((item) => item.id === 'super_admin'),
        });
        toast({
            type: 'error',
            title: 'Couldn’t remove',
            message: error.response?.data?.message || error.response?.data?.errors?.role?.[0] || 'Try again.',
        });
    } finally {
        assigning.value = false;
    }
};

const runDialog = async ({ reason, confirmation }) => {
    if (!shown.value || !dialog.value) {
        return;
    }
    busy.value = true;
    const id = shown.value.id;
    try {
        const routes = {
            disable: route('admin.staff.disable', id),
            reinstate: route('admin.staff.reinstate', id),
            revoke: route('admin.staff.revoke', id),
            remove: route('admin.staff.destroy', id),
        };
        const { data } = await axios.post(routes[dialog.value], { reason, confirmation });
        toast(data.toast);
        if (data.deleted_id) {
            emit('deleted', data.deleted_id);
        } else if (data.staff) {
            emit('updated', { ...shown.value, ...data.staff });
            emit('refresh');
        }
        dialog.value = null;
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

const onDocClick = (event) => {
    if (menu.value && menuRoot.value && !menuRoot.value.contains(event.target)) {
        menu.value = false;
    }
    if (assignOpen.value && assignRoot.value && !assignRoot.value.contains(event.target)) {
        assignOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));

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
.menu-item {
    @apply block w-full px-3 py-2 text-left text-[13px] font-semibold text-ink/70 hover:bg-pale;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>

<style>
.admin-pane-enter-active,
.admin-pane-leave-active {
    transition:
        opacity 0.2s cubic-bezier(0.32, 0.72, 0, 1),
        transform 0.22s cubic-bezier(0.32, 0.72, 0, 1);
}
.admin-pane-enter-from,
.admin-pane-leave-to {
    opacity: 0;
    transform: translate3d(0, 8px, 0);
}
</style>
