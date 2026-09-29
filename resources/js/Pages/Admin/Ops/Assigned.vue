<template>
    <Head :title="pageTitle" />

    <AdminChrome :title="pageTitle" :eyebrow="pageEyebrow" />

    <OpsAssignedTabs />

    <p
        v-if="desk === 'referred'"
        class="mb-4 text-[13px] font-medium leading-relaxed text-ink/50"
    >
        Cases you handed to a colleague or escalated upstairs — status and link back to the subject.
    </p>
    <p
        v-else-if="can_manage"
        class="mb-4 text-[13px] font-medium leading-relaxed text-ink/50"
    >
        Your referred and taken-over cases, plus every ops staff queue — assign, reassign, take over, or resolve.
    </p>
    <p
        v-else
        class="mb-4 text-[13px] font-medium leading-relaxed text-ink/50"
    >
        Cases colleagues handed you — moderation first.
    </p>

    <section
        v-if="can_manage && desk === 'assigned' && growth_duties.length"
        class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4"
    >
        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Quick queues</p>
        <p class="mt-1 text-[13px] font-medium text-ink/50">
            Any ops case can be referred or taken over from Case Desk. Jump into common queues below.
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <Link
                v-for="duty in growth_duties"
                :key="duty.label"
                :href="duty.href"
                class="inline-flex items-center gap-1.5 rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/70 transition-colors hover:bg-tint hover:text-deep"
            >
                {{ duty.label }}
                <i class="ti ti-arrow-right text-[11px]" aria-hidden="true" />
            </Link>
        </div>
    </section>

    <div
        v-if="can_manage && desk === 'assigned'"
        class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4"
    >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex flex-wrap gap-1.5">
                <button
                    type="button"
                    class="rounded-xl px-3.5 py-2 text-[13px] font-semibold transition-colors"
                    :class="scope === 'mine' ? 'bg-base-action text-white shadow-sm' : 'bg-pale text-ink/55 hover:bg-tint hover:text-deep'"
                    @click="setScope('mine')"
                >
                    My desk
                    <span v-if="counts.mine" class="ms-1 tabular-nums opacity-80">{{ counts.mine }}</span>
                </button>
                <button
                    type="button"
                    class="rounded-xl px-3.5 py-2 text-[13px] font-semibold transition-colors"
                    :class="scope === 'all' ? 'bg-base-action text-white shadow-sm' : 'bg-pale text-ink/55 hover:bg-tint hover:text-deep'"
                    @click="setScope('all')"
                >
                    All ops staff
                    <span v-if="counts.team" class="ms-1 tabular-nums opacity-80">{{ counts.team }}</span>
                </button>
            </div>

            <div class="min-w-0 flex-1 lg:max-w-sm lg:ms-auto">
                <FormSelect
                    id="case-desk-staff"
                    v-model="staffFilter"
                    size="sm"
                    searchable
                    placeholder="Jump to one person…"
                    :options="opsStaffOptions"
                    @change="applyStaffFilter"
                />
            </div>
        </div>

        <div
            v-if="scope === 'mine' && (mine_breakdown.referred || mine_breakdown.taken_over || mine_breakdown.escalated)"
            class="mt-3 flex flex-wrap gap-2 border-t border-ink/[0.05] pt-3 text-[12px] font-semibold text-ink/50"
        >
            <span v-if="mine_breakdown.escalated" class="rounded-full bg-coral/10 px-2.5 py-1 text-coral-deep">
                {{ mine_breakdown.escalated }} escalated
            </span>
            <span v-if="mine_breakdown.taken_over" class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-800">
                {{ mine_breakdown.taken_over }} taken over
            </span>
            <span v-if="mine_breakdown.referred" class="rounded-full bg-tint px-2.5 py-1 text-deep">
                {{ mine_breakdown.referred }} referred
            </span>
        </div>
    </div>

    <nav
        class="mb-4 flex gap-1 overflow-x-auto rounded-2xl bg-white p-1.5 shadow-premium ring-1 ring-ink/[0.05]"
        aria-label="Assigned queues"
    >
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="shrink-0 rounded-xl px-3.5 py-2.5 text-center text-[13px] font-semibold transition-colors"
            :class="activeQueue === tab.key ? 'bg-base-action text-white shadow-sm' : 'text-ink/45 hover:bg-pale hover:text-ink'"
            :aria-current="activeQueue === tab.key ? 'page' : undefined"
            @click="selectQueue(tab.key)"
        >
            {{ tab.label }}
            <span
                v-if="counts[tab.countKey] > 0"
                class="ms-1"
                :class="activeQueue === tab.key ? 'text-white/80' : 'text-coral-deep'"
            >
                {{ counts[tab.countKey] }}
            </span>
        </button>
    </nav>

    <div
        class="transition-opacity duration-150"
        :class="queuePending ? 'pointer-events-none opacity-50' : 'opacity-100'"
        :aria-busy="queuePending ? 'true' : undefined"
    >
    <div v-if="items.length" class="space-y-2">
        <article
            v-for="item in items"
            :key="item.id"
            class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        >
            <div class="flex items-start gap-3">
                <span
                    class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="originTone(item.origin)"
                >
                    <i :class="item.icon || 'ti ti-transfer'" class="text-lg" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[15px] font-bold tracking-tight text-ink">{{ item.title }}</p>
                        <span class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/50">
                            {{ item.queue_label }}
                        </span>
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="originBadge(item.origin)"
                        >
                            {{ item.origin_label }}
                        </span>
                        <span
                            v-if="desk === 'referred' && item.viewer_status"
                            class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/55"
                        >
                            {{ item.viewer_status }}
                        </span>
                    </div>
                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/55">{{ item.subtitle }}</p>
                    <p class="mt-2 text-[12px] font-semibold text-ink/35">
                        {{ item.age || item.referred_at }}
                        <span v-if="desk === 'referred' && item.assignee?.name"> · With {{ item.assignee.name }}</span>
                        <span v-else-if="item.referrer?.name"> · From {{ item.referrer.name }}</span>
                        <span v-if="desk !== 'referred' && item.assignee?.name"> · With {{ item.assignee.name }}</span>
                    </p>

                    <button
                        type="button"
                        class="mt-2 text-[12px] font-bold text-base-action hover:text-base-hover"
                        @click="toggleDetails(item.id)"
                    >
                        {{ openDetails === item.id ? 'Hide details' : 'Show details' }}
                    </button>

                    <dl
                        v-if="openDetails === item.id && item.details"
                        class="mt-3 grid gap-2 rounded-xl bg-pale/80 px-3 py-3 text-[12px] sm:grid-cols-2"
                    >
                        <div>
                            <dt class="font-semibold text-ink/40">Artisan</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.artisan || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink/40">Status</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.status || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink/40">Assignee</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.assignee || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink/40">From</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.referrer || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink/40">Received</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.referred_at || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink/40">Acknowledged</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.acknowledged_at || '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-semibold text-ink/40">Note</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ item.details.note || '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <Link
                            :href="item.href"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-base-action px-3 py-2 text-[12px] font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                        >
                            Open case
                            <i class="ti ti-arrow-right" aria-hidden="true" />
                        </Link>

                        <button
                            v-if="desk === 'assigned' && can_manage && Number(item.assignee?.id) !== Number(selfId)"
                            type="button"
                            class="rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/70 transition-colors hover:bg-tint disabled:opacity-50"
                            :disabled="busyId === item.id"
                            @click="takeOver(item)"
                        >
                            Take over
                        </button>

                        <button
                            v-if="desk === 'assigned' && (can_manage || Number(item.assignee?.id) === Number(selfId))"
                            type="button"
                            class="rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/70 transition-colors hover:bg-tint"
                            @click="openReassign(item)"
                        >
                            Assign / reassign
                        </button>

                        <button
                            v-if="desk === 'assigned' && (can_manage || Number(item.assignee?.id) === Number(selfId))"
                            type="button"
                            class="rounded-xl bg-emerald-50 px-3 py-2 text-[12px] font-bold text-emerald-800 transition-colors hover:bg-emerald-100 disabled:opacity-50"
                            :disabled="busyId === item.id"
                            @click="resolve(item)"
                        >
                            Mark resolved
                        </button>
                    </div>
                </div>
            </div>
        </article>
    </div>

    <div v-else class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            :title="emptyTitle"
            :description="emptyDescription"
            icon="ti ti-inbox"
        />
    </div>
    </div>

    <AdminDrawer :open="!!reassigning" title="Assign or reassign" eyebrow="Hand-off" @close="reassigning = null">
        <div class="space-y-4">
            <p class="text-[13px] font-medium text-ink/50">
                {{ reassigning?.title }} — pick who should own it next (ops staff or yourself).
            </p>
            <FormSelect
                id="reassign-assignee"
                v-model="reassignForm.assignee_id"
                label="Assignee"
                icon="ti ti-user-check"
                searchable
                placeholder="Select…"
                :options="assignChoicesOptions"
            />
            <FormTextarea
                id="reassign-note"
                v-model="reassignForm.note"
                label="Note"
                icon="ti ti-notes"
                :rows="3"
                placeholder="Why this hand-off?"
            />
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <FormButton
                    variant="secondary"
                    class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                    label="Cancel"
                    @click="reassigning = null"
                />
                <FormButton
                    variant="primary"
                    class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                    label="Save assignment"
                    :loading="busyId === reassigning?.id"
                    loading-label="Saving…"
                    @click="submitReassign"
                />
            </div>
        </template>
    </AdminDrawer>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import OpsAssignedTabs from '@/Components/Admin/OpsAssignedTabs.vue';
