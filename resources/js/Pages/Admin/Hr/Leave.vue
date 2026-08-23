<template>
    <Head title="Leave" />

    <AdminChrome title="Leave" :eyebrow="pending.length ? `${pending.length} pending` : 'Approvals'" />

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <FormButton v-if="can.manage || can.leave" variant="primary" icon-left="ti ti-plus" label="Book leave" @click="openLeaveDrawer()" />
            <FormButton v-if="can.leave" variant="secondary" icon-left="ti ti-adjustments" label="Allocate" @click="openAllocateDrawer()" />
        </div>
        <button
            type="button"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-base-action hover:text-base-hover"
            @click="visitAdmin(route('admin.hr.calendar'))"
        >
            <i class="ti ti-calendar-month" aria-hidden="true" />
            Team calendar
        </button>
    </div>

    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Pending approval</h2>
    <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
        <AdminEmpty
            v-if="pending.length === 0"
            title="Nothing waiting"
            description="Book leave for someone, or check upcoming approved time off below."
            icon="ti ti-checks"
        >
            <FormButton v-if="can.manage || can.leave" variant="primary" icon-left="ti ti-plus" label="Book leave" @click="openLeaveDrawer()" />
        </AdminEmpty>
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="req in pending" :key="req.id" class="px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-xs font-bold text-deep">{{ req.staff_initials }}</span>
                        <div class="min-w-0">
                            <button type="button" class="text-sm font-semibold text-ink hover:text-base-action" @click="visitAdmin(route('admin.hr.staff.show', { user: req.user_id, tab: 'leave' }))">
                                {{ req.staff_name }}
                            </button>
                            <p class="mt-0.5 text-xs font-medium text-ink/50">
                                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full" :class="leaveDot(req.leave_color)" />{{ req.leave_type }}</span>
                                · {{ req.start_label }} – {{ req.end_label }} · {{ req.days }}d
                            </p>
                            <p v-if="req.note" class="mt-0.5 text-xs font-medium text-ink/45">{{ req.note }}</p>
                            <p v-if="req.would_go_negative" class="mt-0.5 text-xs font-semibold text-coral-deep">Would put balance at {{ req.remaining_after }} days</p>
                        </div>
                    </div>
                    <div v-if="can.leave" class="flex shrink-0 gap-1.5">
                        <button type="button" class="tap-target rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" @click="openDecision(req, 'approve')">Approve</button>
                        <button type="button" class="tap-target rounded-lg bg-coral-tint px-3 py-1.5 text-xs font-semibold text-coral-deep hover:bg-coral-tint/70" @click="openDecision(req, 'reject')">Decline</button>
                    </div>
                </div>
            </li>
        </ul>
    </div>

    <h2 class="mt-8 text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Upcoming</h2>
    <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
        <p v-if="upcoming.length === 0" class="px-6 py-10 text-center text-sm font-medium text-ink/45">No approved leave on the calendar yet.</p>
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="req in upcoming" :key="req.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                <div class="min-w-0">
                    <button type="button" class="text-sm font-semibold text-ink hover:text-base-action" @click="visitAdmin(route('admin.hr.staff.show', { user: req.user_id, tab: 'leave' }))">
                        {{ req.staff_name }}
                    </button>
                    <p class="mt-0.5 text-xs font-medium text-ink/45">
                        {{ req.leave_type }} · {{ req.start_label }} – {{ req.end_label }} · {{ req.days }}d
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-1.5">
                    <span :class="[pillBase, leaveStatus(req.status).class]">{{ leaveStatus(req.status).label }}</span>
                    <button
                        v-if="can.leave"
                        type="button"
                        class="tap-target rounded-lg bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60 hover:bg-tint"
                        @click="cancelLeave(req)"
                    >
                        Cancel
                    </button>
                </div>
            </li>
        </ul>
    </div>

    <h2 class="mt-8 text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Recent decisions</h2>
    <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
        <p v-if="recent.length === 0" class="px-6 py-10 text-center text-sm font-medium text-ink/45">No decisions yet.</p>
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="req in recent" :key="req.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ink">{{ req.staff_name }}</p>
                    <p class="mt-0.5 text-xs font-medium text-ink/45">{{ req.leave_type }} · {{ req.start_label }} – {{ req.end_label }}<span v-if="req.decided_by_name"> · {{ req.decided_by_name }}</span></p>
                </div>
                <span :class="[pillBase, leaveStatus(req.status).class]">{{ leaveStatus(req.status).label }}</span>
            </li>
        </ul>
    </div>

    <AdminDrawer :open="leaveOpen" title="Book leave" eyebrow="On behalf of staff" @close="leaveOpen = false">
        <form class="space-y-4" @submit.prevent="submitLeave">
            <FormSelect id="lv-staff" v-model="leaveForm.user_id" label="Staff member" icon="ti ti-user" :options="staffOptions" searchable :error="leaveForm.errors.user_id" />
            <FormSelect id="lv-type" v-model="leaveForm.leave_type_id" label="Leave type" icon="ti ti-category" :options="leaveTypeOptions" :error="leaveForm.errors.leave_type_id" />
            <div class="grid gap-4 sm:grid-cols-2">
                <FormTextInput id="lv-start" v-model="leaveForm.start_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Start date" icon="ti ti-calendar" :error="leaveForm.errors.start_date" required />
                <FormTextInput id="lv-end" v-model="leaveForm.end_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="End date" icon="ti ti-calendar" :error="leaveForm.errors.end_date" required />
            </div>
            <p v-if="leaveDayCount" class="text-xs font-semibold text-ink/50">{{ leaveDayCount }} day{{ leaveDayCount === 1 ? '' : 's' }}</p>
            <FormTextarea id="lv-note" v-model="leaveForm.note" label="Note (optional)" :error="leaveForm.errors.note" />
            <label v-if="can.leave" class="flex items-center gap-2 text-sm font-medium text-ink/70">
                <input v-model="leaveForm.approve_now" type="checkbox" class="rounded border-ink/30" />
                Book as approved
            </label>
            <label v-if="can.leave && leaveForm.approve_now" class="flex items-center gap-2 text-sm font-medium text-ink/70">
                <input v-model="leaveForm.override_negative" type="checkbox" class="rounded border-ink/30" />
                Override if this exceeds the balance
            </label>
            <p v-if="leaveForm.errors.leave" class="text-sm font-medium text-coral-deep">{{ leaveForm.errors.leave }}</p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <FormButton type="button" variant="secondary" label="Cancel" @click="leaveOpen = false" />
                <FormButton type="submit" variant="primary" :label="leaveForm.approve_now ? 'Book leave' : 'Submit for approval'" :loading="leaveForm.processing" loading-label="Saving…" />
            </div>
        </form>
    </AdminDrawer>

    <AdminDrawer :open="allocateOpen" title="Allocate leave" :eyebrow="`${year} entitlement`" @close="allocateOpen = false">
        <form class="space-y-4" @submit.prevent="submitAllocation">
            <FormSelect id="al-staff" v-model="allocateForm.user_id" label="Staff member" icon="ti ti-user" :options="staffOptions" searchable :error="allocateForm.errors.user_id" />
            <FormSelect id="al-type" v-model="allocateForm.leave_type_id" label="Leave type" icon="ti ti-category" :options="leaveTypeOptions" :error="allocateForm.errors.leave_type_id" />
            <div class="grid gap-4 sm:grid-cols-2">
                <FormTextInput id="al-year" v-model="allocateForm.year" type="number" label="Year" icon="ti ti-calendar" :error="allocateForm.errors.year" required />
                <FormTextInput id="al-days" v-model="allocateForm.allowance_days" type="number" label="Allowance (days)" icon="ti ti-hash" :error="allocateForm.errors.allowance_days" required />
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <FormButton type="button" variant="secondary" label="Cancel" @click="allocateOpen = false" />
                <FormButton type="submit" variant="primary" label="Save" :loading="allocateForm.processing" loading-label="Saving…" />
            </div>
        </form>
    </AdminDrawer>

    <AppModal :show="decisionOpen" :title="decisionMode === 'approve' ? 'Approve leave?' : 'Decline leave?'" :icon="decisionMode === 'approve' ? 'ti ti-check' : 'ti ti-x'" :icon-tone="decisionMode === 'approve' ? 'base' : 'coral'" @close="decisionOpen = false">
        <form class="space-y-4" @submit.prevent="submitDecision">
            <p v-if="decisionTarget" class="text-sm font-medium text-ink/60">{{ decisionTarget.staff_name }} · {{ decisionTarget.leave_type }} · {{ decisionTarget.days }}d</p>
            <div v-if="decisionMode === 'approve' && decisionTarget?.would_go_negative" class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                This will put the balance negative. Tick to override — it will be logged.
                <label class="mt-2 flex items-center gap-2 text-xs font-semibold">
                    <input v-model="decisionForm.override_negative" type="checkbox" class="rounded border-ink/30" />
                    Override negative balance
                </label>
            </div>
            <FormTextarea id="ldc-comment" v-model="decisionForm.comment" label="Comment (optional)" :error="decisionForm.errors.comment" />
            <p v-if="decisionForm.errors.leave" class="text-sm font-medium text-coral-deep">{{ decisionForm.errors.leave }}</p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <FormButton type="button" variant="secondary" label="Cancel" @click="decisionOpen = false" />
                <FormButton type="submit" :variant="decisionMode === 'approve' ? 'primary' : 'accent'" :label="decisionMode === 'approve' ? 'Approve' : 'Decline'" :loading="decisionForm.processing" />
            </div>
        </form>
    </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { leaveDotClass, leaveStatusMeta, pillBase } from '@/utils/hrStatus';
