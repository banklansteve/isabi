<template>
    <Head title="Discipline" />

    <AdminChrome title="Discipline" :eyebrow="eyebrow" />

    <div class="mb-4 grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Open</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-coral-deep">{{ stats.open }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Monitoring</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-amber-600">{{ stats.monitoring }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">Closed</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-ink">{{ stats.closed }}</p>
        </div>
    </div>

    <form class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4" @submit.prevent="applyFilters">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Search staff or summary…"
                    class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                />
            </div>
            <div class="no-scrollbar flex gap-1.5 overflow-x-auto lg:justify-end">
                <select v-model="form.status" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="">All statuses</option>
                    <option v-for="opt in statuses" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <select v-model="form.type" class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base">
                    <option value="">All types</option>
                    <option v-for="opt in types" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <button
                    type="submit"
                    class="tap-target rounded-full bg-base-action px-4 py-2 text-[13px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    Filter
                </button>
            </div>
        </div>
    </form>

    <div class="mb-4 flex justify-end">
        <FormButton v-if="can.manage" variant="primary" icon-left="ti ti-plus" label="Add record" @click="openCreate()" />
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            v-if="records.length === 0"
            title="No disciplinary records"
            description="Open cases stay on the HR file — including after someone exits."
            icon="ti ti-gavel"
        >
            <FormButton v-if="can.manage" variant="primary" icon-left="ti ti-plus" label="Record the first case" @click="openCreate()" />
        </AdminEmpty>
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="record in records" :key="record.id">
                <button
                    type="button"
                    class="flex w-full items-start gap-3.5 px-5 py-4 text-left transition-colors hover:bg-pale/70 sm:px-6"
                    @click="openEdit(record)"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-xs font-bold text-deep">
                        {{ record.staff_initials }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-ink">{{ record.staff_name }}</p>
                            <span :class="[pillBase, disciplineStatus(record.status).class]">{{ record.status_label }}</span>
                        </div>
                        <p class="mt-0.5 truncate text-sm font-medium text-ink/70">{{ record.summary }}</p>
                        <p class="mt-1 text-xs font-medium text-ink/45">
                            {{ record.type_label }} · {{ record.occurred_label }}
                            <span v-if="record.follow_up_label"> · Follow-up {{ record.follow_up_label }}</span>
                        </p>
                    </div>
                    <i class="ti ti-chevron-right mt-2 shrink-0 text-ink/25" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </div>

    <AdminDrawer :open="drawerOpen" :title="editing ? 'Update record' : 'Add disciplinary record'" :eyebrow="editing ? editing.staff_name : 'HR file'" @close="drawerOpen = false">
        <form class="space-y-4" @submit.prevent="submitRecord">
            <FormSelect
                v-if="!editing"
                id="dc-staff"
                v-model="recordForm.user_id"
                label="Staff member"
                icon="ti ti-user"
                :options="staffOptions"
                searchable
                :error="recordForm.errors.user_id"
            />
            <FormSelect id="dc-type" v-model="recordForm.type" label="Type" icon="ti ti-alert-triangle" :options="types" :error="recordForm.errors.type" />
            <FormSelect id="dc-status" v-model="recordForm.status" label="Status" icon="ti ti-flag" :options="statuses" :error="recordForm.errors.status" />
            <FormTextInput id="dc-date" v-model="recordForm.occurred_on" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Occurred / issued" icon="ti ti-calendar" :error="recordForm.errors.occurred_on" required />
            <FormTextInput id="dc-summary" v-model="recordForm.summary" label="Summary" icon="ti ti-text-caption" placeholder="Missed two scheduled shifts without notice" :error="recordForm.errors.summary" required />
            <FormTextarea id="dc-details" v-model="recordForm.details" label="Details (optional)" :error="recordForm.errors.details" />
            <FormTextInput id="dc-follow" v-model="recordForm.follow_up_on" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Follow-up date (optional)" icon="ti ti-calendar-event" :error="recordForm.errors.follow_up_on" />
            <FormTextarea id="dc-outcome" v-model="recordForm.outcome" :label="recordForm.status === 'closed' ? 'Outcome' : 'Outcome (optional)'" :error="recordForm.errors.outcome" />
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <FormButton type="button" variant="secondary" label="Cancel" @click="drawerOpen = false" />
                <FormButton type="submit" variant="primary" :label="editing ? 'Save' : 'Add record'" :loading="recordForm.processing" loading-label="Saving…" />
            </div>
        </form>
    </AdminDrawer>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { disciplineStatusMeta, pillBase } from '@/utils/hrStatus';
import { visitAdmin } from '@/utils/adminVisit';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    staff: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const DATE_MIN = '1950-01-01';
const DATE_MAX = '2100-12-31';
const today = () => new Date().toISOString().slice(0, 10);

const eyebrow = computed(() => {
    const open = props.stats.open || 0;
    return open ? `${open} open` : 'Case file';
});

const staffOptions = computed(() => props.staff.map((person) => ({ value: person.id, label: person.name })));
const disciplineStatus = (status) => disciplineStatusMeta(status);

const form = useForm({
    q: props.filters.q || '',
    status: props.filters.status || '',
    type: props.filters.type || '',
});

const applyFilters = () => {
    visitAdmin(route('admin.hr.discipline.index', {
        q: form.q || undefined,
        status: form.status || undefined,
        type: form.type || undefined,
    }), { preserveState: true, replace: true });
};

const drawerOpen = ref(false);
const editing = ref(null);
const recordForm = useForm({
    user_id: '',
    type: 'verbal_warning',
    status: 'open',
    occurred_on: today(),
    summary: '',
    details: '',
    follow_up_on: '',
    outcome: '',
});

const openCreate = () => {
    editing.value = null;
    recordForm.reset();
    recordForm.clearErrors();
    recordForm.type = 'verbal_warning';
    recordForm.status = 'open';
    recordForm.occurred_on = today();
    drawerOpen.value = true;
};

const openEdit = (record) => {
    if (!props.can.manage) {
        return;
    }
    editing.value = record;
    recordForm.clearErrors();
    recordForm.user_id = record.user_id;
    recordForm.type = record.type;
    recordForm.status = record.status;
    recordForm.occurred_on = record.occurred_on;
    recordForm.summary = record.summary;
    recordForm.details = record.details || '';
    recordForm.follow_up_on = record.follow_up_on || '';
    recordForm.outcome = record.outcome || '';
    drawerOpen.value = true;
};

const submitRecord = () => {
    if (editing.value) {
        recordForm.patch(route('admin.hr.discipline.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { drawerOpen.value = false; },
        });
        return;
    }

    if (!recordForm.user_id) {
        recordForm.setError('user_id', 'Choose a staff member.');
        return;
    }

    recordForm.post(route('admin.hr.discipline.store', recordForm.user_id), {
        preserveScroll: true,
        onSuccess: () => { drawerOpen.value = false; },
    });
};
</script>