import { toast } from '@/utils/adminRange';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const page = usePage();
const selfId = computed(() => Number(page.props.auth?.user?.id || 0));

const props = defineProps({
    queue: { type: String, default: 'all' },
    desk: { type: String, default: 'assigned' },
    scope: { type: String, default: 'mine' },
    counts: {
        type: Object,
        default: () => ({
            all: 0,
            moderation: 0,
            support: 0,
            patrol: 0,
            jobs: 0,
            escalation: 0,
            mine: 0,
            referred: 0,
            team: 0,
        }),
    },
    mine_breakdown: {
        type: Object,
        default: () => ({ referred: 0, taken_over: 0, escalated: 0 }),
    },
    items: { type: Array, default: () => [] },
    staff: { type: Array, default: () => [] },
    assignable_staff: { type: Array, default: () => [] },
    ops_staff: { type: Array, default: () => [] },
    growth_duties: { type: Array, default: () => [] },
    viewing: { type: Object, default: null },
    can_manage: { type: Boolean, default: false },
});

const staffFilter = ref(props.scope === 'staff' && props.viewing?.id ? String(props.viewing.id) : '');
const busyId = ref(null);
const openDetails = ref(null);
const reassigning = ref(null);
const reassignForm = reactive({ assignee_id: '', note: '' });
const queuePending = ref(false);
const activeQueue = ref(props.queue || 'all');