import { visitAdmin } from '@/utils/adminVisit';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    pending: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    staff: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    year: { type: Number, default: () => new Date().getFullYear() },
    can: { type: Object, default: () => ({}) },
});

const DATE_MIN = '1950-01-01';
const DATE_MAX = '2100-12-31';
const today = () => new Date().toISOString().slice(0, 10);
const isValidDate = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value || '') && value >= DATE_MIN && value <= DATE_MAX;

const leaveDot = (token) => leaveDotClass(token);
const leaveStatus = (status) => leaveStatusMeta(status);
const staffOptions = computed(() => props.staff.map((person) => ({ value: person.id, label: person.name })));
const leaveTypeOptions = computed(() => props.types.map((type) => ({ value: type.id, label: type.name })));

const leaveOpen = ref(false);
const leaveForm = useForm({
    user_id: '',
    leave_type_id: '',
    start_date: today(),
    end_date: today(),
    note: '',
    approve_now: true,
    override_negative: false,
});

const openLeaveDrawer = () => {
    leaveForm.reset();
    leaveForm.clearErrors();
    leaveForm.start_date = today();
    leaveForm.end_date = today();
    leaveForm.approve_now = !!props.can.leave;
    leaveOpen.value = true;
};

const leaveDayCount = computed(() => {
    if (!isValidDate(leaveForm.start_date) || !isValidDate(leaveForm.end_date)) return 0;
    const start = new Date(leaveForm.start_date);
    const end = new Date(leaveForm.end_date);
    if (end < start) return 0;
    return Math.round((end - start) / 86400000) + 1;
});

