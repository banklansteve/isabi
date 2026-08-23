<template>
    <Head title="Users" />

    <AdminChrome title="Users" :eyebrow="`${list.total.value.toLocaleString()} artisans`" />
        <div class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="list.q.value"
                        type="search"
                        placeholder="Search name, email, business…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <div class="no-scrollbar flex gap-1.5 overflow-x-auto lg:justify-end">
                    <select v-model="plan" class="chip-select" :class="plan ? 'chip-select--on' : ''">
                        <option value="">All plans</option>
                        <option value="Free">Free</option>
                        <option value="Pay-as-you-go">Pay-as-you-go</option>
                        <option value="Annual">Annual</option>
                    </select>
                    <select v-model="statusFilter" class="chip-select" :class="statusFilter ? 'chip-select--on' : ''">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="unverified">Unverified</option>
                    </select>
                    <select v-model="trade" class="chip-select" :class="trade ? 'chip-select--on' : ''">
                        <option value="">All trades</option>
                        <option v-for="item in trades" :key="item" :value="item">{{ item }}</option>
                    </select>
                    <select v-model="state" class="chip-select" :class="state ? 'chip-select--on' : ''">
                        <option value="">All locations</option>
                        <option v-for="item in states" :key="item" :value="item">{{ item }}</option>
                    </select>
                    <select v-model="list.sort.value" class="chip-select">
                        <option value="date_desc">Newest first</option>
                        <option value="active_desc">Most active</option>
                        <option value="jobs_desc">Most jobs logged</option>
                        <option value="revenue_desc">Most revenue</option>
                        <option value="name_asc">Name A–Z</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 flex flex-col gap-3 border-t border-ink/[0.05] pt-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 flex-1">
                    <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Signed up</p>
                    <AdminRangePicker :range="range" />
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <button
                        v-for="view in saved.views.value"
                        :key="view.id"
                        type="button"
                        class="group inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-[12px] font-semibold transition-all duration-150"
                        :class="saved.isActive.value === view.id ? 'bg-base-action text-white shadow-sm' : 'bg-[#F4F6FA] text-ink/50 hover:bg-tint hover:text-deep'"
                        @click="applyView(view)"
                    >
                        {{ view.name }}
                        <i
                            class="ti ti-x text-[11px] opacity-50 transition-opacity group-hover:opacity-100"
                            aria-hidden="true"
                            @click.stop="saved.remove(view.id)"
                        />
                    </button>
                    <form class="flex items-center gap-1.5" @submit.prevent="saved.save(saved.name.value)">
                        <input
                            v-model="saved.name.value"
                            type="text"
                            placeholder="Save this view…"
                            class="w-32 rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-1.5 text-[12px] font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white sm:w-40"
                        />
                        <button type="submit" class="rounded-full bg-pale px-3 py-1.5 text-[12px] font-bold text-deep transition-colors duration-150 hover:bg-tint">
                            Save
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                title="No artisans match"
                description="Try a different search, status, or date range."
                icon="ti ti-users"
            />
            <template v-else>
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[960px] text-left text-[13px]">
                        <thead class="border-b border-ink/[0.06] bg-pale/60 text-[11px] font-bold uppercase tracking-wide text-ink/40">
                            <tr>
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox" :checked="allVisibleSelected" class="rounded border-ink/20 text-base-action" @change="togglePage" />
                                </th>
                                <th class="px-3 py-3">Name</th>
                                <th class="px-3 py-3">Trade</th>
                                <th class="px-3 py-3">Location</th>
                                <th class="px-3 py-3">Plan</th>
                                <th class="px-3 py-3 text-right">Jobs</th>
                                <th class="px-3 py-3 text-right">Reviews</th>
                                <th class="px-3 py-3">Joined</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-4 py-3 text-right"> </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/[0.06]">
                            <tr
                                v-for="person in list.pageItems.value"
                                :key="person.id"
                                class="group cursor-pointer transition-colors duration-150"
                                :class="open?.id === person.id ? 'bg-tint/80' : 'hover:bg-pale/80'"
                                @click="openUser(person)"
                            >
                                <td class="px-4 py-3" @click.stop>
                                    <input v-model="selected" type="checkbox" :value="person.id" class="rounded border-ink/20 text-base-action" />
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                                            <img v-if="person.avatar_url" :src="person.avatar_url" alt="" class="h-full w-full object-cover" />
                                            <span v-else>{{ initials(person.name) }}</span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-ink">{{ person.business_name }}</p>
                                            <p class="truncate text-[12px] text-ink/40">{{ person.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 font-medium text-ink/60">{{ person.trade || '—' }}</td>
                                <td class="px-3 py-3 font-medium text-ink/60">{{ person.state || '—' }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="planClass(person)">{{ person.plan }}</span>
                                </td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums text-ink">{{ person.jobs }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums text-ink">{{ person.reviews }}</td>
                                <td class="px-3 py-3 font-medium text-ink/50">{{ person.joined }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="statusClass(person)">{{ statusLabel(person) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <div class="flex justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" class="icon-btn" title="View" @click="openUser(person)">
                                            <i class="ti ti-eye" aria-hidden="true" />
                                        </button>
                                        <button type="button" class="icon-btn" title="Message" @click="startRowAction('message', person)">
                                            <i class="ti ti-mail" aria-hidden="true" />
                                        </button>
                                        <button
                                            type="button"
                                            class="icon-btn text-red-500"
                                            :title="person.suspended ? 'Reinstate' : 'Suspend'"
                                            @click="startRowAction(person.suspended ? 'reinstate' : 'suspend', person)"
                                        >
                                            <i :class="person.suspended ? 'ti ti-player-play' : 'ti ti-ban'" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <ul class="divide-y divide-ink/[0.06] lg:hidden">
                    <li v-for="person in list.pageItems.value" :key="person.id">
                        <div
                            class="flex items-center gap-3 px-4 py-3.5 transition-colors duration-150"
                            :class="open?.id === person.id ? 'bg-tint/80' : ''"
                        >
                            <input v-model="selected" type="checkbox" :value="person.id" class="rounded border-ink/20 text-base-action" @click.stop />
                            <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left" @click="openUser(person)">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-xs font-bold text-deep">
                                    <img v-if="person.avatar_url" :src="person.avatar_url" alt="" class="h-full w-full object-cover" />
                                    <span v-else>{{ initials(person.name) }}</span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-ink">{{ person.business_name }}</p>
                                    <p class="truncate text-[13px] font-medium text-ink/45">
                                        {{ [person.trade, person.state].filter(Boolean).join(' · ') || '—' }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-[11px] font-bold uppercase tracking-wide"
                                    :class="mobilePill(person)"
                                >
                                    {{ mobileLabel(person) }}
                                </span>
                                <i class="ti ti-chevron-right text-ink/25" aria-hidden="true" />
                            </button>
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

        <Transition name="admin-dock">
            <div
                v-if="selected.length"
                class="fixed inset-x-4 bottom-20 z-40 mx-auto flex max-w-xl items-center justify-between gap-3 rounded-2xl bg-ink px-4 py-3 text-white shadow-premium-ink lg:bottom-6"
            >
                <p class="text-[13px] font-semibold">{{ selected.length }} selected</p>
                <div class="flex gap-2">
                    <button type="button" class="rounded-lg bg-white/10 px-3 py-1.5 text-[12px] font-bold transition-colors duration-150 hover:bg-white/15" @click="bulk = 'message'">Message</button>
                    <button type="button" class="rounded-lg bg-white/10 px-3 py-1.5 text-[12px] font-bold transition-colors duration-150 hover:bg-white/15" @click="exportSelected">Export</button>
                    <button type="button" class="rounded-lg bg-red-500/90 px-3 py-1.5 text-[12px] font-bold transition-colors duration-150 hover:bg-red-500" @click="bulk = 'suspend'">Suspend</button>
                </div>
            </div>
        </Transition>

        <AdminUserDrawer
            :person="open"
            :panel="panel"
            :loading="panelLoading"
            @close="closeUser"
            @updated="onUpdated"
            @deleted="onDeleted"
            @refresh="loadPanel"
        />

        <AdminConfirmDialog
            :open="!!bulk"
            :title="bulk === 'message' ? 'Message selected artisans' : 'Suspend selected artisans'"
            :description="bulk === 'message' ? 'Sends in-app and email to everyone checked.' : 'They will be signed out and blocked from signing in.'"
            :confirm-label="bulk === 'message' ? 'Send' : 'Suspend'"
            :tone="bulk === 'suspend' ? 'danger' : 'default'"
            :require-reason="true"
            :processing="busy"
            @close="bulk = null"
            @confirm="runBulk"
        >
            <template v-if="bulk === 'message'">
                <input v-model="bulkSubject" type="text" placeholder="Subject" class="mt-4 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium" />
                <textarea v-model="bulkBody" rows="4" placeholder="Message" class="mt-2 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium" />
            </template>
        </AdminConfirmDialog>

        <AdminConfirmDialog
            :open="!!rowAction"
            :title="rowDialogMeta.title"
            :description="rowDialogMeta.description"
            :confirm-label="rowDialogMeta.confirmLabel"
            :tone="rowDialogMeta.tone"
            :require-reason="true"
            :processing="busy"
            @close="rowAction = null"
            @confirm="runRowAction"
        >
            <template v-if="rowAction?.type === 'message'">
                <input v-model="rowSubject" type="text" placeholder="Subject" class="mt-4 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium" />
                <textarea v-model="rowBody" rows="4" placeholder="Message" class="mt-2 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium" />
            </template>
        </AdminConfirmDialog>
</template>

<script setup>
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import AdminUserDrawer from '@/Components/Admin/AdminUserDrawer.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import { useSavedViews } from '@/Composables/useSavedViews';
import { toast } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, inject, ref, watch } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    trades: { type: Array, default: () => [] },
    states: { type: Array, default: () => [] },
    opened_id: { type: Number, default: null },
});

const tabQuery = inject('adminTabQuery', ref({}));
const { status } = useAdminTabs({ status: '' });
const range = useDateRange('all');
const plan = ref('');
const trade = ref('');
const state = ref('');
const statusFilter = ref(status.value || '');
const rows = ref([...props.users]);
const selected = ref([]);
const open = ref(null);
const panel = ref(null);
const panelLoading = ref(false);
const bulk = ref(null);
const bulkSubject = ref('');
const bulkBody = ref('');
const rowAction = ref(null);
const rowSubject = ref('');
const rowBody = ref('');
const busy = ref(false);
let panelSeq = 0;

const list = useClientList(
    () => rows.value.filter((person) => {
        if (statusFilter.value === 'suspended' && !person.suspended) return false;
        if (statusFilter.value === 'active' && person.suspended) return false;
        if (statusFilter.value === 'unverified' && (person.verified || person.suspended)) return false;
        if (plan.value && person.plan !== plan.value) return false;
        if (trade.value && person.trade !== trade.value) return false;
        if (state.value && person.state !== state.value) return false;
        return range.matches(person.created_iso);
    }),
    {
        perPage: 24,
        searchFields: ['name', 'email', 'business_name', 'trade', 'state'],
        sort: 'date_desc',
        sortMap: {
            date: 'created_iso',
            name: 'business_name',
            jobs: 'jobs',
            revenue: 'revenue',
            active: 'last_active_iso',
        },
    },
);

const saved = useSavedViews('isabi:admin-user-views', () => ({
    q: list.q.value,
    plan: plan.value,
    trade: trade.value,
    state: state.value,
    status: statusFilter.value,
    sort: list.sort.value,
    range: range.preset.value,
    customFrom: range.customFrom.value,
    customTo: range.customTo.value,
}));

watch(status, (value) => {
    const next = value || '';
    if (statusFilter.value !== next) {
        statusFilter.value = next;
    }
});

watch(statusFilter, (value) => {
    const current = String(tabQuery.value?.status || '');
    if (current === value) {
        return;
    }

    const next = { ...(tabQuery.value || {}) };
    if (value) {
        next.status = value;
    } else {
        delete next.status;
    }
    tabQuery.value = next;
    replaceListUrl();
});

watch([plan, trade, state, statusFilter, () => range.preset.value], () => {
    list.page.value = 1;
    selected.value = [];
});

watch(
    () => props.users,
    (value) => {
        rows.value = [...value];
    },
);

const allVisibleSelected = computed(
    () => list.pageItems.value.length > 0 && list.pageItems.value.every((person) => selected.value.includes(person.id)),
);

const rowDialogMeta = computed(() => {
    const type = rowAction.value?.type;
    if (type === 'message') {
        return {
            title: `Message ${rowAction.value.person.business_name}`,
            description: 'Sends in-app and email to this one account.',
            confirmLabel: 'Send',
            tone: 'default',
        };
    }
    if (type === 'reinstate') {
        return {
            title: 'Reinstate account',
            description: 'They can sign in again.',
            confirmLabel: 'Reinstate',
            tone: 'default',
        };
    }
    return {
        title: 'Suspend account',
        description: 'They will be signed out and blocked from signing in.',
        confirmLabel: 'Suspend',
        tone: 'danger',
    };
});

const togglePage = (event) => {
    const ids = list.pageItems.value.map((person) => person.id);
    if (event.target.checked) {
        selected.value = [...new Set([...selected.value, ...ids])];
    } else {
        selected.value = selected.value.filter((id) => !ids.includes(id));
    }
};

const applyView = (view) => {
    const filters = view.filters || {};
    list.q.value = filters.q || '';
    plan.value = filters.plan || '';
    trade.value = filters.trade || '';
    state.value = filters.state || '';
    statusFilter.value = filters.status || '';
    list.sort.value = filters.sort || 'date_desc';
    if (filters.range) {
        range.apply(filters.range);
    }
    if (filters.customFrom) {
        range.customFrom.value = filters.customFrom;
    }
    if (filters.customTo) {
        range.customTo.value = filters.customTo;
    }
};

const listPath = (path) => {
    try {
        const url = new URL(path, window.location.origin);
        if (statusFilter.value) {
            url.searchParams.set('status', statusFilter.value);
        } else {
            url.searchParams.delete('status');
        }
        return url.pathname + url.search;
    } catch {
        return path;
    }
};

const replaceListUrl = (path = window.location.pathname) => {
    window.history.replaceState(window.history.state, '', listPath(path));
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
        const { data } = await axios.get(route('admin.users.show', id), {
            headers: { Accept: 'application/json' },
        });
        if (seq !== panelSeq || open.value?.id !== id) {
            return;
        }
        panel.value = data;
        if (data.user) {
            onUpdated({ ...open.value, ...data.user });
        }
    } catch {
        if (seq === panelSeq) {
            toast({ type: 'error', title: 'Couldn’t load', message: 'The account details did not load.' });
        }
    } finally {
        if (seq === panelSeq) {
            panelLoading.value = false;
        }
    }
};

const openUser = async (person) => {
    open.value = person;
    panel.value = null;
    replaceListUrl(route('admin.users.show', person.id));
    await loadPanel();
};

const closeUser = () => {
    open.value = null;
    panel.value = null;
    replaceListUrl(route('admin.users.index'));
};

watch(
    () => props.opened_id,
    (id) => {
        if (!id) {
            return;
        }
        const person = rows.value.find((row) => row.id === id);
        if (person) {
            openUser(person);
        }
    },
    { immediate: true },
);

const onDeleted = (id) => {
    rows.value = rows.value.filter((row) => row.id !== id);
    selected.value = selected.value.filter((item) => item !== id);
    closeUser();
};

const startRowAction = (type, person) => {
    rowAction.value = { type, person };
    if (type === 'message') {
        rowSubject.value = 'A note from Isabi';
        rowBody.value = `Hi {{first_name}}, `;
    }
};

const exportSelected = async () => {
    try {
        const { data } = await axios.post(route('admin.users.bulk-export'), { ids: selected.value }, { responseType: 'blob' });
        const url = URL.createObjectURL(data);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'isabi-users.csv';
        link.click();
        URL.revokeObjectURL(url);
    } catch {
        toast({ type: 'error', title: 'Export failed', message: 'Could not download the CSV.' });
    }
};

const runBulk = async ({ reason }) => {
    busy.value = true;
    try {
        if (bulk.value === 'suspend') {
            const { data } = await axios.post(route('admin.users.bulk-suspend'), { ids: selected.value, reason });
            toast(data.toast);
            rows.value = rows.value.map((row) =>
                selected.value.includes(row.id) ? { ...row, suspended: true } : row,
            );
        } else {
            const { data } = await axios.post(route('admin.users.bulk-message'), {
                ids: selected.value,
                reason,
                subject: bulkSubject.value,
                body: bulkBody.value,
                channels: ['in_app', 'email'],
            });
            toast(data.toast);
        }
        selected.value = [];
        bulk.value = null;
    } catch (error) {
        toast({ type: 'error', title: 'Couldn’t complete', message: error.response?.data?.message || 'Try again.' });
    } finally {
        busy.value = false;
    }
};

const runRowAction = async ({ reason }) => {
    if (!rowAction.value) {
        return;
    }
    const { type, person } = rowAction.value;
    busy.value = true;
    try {
        if (type === 'message') {
            const { data } = await axios.post(route('admin.messaging.store'), {
                audience: 'users',
                title: rowSubject.value,
                subject: rowSubject.value,
                body: rowBody.value,
                channels: ['in_app', 'email'],
                segment: { user_id: person.id },
                action: 'send',
            });
            toast(data.toast);
        } else {
            const url = type === 'reinstate' ? route('admin.users.reinstate', person.id) : route('admin.users.suspend', person.id);
            const { data } = await axios.post(url, { reason });
            toast(data.toast);
            if (data.user) {
                onUpdated(data.user);
            } else {
                onUpdated({ ...person, suspended: type === 'suspend' });
            }
        }
        rowAction.value = null;
    } catch (error) {
        toast({ type: 'error', title: 'Couldn’t complete', message: error.response?.data?.message || 'Try again.' });
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

const planClass = (person) => {
    if (person.plan === 'Annual') return 'bg-emerald-50 text-emerald-700';
    if (person.plan === 'Pay-as-you-go') return 'bg-tint text-deep';
    return 'bg-pale text-ink/45';
};

const statusLabel = (person) => {
    if (person.suspended) return 'Suspended';
    if (!person.verified) return 'Unverified';
    return 'Active';
};

const statusClass = (person) => {
    if (person.suspended) return 'bg-red-50 text-red-600';
    if (!person.verified) return 'bg-amber-50 text-amber-700';
    return 'bg-emerald-50 text-emerald-700';
};

const mobileLabel = (person) => {
    if (person.suspended) return 'Suspended';
    if (!person.verified) return 'Unverified';
    return person.plan;
};

const mobilePill = (person) => {
    if (person.suspended) return 'rounded-full bg-red-50 px-2 py-0.5 text-red-600';
    if (!person.verified) return 'rounded-full bg-amber-50 px-2 py-0.5 text-amber-700';
    if (person.plan === 'Annual') return 'rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700';
    return 'text-ink/40';
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
.icon-btn {
    @apply flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 hover:bg-tint hover:text-deep;
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
.admin-dock-enter-active,
.admin-dock-leave-active {
    transition:
        opacity 0.28s cubic-bezier(0.32, 0.72, 0, 1),
        transform 0.32s cubic-bezier(0.32, 0.72, 0, 1);
}
.admin-dock-enter-from,
.admin-dock-leave-to {
    opacity: 0;
    transform: translate3d(0, 12px, 0);
}
</style>