watch(
    () => props.queue,
    (value) => {
        if (!queuePending.value) {
            activeQueue.value = value || 'all';
        }
    },
);

const pageTitle = computed(() => {
    if (props.desk === 'referred') {
        return 'Referred by me';
    }
    if (!props.can_manage) {
        return 'Assigned to me';
    }
    if (props.scope === 'all') {
        return 'All ops cases';
    }
    if (props.scope === 'staff' && props.viewing?.name) {
        return `${props.viewing.name}’s cases`;
    }
    return 'Case desk';
});

const pageEyebrow = computed(() => {
    if (props.desk === 'referred') {
        const total = Number(props.counts?.referred || props.counts?.all || 0);
        return total ? `${total.toLocaleString()} outbound` : 'Outbound referrals';
    }
    if (!props.can_manage) {
        return 'Referrals';
    }
    const total = Number(props.counts?.all || 0);
    if (props.scope === 'all') {
        return total ? `${total.toLocaleString()} across ops` : 'Ops queues';
    }
    if (props.scope === 'staff') {
        return 'Staff queue';
    }
    const mine = Number(props.counts?.mine || 0);
    return mine ? `${mine.toLocaleString()} on your desk` : 'Your desk';
});

const emptyTitle = computed(() => {
    if (props.desk === 'referred') return 'No outbound referrals yet';
    if (props.scope === 'all') return 'No active cases across ops';
    if (props.scope === 'staff') return 'No active cases for this person';
    return 'Nothing on your desk right now';
});

const emptyDescription = computed(() => {
    if (props.desk === 'referred') {
        return 'When you refer a case to a colleague or escalate to Super Admin, it shows up here with status.';
    }
    return props.can_manage
        ? 'Escalations, takeovers, and referrals land on My desk. All ops staff shows everyone else’s active cases.'
        : 'When a colleague refers a case to you, it shows up here.';
});

const tabs = computed(() => {
    const base = [
        { key: 'all', label: 'All', countKey: 'all' },
        { key: 'moderation', label: 'Moderation', countKey: 'moderation' },
        { key: 'support', label: 'Support', countKey: 'support' },
        { key: 'patrol', label: 'Patrol', countKey: 'patrol' },
        { key: 'jobs', label: 'Jobs', countKey: 'jobs' },
    ];

    if (props.desk === 'assigned' && props.can_manage && props.scope === 'mine') {
        base.splice(1, 0, { key: 'escalation', label: 'Escalations', countKey: 'escalation' });
    }

    if (props.desk === 'referred') {
        base.splice(1, 0, { key: 'escalation', label: 'Escalations', countKey: 'escalation' });
    }

    return base;
});

const opsStaffOptions = computed(() => [
    { value: '', label: 'All ops staff' },
    ...props.ops_staff.map((person) => ({
        value: String(person.id),
        label: person.assigned_count
            ? `${person.name} (${person.assigned_count})`
            : person.name,
    })),
]);

const assignChoices = computed(() => {
    const exclude = Number(reassigning.value?.assignee?.id || 0);
    const pool = props.assignable_staff.length ? props.assignable_staff : props.ops_staff;
    return pool.filter((person) => Number(person.id) !== exclude);
});

const assignChoicesOptions = computed(() =>
    assignChoices.value.map((person) => ({
        value: String(person.id),
        label: person.name,
    })),
);