const submitLeave = () => {
    if (!leaveForm.user_id) {
        leaveForm.setError('user_id', 'Choose a staff member.');
        return;
    }
    leaveForm.post(route('admin.hr.leave.store', leaveForm.user_id), {
        preserveScroll: true,
        onSuccess: () => { leaveOpen.value = false; },
    });
};

const allocateOpen = ref(false);
const allocateForm = useForm({
    user_id: '',
    leave_type_id: '',
    allowance_days: 20,
    year: props.year,
});

const openAllocateDrawer = () => {
    allocateForm.reset();
    allocateForm.clearErrors();
    allocateForm.year = props.year;
    allocateForm.allowance_days = 20;
    allocateOpen.value = true;
};

const submitAllocation = () => {
    if (!allocateForm.user_id) {
        allocateForm.setError('user_id', 'Choose a staff member.');
        return;
    }
    allocateForm.post(route('admin.hr.leave.allocate', allocateForm.user_id), {
        preserveScroll: true,
        onSuccess: () => { allocateOpen.value = false; },
    });
};

const decisionOpen = ref(false);
const decisionMode = ref('approve');
const decisionTarget = ref(null);
const decisionForm = useForm({ comment: '', override_negative: false });

const openDecision = (req, mode) => {
    decisionTarget.value = req;
    decisionMode.value = mode;
    decisionForm.reset();
    decisionForm.clearErrors();
    decisionOpen.value = true;
};

const submitDecision = () => {
    const routeName = decisionMode.value === 'approve' ? 'admin.hr.leave.approve' : 'admin.hr.leave.reject';
    decisionForm.post(route(routeName, decisionTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { decisionOpen.value = false; },
    });
};

const cancelLeave = (req) => router.post(route('admin.hr.leave.cancel', req.id), {}, { preserveScroll: true, showProgress: false });
</script>
