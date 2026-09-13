<template>
    <Head title="Careers" />

    <AdminChrome title="Careers" eyebrow="Company" />

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[13px] font-medium text-ink/50">
            Manage hiring vacancies. Open + public roles appear on
            <span class="font-semibold text-ink/70">/careers</span>.
            <span class="font-semibold text-ink/70">{{ open_count }} open</span>
        </p>
        <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover active:scale-[0.98]"
            @click="openCreate"
        >
            <i class="ti ti-plus" aria-hidden="true" />
            Add vacancy
        </button>
    </div>

    <AdminEmpty
        v-if="!rows.length"
        class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
        title="No vacancies yet"
        description="Create a role when you’re ready to hire."
        icon="ti ti-briefcase"
    >
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-base-hover"
            @click="openCreate"
        >
            Add vacancy
        </button>
    </AdminEmpty>

    <div v-else class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[13px]">
                <thead class="border-b border-ink/[0.06] bg-pale/60 text-[11px] font-bold uppercase tracking-wide text-ink/40">
                    <tr>
                        <th class="px-4 py-3">Job title</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Openings</th>
                        <th class="px-4 py-3 text-right">Applicants</th>
                        <th class="px-4 py-3">Posted</th>
                        <th class="px-4 py-3">Closes</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="vacancy in rows"
                        :key="vacancy.id"
                        class="border-b border-ink/[0.04] last:border-0"
                    >
                        <td class="px-4 py-3">
                            <p class="font-bold text-ink">{{ vacancy.title }}</p>
                            <p v-if="vacancy.is_public" class="mt-0.5 text-[11px] font-semibold text-emerald-600">
                                Public
                            </p>
                            <p v-else class="mt-0.5 text-[11px] font-semibold text-ink/35">Internal only</p>
                        </td>
                        <td class="px-4 py-3 font-medium text-ink/55">{{ vacancy.department || '—' }}</td>
                        <td class="px-4 py-3 font-medium text-ink/55">
                            {{ labelOf(meta.employment_types, vacancy.employment_type) }}
                        </td>
                        <td class="px-4 py-3 font-medium text-ink/55">
                            {{ labelOf(meta.work_modes, vacancy.work_mode) }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                :class="statusClass(vacancy.status)"
                            >
                                {{ vacancy.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-ink/70">{{ vacancy.openings }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-ink/70">
                            {{ vacancy.applicants_count }}
                        </td>
                        <td class="px-4 py-3 font-medium text-ink/45">{{ vacancy.published_at || '—' }}</td>
                        <td class="px-4 py-3 font-medium text-ink/45">{{ vacancy.closes_at || '—' }}</td>
                        <td class="px-4 py-3 font-medium text-ink/55">
                            {{ vacancy.hiring_manager_name || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1.5">
                                <button
                                    type="button"
                                    class="rounded-lg bg-pale px-2.5 py-1.5 text-[11px] font-bold text-ink/60 hover:bg-ink/[0.08]"
                                    @click="openEdit(vacancy)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="vacancy.status !== 'paused' && vacancy.status !== 'closed'"
                                    type="button"
                                    class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-[11px] font-bold text-amber-700 hover:bg-amber-100"
                                    @click="makeInactive(vacancy)"
                                >
                                    Inactive
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg bg-red-50 px-2.5 py-1.5 text-[11px] font-bold text-red-600 hover:bg-red-100"
                                    @click="deleting = vacancy"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <AdminDrawer
        :open="drawerOpen"
        size="lg"
        :title="editing ? 'Edit vacancy' : 'Add vacancy'"
        @close="closeDrawer"
    >
        <form class="space-y-5" @submit.prevent="save">
            <FormTextInput
                v-model="form.title"
                label="Job title"
                placeholder="e.g. Trust & Safety Moderator"
                required
                :error="errors.title"
            />

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <FormSelect
                        v-if="departmentOptions.length"
                        v-model="form.department"
                        label="Department"
                        placeholder="Select department"
                        :options="departmentOptions"
                        :error="errors.department"
                    />
                    <FormTextInput
                        v-else
                        v-model="form.department"
                        label="Department"
                        placeholder="e.g. Trust & Safety"
                        :error="errors.department"
                    />
                    <p v-if="departmentOptions.length" class="mt-1.5 text-[11px] font-medium text-ink/35">
                        From HR directories — or type a new one below if missing.
                    </p>
                    <FormTextInput
                        v-if="departmentOptions.length"
                        v-model="form.department_custom"
                        class="mt-2"
                        label="Or enter department"
                        placeholder="New department name"
                    />
                </div>
                <FormTextInput
                    v-model="form.location"
                    label="Location / base"
                    placeholder="e.g. Lagos · Nigeria"
                    :error="errors.location"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <FormSelect
                    v-model="form.employment_type"
                    label="Employment type"
                    required
                    :options="meta.employment_types"
                    :error="errors.employment_type"
                />
                <FormSelect
                    v-model="form.work_mode"
                    label="Work mode"
                    required
                    :options="meta.work_modes"
                    :error="errors.work_mode"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <FormSelect
                    v-model="form.status"
                    label="Status"
                    required
                    :options="meta.statuses"
                    :error="errors.status"
                />
                <FormTextInput
                    v-model.number="form.openings"
                    type="number"
                    label="Openings"
                    hint="Slots to fill"
                    required
                    :error="errors.openings"
                />
                <FormTextInput
                    v-model.number="form.sort_order"
                    type="number"
                    label="Sort order"
                    hint="Lower first"
                    :error="errors.sort_order"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <FormTextInput
                    v-model="form.published_at"
                    type="date"
                    label="Date posted"
                    :error="errors.published_at"
                />
                <FormTextInput
                    v-model="form.closes_at"
                    type="date"
                    label="Closing date"
                    hint="Application deadline"
                    :error="errors.closes_at"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <FormSelect
                    v-model="form.hiring_manager_id"
                    label="Hiring manager / owner"
                    placeholder="Select staff"
                    :options="managerOptions"
                    :error="errors.hiring_manager_id"
                />
                <FormSelect
                    v-model="form.staff_role_id"
                    label="Role / permissions on hire"
                    placeholder="Optional staff role"
                    hint="Pre-select Access & Role Management role"
                    :options="roleOptions"
                    :error="errors.staff_role_id"
                />
            </div>

            <FormTextarea
                v-model="form.summary"
                label="Short summary"
                hint="List card blurb"
                :rows="2"
                required
                :error="errors.summary"
            />
            <FormTextarea
                v-model="form.description"
                label="Job description"
                hint="Responsibilities and what the role involves"
                :rows="6"
                required
                :error="errors.description"
            />
            <FormTextarea
                v-model="form.requirements"
                label="Requirements / qualifications"
                :rows="5"
                :error="errors.requirements"
            />

            <div class="grid gap-3 sm:grid-cols-3">
                <FormTextInput
                    v-model.number="form.salary_min"
                    type="number"
                    label="Salary min (NGN)"
                    :error="errors.salary_min"
                />
                <FormTextInput
                    v-model.number="form.salary_max"
                    type="number"
                    label="Salary max (NGN)"
                    :error="errors.salary_max"
                />
                <label class="flex items-end gap-2 pb-2 text-sm font-semibold text-ink/70">
                    <input
                        v-model="form.salary_is_public"
                        type="checkbox"
                        class="rounded border-ink/20 text-base-action"
                    />
                    Show salary publicly
                </label>
            </div>

            <label class="flex items-start gap-2 text-sm font-semibold text-ink/70">
                <input
                    v-model="form.is_public"
                    type="checkbox"
                    class="mt-0.5 rounded border-ink/20 text-base-action"
                />
                <span>
                    Public visibility
                    <span class="block text-[12px] font-medium text-ink/40">
                        Appear on the public Careers page when status is Open
                    </span>
                </span>
            </label>

            <FormTextarea
                v-model="form.internal_notes"
                label="Internal notes"
                hint="Hiring-process context — not shown to applicants"
                :rows="3"
                :error="errors.internal_notes"
            />

            <FormTextInput
                v-model="form.apply_email"
                type="email"
                label="Notify email (optional)"
                hint="Internal alert address; applications still go through the form"
                :error="errors.apply_email"
            />

            <p v-if="formError" class="text-sm font-medium text-red-600">{{ formError }}</p>
            <div class="flex flex-wrap gap-2 pt-2">
                <FormButton type="submit" variant="primary" :loading="busy">
                    {{ editing ? 'Save changes' : 'Create vacancy' }}
                </FormButton>
                <FormButton type="button" variant="secondary" :disabled="busy" @click="closeDrawer">
                    Cancel
                </FormButton>
            </div>
        </form>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="!!deleting"
        title="Delete this vacancy"
        :description="deleting ? `Remove “${deleting.title}” and all its applications?` : ''"
        confirm-label="Delete vacancy"
        tone="danger"
        :processing="busy"
        @close="deleting = null"
        @confirm="destroy"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { toast } from '@/utils/adminRange';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    vacancies: { type: Array, default: () => [] },
    open_count: { type: Number, default: 0 },
    meta: {
        type: Object,
        default: () => ({
            departments: [],
            hiring_managers: [],
            staff_roles: [],
            statuses: [],
            employment_types: [],
            work_modes: [],
        }),
    },
});

const rows = ref([...props.vacancies]);
const open_count = ref(props.open_count);
const drawerOpen = ref(false);
const editing = ref(null);
const deleting = ref(null);
const busy = ref(false);
const formError = ref('');
const errors = reactive({});

const blank = () => ({
    title: '',
    department: '',
    department_custom: '',
    location: 'Remote · Nigeria',
    employment_type: 'full-time',
    work_mode: 'remote',
    status: 'draft',
    openings: 1,
    summary: '',
    description: '',
    requirements: '',
    salary_min: null,
    salary_max: null,
    salary_currency: 'NGN',
    salary_is_public: false,
    is_public: false,
    internal_notes: '',
    apply_email: 'hello@kraftrack.com',
    apply_url: '',
    sort_order: 100,
    published_at: '',
    closes_at: '',
    hiring_manager_id: '',
    staff_role_id: '',
});

const form = reactive(blank());

const departmentOptions = computed(() =>
    (props.meta.departments || []).map((d) => ({ value: d, label: d })),
);

const managerOptions = computed(() => [
    { value: '', label: 'Unassigned' },
    ...(props.meta.hiring_managers || []),
]);

const roleOptions = computed(() => [
    { value: '', label: 'None' },
    ...(props.meta.staff_roles || []),
]);

const labelOf = (options, value) =>
    (options || []).find((o) => o.value === value)?.label || value || '—';

const statusClass = (status) => {
    const map = {
        open: 'bg-emerald-50 text-emerald-700',
        draft: 'bg-pale text-ink/45',
        paused: 'bg-amber-50 text-amber-700',
        closed: 'bg-ink/[0.06] text-ink/45',
        filled: 'bg-sky-50 text-sky-700',
    };
    return map[status] || map.draft;
};

watch(
    () => props.vacancies,
    (value) => {
        rows.value = [...value];
    },
);

watch(
    () => props.open_count,
    (value) => {
        open_count.value = value;
    },
);

const openCreate = () => {
    editing.value = null;
    Object.assign(form, blank());
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    drawerOpen.value = true;
};

const openEdit = (vacancy) => {
    editing.value = vacancy;
    Object.assign(form, {
        ...blank(),
        title: vacancy.title,
        department: vacancy.department || '',
        department_custom: '',
        location: vacancy.location || '',
        employment_type: vacancy.employment_type || 'full-time',
        work_mode: vacancy.work_mode || 'remote',
        status: vacancy.status || 'draft',
        openings: vacancy.openings || 1,
        summary: vacancy.summary || '',
        description: vacancy.description || '',
        requirements: vacancy.requirements || '',
        salary_min: vacancy.salary_min,
        salary_max: vacancy.salary_max,
        salary_currency: vacancy.salary_currency || 'NGN',
        salary_is_public: !!vacancy.salary_is_public,
        is_public: !!vacancy.is_public,
        internal_notes: vacancy.internal_notes || '',
        apply_email: vacancy.apply_email || '',
        apply_url: vacancy.apply_url || '',
        sort_order: vacancy.sort_order,
        published_at: vacancy.published_at || '',
        closes_at: vacancy.closes_at || '',
        hiring_manager_id: vacancy.hiring_manager_id || '',
        staff_role_id: vacancy.staff_role_id || '',
    });
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    drawerOpen.value = true;
};

const closeDrawer = () => {
    if (busy.value) return;
    drawerOpen.value = false;
};

const applyPayload = (payload) => {
    if (payload?.vacancy) {
        const idx = rows.value.findIndex((r) => r.id === payload.vacancy.id);
        if (idx >= 0) rows.value.splice(idx, 1, payload.vacancy);
        else rows.value.unshift(payload.vacancy);
        open_count.value = rows.value.filter((r) => r.status === 'open').length;
    }
    if (payload?.deleted_id) {
        rows.value = rows.value.filter((r) => r.id !== payload.deleted_id);
        open_count.value = rows.value.filter((r) => r.status === 'open').length;
    }
};

const buildBody = () => {
    const body = { ...form };
    if (body.department_custom?.trim()) {
        body.department = body.department_custom.trim();
    }
    delete body.department_custom;

    body.hiring_manager_id = body.hiring_manager_id || null;
    body.staff_role_id = body.staff_role_id || null;
    body.salary_min = body.salary_min || null;
    body.salary_max = body.salary_max || null;
    body.published_at = body.published_at || null;
    body.closes_at = body.closes_at || null;
    body.apply_url = body.apply_url || null;
    body.apply_email = body.apply_email || null;

    return body;
};

const save = async () => {
    busy.value = true;
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    try {
        const body = buildBody();
        const { data } = editing.value
            ? await axios.patch(route('admin.careers.update', editing.value.id), body)
            : await axios.post(route('admin.careers.store'), body);

        applyPayload(data);
        if (data?.toast) toast(data.toast);
        drawerOpen.value = false;
    } catch (e) {
        const bag = e?.response?.data?.errors;
        if (bag) {
            Object.entries(bag).forEach(([k, v]) => {
                errors[k] = Array.isArray(v) ? v[0] : v;
            });
        }
        formError.value = e?.response?.data?.message || 'Could not save. Check the fields and try again.';
    } finally {
        busy.value = false;
    }
};

const makeInactive = async (vacancy) => {
    busy.value = true;
    try {
        const body = {
            title: vacancy.title,
            department: vacancy.department || null,
            location: vacancy.location || null,
            employment_type: vacancy.employment_type,
            work_mode: vacancy.work_mode,
            status: 'paused',
            openings: vacancy.openings || 1,
            summary: vacancy.summary,
            description: vacancy.description || vacancy.summary,
            requirements: vacancy.requirements || null,
            salary_min: vacancy.salary_min,
            salary_max: vacancy.salary_max,
            salary_currency: vacancy.salary_currency || 'NGN',
            salary_is_public: !!vacancy.salary_is_public,
            is_public: false,
            internal_notes: vacancy.internal_notes || null,
            apply_email: vacancy.apply_email || null,
            apply_url: vacancy.apply_url || null,
            sort_order: vacancy.sort_order,
            published_at: vacancy.published_at || null,
            closes_at: vacancy.closes_at || null,
            hiring_manager_id: vacancy.hiring_manager_id || null,
            staff_role_id: vacancy.staff_role_id || null,
        };
        const { data } = await axios.patch(route('admin.careers.update', vacancy.id), body);
        applyPayload(data);
        if (data?.toast) toast(data.toast);
        else toast({ type: 'success', title: 'Marked inactive', message: 'Status set to Paused.' });
    } catch (e) {
        toast({
            type: 'error',
            title: 'Update failed',
            message: e?.response?.data?.message || 'Could not update vacancy.',
        });
    } finally {
        busy.value = false;
    }
};

const destroy = async () => {
    if (!deleting.value) return;
    busy.value = true;
    try {
        const { data } = await axios.delete(route('admin.careers.destroy', deleting.value.id));
        applyPayload(data);
        if (data?.toast) toast(data.toast);
        deleting.value = null;
    } catch (e) {
        toast({
            type: 'error',
            title: 'Delete failed',
            message: e?.response?.data?.message || 'Could not delete vacancy.',
        });
    } finally {
        busy.value = false;
    }
};
</script>