const scopeParams = () => {
    const params = {
        queue: props.queue || 'all',
        desk: props.desk || 'assigned',
        scope: props.scope || 'mine',
    };
    if (props.desk === 'referred') {
        delete params.scope;
        return params;
    }
    if (props.scope === 'staff' && props.viewing?.id) {
        params.staff = props.viewing.id;
        delete params.scope;
    }
    return params;
};

const tabHref = (key) => {
    const params = scopeParams();
    params.queue = key;
    return route('admin.assigned.index', params);
};

const selectQueue = (key) => {
    if (key === activeQueue.value || queuePending.value) {
        return;
    }

    activeQueue.value = key;
    queuePending.value = true;

    router.get(tabHref(key), {}, {
        only: ['queue', 'desk', 'scope', 'counts', 'mine_breakdown', 'items', 'viewing', 'ops_staff', 'assignable_staff', 'staff', 'growth_duties', 'can_manage'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        showProgress: false,
        onFinish: () => {
            queuePending.value = false;
            activeQueue.value = props.queue || 'all';
        },
    });
};

const setScope = (next) => {
    staffFilter.value = '';
    router.get(
        route('admin.assigned.index', { queue: activeQueue.value || 'all', desk: 'assigned', scope: next }),
        {},
        {
            only: ['queue', 'desk', 'scope', 'counts', 'mine_breakdown', 'items', 'viewing', 'ops_staff', 'assignable_staff', 'staff', 'growth_duties', 'can_manage'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            showProgress: false,
        },
    );
};

const applyStaffFilter = () => {
    if (!staffFilter.value) {
        setScope('all');
        return;
    }
    router.get(
        route('admin.assigned.index', { queue: activeQueue.value || 'all', desk: 'assigned', staff: staffFilter.value }),
        {},
        {
            only: ['queue', 'desk', 'scope', 'counts', 'mine_breakdown', 'items', 'viewing', 'ops_staff', 'assignable_staff', 'staff', 'growth_duties', 'can_manage'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            showProgress: false,
        },
    );
};

const toggleDetails = (id) => {
    openDetails.value = openDetails.value === id ? null : id;
};

const originTone = (origin) => {
    if (origin === 'escalated') return 'bg-coral/10 text-coral-deep';
    if (origin === 'taken_over') return 'bg-amber-50 text-amber-800';
    return 'bg-pale text-ink/45';
};

const originBadge = (origin) => {
    if (origin === 'escalated') return 'bg-coral/10 text-coral-deep';
    if (origin === 'taken_over') return 'bg-amber-50 text-amber-800';
    if (origin === 'reassigned') return 'bg-sky-50 text-sky-800';
    return 'bg-tint text-deep';
};

const takeOver = async (item) => {
    busyId.value = item.id;
    try {
        const { data } = await axios.post(route('admin.referrals.take-over', item.id), {
            note: 'Super Admin took this case over.',
        });
        toast(data.toast);
        router.reload({ only: ['items', 'counts', 'viewing', 'ops_staff', 'mine_breakdown', 'assignable_staff'] });
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t take over',
            message: error.response?.data?.message || 'Try again.',
        });
    } finally {
        busyId.value = null;
    }
};

const openReassign = (item) => {
    reassigning.value = item;
    reassignForm.assignee_id = '';
    reassignForm.note = '';
};

const submitReassign = async () => {
    if (!reassigning.value || !reassignForm.assignee_id || reassignForm.note.trim().length < 4) {
        toast({
            type: 'error',
            title: 'Missing details',
            message: 'Pick an assignee and add a short note.',
        });
        return;
    }

    busyId.value = reassigning.value.id;
    try {
        const { data } = await axios.post(route('admin.referrals.reassign', reassigning.value.id), {
            assignee_id: Number(reassignForm.assignee_id),
            note: reassignForm.note.trim(),
        });
        toast(data.toast);
        reassigning.value = null;
        router.reload({ only: ['items', 'counts', 'viewing', 'ops_staff', 'mine_breakdown', 'assignable_staff'] });
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t assign',
            message: error.response?.data?.message
                || error.response?.data?.errors?.assignee_id?.[0]
                || 'Try again.',
        });
    } finally {
        busyId.value = null;
    }
};

const resolve = async (item) => {
    busyId.value = item.id;
    try {
        const routeName = item.queue === 'escalation'
            ? 'admin.escalations.complete'
            : 'admin.referrals.complete';
        const { data } = await axios.post(route(routeName, item.id));
        toast(data.toast);
        router.reload({ only: ['items', 'counts', 'viewing', 'ops_staff', 'mine_breakdown', 'assignable_staff'] });
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t resolve',
            message: error.response?.data?.message || 'Try again.',
        });
    } finally {
        busyId.value = null;
    }
};
</script>
