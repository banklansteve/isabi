<template>
    <Head title="Careers" />

    <AdminChrome title="Careers" eyebrow="Company" />

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[13px] font-medium text-ink/50">
            Set status to
            <span class="font-semibold text-ink/70">Open</span>
            to post on
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

    <div
        v-if="rows.length"
        class="mb-4 flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:flex-row sm:items-center sm:p-4"
    >
        <div class="relative min-w-0 flex-1">
            <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink/30" />
            <input
                v-model="listQ"
                type="search"
                placeholder="Search title, department, owner…"
                class="w-full rounded-xl border border-ink/10 bg-pale py-2.5 pl-9 pr-3 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            />
        </div>
        <select
            v-model="listStatus"
            class="rounded-xl border border-ink/10 bg-pale px-3 py-2.5 text-sm font-semibold text-ink/70 outline-none focus:border-base"
        >
            <option value="">All statuses</option>
            <option v-for="s in meta.statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
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

    <AdminEmpty
        v-else-if="!filteredRows.length"
        class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
        title="No matches"
        description="Try another search or status filter."
        icon="ti ti-search"
    />

    <div v-else class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[13px]">
                <thead class="border-b border-ink/[0.06] bg-pale/60 text-[11px] font-bold uppercase tracking-wide text-ink/40">
                    <tr>
                        <th class="px-4 py-3">Job title</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Openings</th>
                        <th class="px-4 py-3 text-right">Applicants</th>
                        <th class="px-4 py-3">Posted</th>
                        <th class="px-4 py-3">Closes</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="vacancy in filteredRows"
                        :key="vacancy.id"
                        class="cursor-pointer border-b border-ink/[0.04] transition-colors last:border-0 hover:bg-pale/50"
                        @click="openDetail(vacancy)"
                    >
                        <td class="px-4 py-3">
                            <p class="font-bold text-ink">{{ vacancy.title }}</p>
                            <p class="mt-0.5 text-[11px] font-semibold text-ink/35">
                                {{ labelOf(meta.employment_types, vacancy.employment_type) }}
                                · {{ labelOf(meta.work_modes, vacancy.work_mode) }}
                                <span v-if="vacancy.is_public" class="text-emerald-600"> · Public</span>
                                <span v-else> · Internal</span>
                            </p>
                        </td>
                        <td class="px-4 py-3 font-medium text-ink/55">{{ vacancy.department || '—' }}</td>
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
                        <td class="px-4 py-3 font-medium text-ink/45">{{ formatDate(vacancy.published_at) }}</td>
                        <td class="px-4 py-3 font-medium text-ink/45">{{ formatDate(vacancy.closes_at) }}</td>
                        <td class="px-4 py-3" @click.stop>
                            <div class="flex flex-wrap justify-end gap-1.5">
                                <button
                                    type="button"
                                    class="rounded-lg bg-pale px-2.5 py-1.5 text-[11px] font-bold text-ink/60 hover:bg-ink/[0.08]"
                                    @click="openDetail(vacancy)"
                                >
                                    Open
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg bg-pale px-2.5 py-1.5 text-[11px] font-bold text-ink/60 hover:bg-ink/[0.08]"
                                    @click="openEdit(vacancy)"
                                >
                                    Edit
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

    <!-- Detail -->
    <AdminDrawer :open="!!viewing" size="lg" title="Vacancy details" @close="viewing = null">
        <template v-if="viewing">
            <div class="space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-bold tracking-tight text-ink">{{ viewing.title }}</h3>
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                :class="statusClass(viewing.status)"
                            >
                                {{ viewing.status }}
                            </span>
                            <span
                                v-if="viewing.status === 'open'"
                                class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700"
                            >
                                Live on /careers
                            </span>
                        </div>
                        <p class="mt-1 text-sm font-medium text-ink/45">
                            {{ viewing.department || 'No department' }}
                            · {{ labelOf(meta.employment_types, viewing.employment_type) }}
                            · {{ labelOf(meta.work_modes, viewing.work_mode) }}
                        </p>
                    </div>
                    <FormButton
                        type="button"
                        variant="primary"
                        class="shrink-0"
                        :disabled="busy"
                        @click="openEdit(viewing)"
                    >
                        Edit vacancy
                    </FormButton>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Location</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.location || '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Employment type</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">
                            {{ labelOf(meta.employment_types, viewing.employment_type) }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Work mode</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">
                            {{ labelOf(meta.work_modes, viewing.work_mode) }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Department</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.department || '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Openings</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.openings }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Applicants</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.applicants_count }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Date posted</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ formatDate(viewing.published_at) }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Closing date</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ formatDate(viewing.closes_at) }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Hiring manager</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.hiring_manager_name || '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Role on hire</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.staff_role_name || '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Salary range</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ salaryLabel(viewing) }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Salary public</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">
                            {{ viewing.salary_is_public ? 'Shown publicly' : 'Internal only' }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Public visibility</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">
                            {{ viewing.status === 'open' ? 'Yes — live on /careers' : 'No' }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Sort order</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.sort_order ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-pale/80 px-3 py-2.5 sm:col-span-2">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink/35">Notify email</p>
                        <p class="mt-0.5 text-sm font-bold text-ink">{{ viewing.apply_email || '—' }}</p>
                    </div>
                </div>

                <FormSelect
                    v-model="quickStatus"
                    label="Change status"
                    hint="Open posts this role on the public Careers page"
                    :options="meta.statuses"
                    :disabled="busy"
                />

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Summary</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-ink/60">
                        {{ viewing.summary || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Job description</p>
                    <p class="mt-1 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60">
                        {{ viewing.description || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-ink/35">
                        Requirements / qualifications
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60">
                        {{ viewing.requirements || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Internal notes</p>
                    <p class="mt-1 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60">
                        {{ viewing.internal_notes || '—' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-ink/[0.06] pt-4">
                    <FormButton type="button" variant="primary" :loading="busy" @click="saveQuickStatus">
                        Save status
                    </FormButton>
                    <a
                        v-if="viewing.status === 'open' && viewing.public_uid"
                        :href="
                            route('careers.show', {
                                slug: viewing.slug || 'role',
                                vacancy: viewing.public_uid,
                            })
                        "
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center rounded-xl bg-pale px-4 py-2.5 text-sm font-semibold text-ink/70 hover:bg-ink/[0.08]"
                    >
                        View public page
                    </a>
                    <FormButton
                        v-if="viewing.status === 'open'"
                        type="button"
                        variant="secondary"
                        :disabled="busy"
                        @click="makeInactive(viewing)"
                    >
                        Make inactive
                    </FormButton>
                    <FormButton
                        v-else-if="viewing.status !== 'open'"
                        type="button"
                        variant="primary"
                        :loading="busy"
                        @click="postVacancy(viewing)"
                    >
                        Post to careers
                    </FormButton>
                    <button
                        type="button"
                        class="rounded-xl bg-red-50 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-100"
                        :disabled="busy"
                        @click="deleting = viewing"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>
    </AdminDrawer>

    <!-- Create / Edit -->
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
                <FormDatePicker
                    v-model="form.published_at"
                    label="Date posted"
                    :min-date="DATE_MIN"
                    :max-date="DATE_MAX"
                    :error="errors.published_at"
                />
                <FormDatePicker
                    v-model="form.closes_at"
                    label="Closing date"
                    hint="Application deadline"
                    :min-date="DATE_MIN"
                    :max-date="DATE_MAX"
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
                    :disabled="form.status === 'open'"
                />
                <span>
                    Public visibility
                    <span class="block text-[12px] font-medium text-ink/40">
                        Status “Open” always posts to /careers. Use Draft/Paused to hide.
                    </span>
                </span>
            </label>

            <FormTextarea
                v-model="form.internal_notes"
                label="Internal notes"
                :rows="3"
                :error="errors.internal_notes"
            />

            <FormTextInput
                v-model="form.apply_email"
                type="email"
                label="Notify email (optional)"
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
import FormDatePicker from '@/Components/Form/FormDatePicker.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { toast } from '@/utils/adminRange';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const DATE_MIN = '2000-01-01';
const DATE_MAX = (() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() + 5);
    return d.toISOString().slice(0, 10);
})();

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
const listQ = ref('');
const listStatus = ref('');
const drawerOpen = ref(false);
const editing = ref(null);
const viewing = ref(null);
const quickStatus = ref('');
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
    status: 'open',
    openings: 1,
    summary: '',
    description: '',
    requirements: '',
    salary_min: null,
    salary_max: null,
    salary_currency: 'NGN',
    salary_is_public: false,
    is_public: true,
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

const filteredRows = computed(() => {
    const needle = listQ.value.trim().toLowerCase();
    return rows.value.filter((v) => {
        if (listStatus.value && v.status !== listStatus.value) return false;
        if (!needle) return true;
        const hay = [v.title, v.department, v.hiring_manager_name, v.location, v.summary]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return hay.includes(needle);
    });
});

const labelOf = (options, value) =>
    (options || []).find((o) => o.value === value)?.label || value || '—';

const formatDate = (iso) => {
    if (!iso) return '—';
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso));
    if (!m) return iso;
    return `${m[3]}/${m[2]}/${m[1]}`;
};

const salaryLabel = (vacancy) => {
    if (!vacancy) return '—';
    const cur = vacancy.salary_currency || 'NGN';
    const fmt = (n) => (n == null ? null : Number(n).toLocaleString('en-NG'));
    if (vacancy.salary_min && vacancy.salary_max) {
        return `${cur} ${fmt(vacancy.salary_min)} – ${fmt(vacancy.salary_max)}`;
    }
    if (vacancy.salary_min) return `From ${cur} ${fmt(vacancy.salary_min)}`;
    if (vacancy.salary_max) return `Up to ${cur} ${fmt(vacancy.salary_max)}`;
    return 'Not set';
};

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
        if (viewing.value) {
            const fresh = rows.value.find((r) => r.id === viewing.value.id);
            if (fresh) {
                viewing.value = fresh;
                quickStatus.value = fresh.status;
            }
        }
    },
);

watch(
    () => props.open_count,
    (value) => {
        open_count.value = value;
    },
);

watch(
    () => form.status,
    (status) => {
        if (status === 'open') {
            form.is_public = true;
            if (!form.published_at) {
                form.published_at = new Date().toISOString().slice(0, 10);
            }
        }
    },
);

const openDetail = (vacancy) => {
    viewing.value = vacancy;
    quickStatus.value = vacancy.status;
};

const openCreate = () => {
    viewing.value = null;
    editing.value = null;
    Object.assign(form, blank());
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    drawerOpen.value = true;
};

const openEdit = (vacancy) => {
    viewing.value = null;
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
        if (viewing.value?.id === payload.vacancy.id) {
            viewing.value = payload.vacancy;
            quickStatus.value = payload.vacancy.status;
        }
    }
    if (payload?.deleted_id) {
        rows.value = rows.value.filter((r) => r.id !== payload.deleted_id);
        open_count.value = rows.value.filter((r) => r.status === 'open').length;
        if (viewing.value?.id === payload.deleted_id) viewing.value = null;
        if (editing.value?.id === payload.deleted_id) {
            editing.value = null;
            drawerOpen.value = false;
        }
    }
};

const buildBody = (overrides = {}) => {
    const source = { ...form, ...overrides };
    const body = { ...source };
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

const bodyFromVacancy = (vacancy, overrides = {}) => ({
    title: vacancy.title,
    department: vacancy.department || null,
    location: vacancy.location || null,
    employment_type: vacancy.employment_type,
    work_mode: vacancy.work_mode,
    status: vacancy.status,
    openings: vacancy.openings || 1,
    summary: vacancy.summary,
    description: vacancy.description || vacancy.summary,
    requirements: vacancy.requirements || null,
    salary_min: vacancy.salary_min,
    salary_max: vacancy.salary_max,
    salary_currency: vacancy.salary_currency || 'NGN',
    salary_is_public: !!vacancy.salary_is_public,
    is_public: !!vacancy.is_public,
    internal_notes: vacancy.internal_notes || null,
    apply_email: vacancy.apply_email || null,
    apply_url: vacancy.apply_url || null,
    sort_order: vacancy.sort_order,
    published_at: vacancy.published_at || null,
    closes_at: vacancy.closes_at || null,
    hiring_manager_id: vacancy.hiring_manager_id || null,
    staff_role_id: vacancy.staff_role_id || null,
    ...overrides,
});

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

const saveQuickStatus = async () => {
    if (!viewing.value) return;
    busy.value = true;
    try {
        const body = bodyFromVacancy(viewing.value, {
            status: quickStatus.value,
            is_public: quickStatus.value === 'open' ? true : viewing.value.is_public,
        });
        const { data } = await axios.patch(route('admin.careers.update', viewing.value.id), body);
        applyPayload(data);
        if (data?.toast) toast(data.toast);
        else toast({ type: 'success', title: 'Status updated', message: `Now ${quickStatus.value}.` });
    } catch (e) {
        toast({
            type: 'error',
            title: 'Update failed',
            message: e?.response?.data?.message || 'Could not update status.',
        });
    } finally {
        busy.value = false;
    }
};

const makeInactive = async (vacancy) => {
    busy.value = true;
    try {
        const body = bodyFromVacancy(vacancy, { status: 'paused', is_public: false });
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

const postVacancy = async (vacancy) => {
    busy.value = true;
    try {
        const body = bodyFromVacancy(vacancy, {
            status: 'open',
            is_public: true,
            published_at: vacancy.published_at || new Date().toISOString().slice(0, 10),
        });
        const { data } = await axios.patch(route('admin.careers.update', vacancy.id), body);
        applyPayload(data);
        quickStatus.value = 'open';
        if (data?.toast) toast(data.toast);
        else {
            toast({
                type: 'success',
                title: 'Posted',
                message: 'This role is now live on /careers.',
            });
        }
    } catch (e) {
        toast({
            type: 'error',
            title: 'Could not post',
            message: e?.response?.data?.message || 'Could not post vacancy.',
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
