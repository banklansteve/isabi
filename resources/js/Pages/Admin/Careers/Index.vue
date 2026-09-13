<template>
    <Head title="Careers" />

    <AdminChrome title="Careers" eyebrow="Company" />

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[13px] font-medium text-ink/50">
            Publish vacancies on the public Careers page. Drafts stay hidden until you mark them live.
            <span class="font-semibold text-ink/70">{{ published_count }} published</span>
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
        description="Create a role when you’re ready to hire. Until then the public Careers page invites intros by email only."
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

    <div v-else class="space-y-3">
        <article
            v-for="vacancy in rows"
            :key="vacancy.id"
            class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-bold text-ink">{{ vacancy.title }}</h2>
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="
                                vacancy.is_published
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-pale text-ink/40'
                            "
                        >
                            {{ vacancy.is_published ? 'Live' : 'Draft' }}
                        </span>
                    </div>
                    <p class="mt-1 text-[12px] font-semibold text-ink/35">
                        <span v-if="vacancy.department">{{ vacancy.department }}</span>
                        <span v-if="vacancy.department && vacancy.location"> · </span>
                        <span v-if="vacancy.location">{{ vacancy.location }}</span>
                        <span v-if="(vacancy.department || vacancy.location) && vacancy.employment_type"> · </span>
                        <span v-if="vacancy.employment_type">{{ employmentLabel(vacancy.employment_type) }}</span>
                    </p>
                    <p class="mt-2 line-clamp-2 text-[13px] font-medium leading-relaxed text-ink/50">
                        {{ vacancy.summary }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 sm:shrink-0">
                    <button
                        type="button"
                        class="rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60"
                        @click="openEdit(vacancy)"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-red-50 px-3 py-2 text-[12px] font-bold text-red-600"
                        @click="deleting = vacancy"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </article>
    </div>

    <AdminDrawer
        :open="drawerOpen"
        :title="editing ? 'Edit vacancy' : 'Add vacancy'"
        @close="closeDrawer"
    >
        <form class="space-y-4" @submit.prevent="save">
            <FormTextInput v-model="form.title" label="Role title" required :error="errors.title" />
            <FormTextarea
                v-model="form.summary"
                label="Short summary"
                hint="Shown on the public Careers list"
                :rows="3"
                required
                :error="errors.summary"
            />
            <FormTextarea
                v-model="form.description"
                label="Full description (optional)"
                :rows="6"
                :error="errors.description"
            />
            <div class="grid gap-3 sm:grid-cols-2">
                <FormTextInput
                    v-model="form.department"
                    label="Department"
                    placeholder="e.g. Engineering"
                    :error="errors.department"
                />
                <FormTextInput
                    v-model="form.location"
                    label="Location"
                    placeholder="e.g. Remote · Nigeria"
                    :error="errors.location"
                />
            </div>
            <FormSelect
                v-model="form.employment_type"
                label="Employment type"
                placeholder="Select type"
                :options="employmentOptions"
                :error="errors.employment_type"
            />
            <div class="grid gap-3 sm:grid-cols-2">
                <FormTextInput
                    v-model="form.apply_email"
                    type="email"
                    label="Apply email"
                    hint="Defaults to hello@kraftrack.com if empty"
                    :error="errors.apply_email"
                />
                <FormTextInput
                    v-model="form.apply_url"
                    label="Apply URL (optional)"
                    placeholder="https://…"
                    :error="errors.apply_url"
                />
            </div>
            <FormTextInput
                v-model.number="form.sort_order"
                type="number"
                label="Sort order"
                hint="Lower shows first"
                :error="errors.sort_order"
            />
            <label class="flex items-center gap-2 text-sm font-semibold text-ink/70">
                <input
                    v-model="form.is_published"
                    type="checkbox"
                    class="rounded border-ink/20 text-base-action"
                />
                Published on /careers
            </label>
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
        :description="deleting ? `Remove “${deleting.title}” from Careers?` : ''"
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
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    vacancies: { type: Array, default: () => [] },
    published_count: { type: Number, default: 0 },
});

const rows = ref([...props.vacancies]);
const published_count = ref(props.published_count);
const drawerOpen = ref(false);
const editing = ref(null);
const deleting = ref(null);
const busy = ref(false);
const formError = ref('');
const errors = reactive({});

const employmentOptions = [
    { value: 'full-time', label: 'Full-time' },
    { value: 'part-time', label: 'Part-time' },
    { value: 'contract', label: 'Contract' },
    { value: 'internship', label: 'Internship' },
    { value: 'other', label: 'Other' },
];

const blank = () => ({
    title: '',
    department: '',
    location: 'Remote · Nigeria',
    employment_type: 'full-time',
    summary: '',
    description: '',
    apply_email: 'hello@kraftrack.com',
    apply_url: '',
    sort_order: 100,
    is_published: false,
});

const form = reactive(blank());

const employmentLabel = (value) =>
    employmentOptions.find((o) => o.value === value)?.label || value || '';

watch(
    () => props.vacancies,
    (value) => {
        rows.value = [...value];
    },
);

watch(
    () => props.published_count,
    (value) => {
        published_count.value = value;
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
        title: vacancy.title,
        department: vacancy.department || '',
        location: vacancy.location || '',
        employment_type: vacancy.employment_type || '',
        summary: vacancy.summary,
        description: vacancy.description || '',
        apply_email: vacancy.apply_email || '',
        apply_url: vacancy.apply_url || '',
        sort_order: vacancy.sort_order,
        is_published: vacancy.is_published,
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
        published_count.value = rows.value.filter((r) => r.is_published).length;
    }
    if (payload?.deleted_id) {
        rows.value = rows.value.filter((r) => r.id !== payload.deleted_id);
        published_count.value = rows.value.filter((r) => r.is_published).length;
    }
};

const save = async () => {
    busy.value = true;
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    try {
        const body = { ...form };
        if (!body.apply_url) body.apply_url = null;
        if (!body.apply_email) body.apply_email = null;
        if (!body.employment_type) body.employment_type = null;

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
