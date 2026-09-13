<template>
    <Head title="FAQs" />

    <AdminChrome title="FAQs" eyebrow="Content" />

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[13px] font-medium text-ink/50">
                Manage public FAQ answers. Favourited items (up to 5 recommended) appear on the landing page.
                <span class="font-semibold text-ink/70">{{ featured_count }} favourited</span>
            </p>
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover active:scale-[0.98]"
                @click="openCreate"
            >
                <i class="ti ti-plus" aria-hidden="true" />
                Add FAQ
            </button>
        </div>

        <AdminEmpty
            v-if="!rows.length"
            class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            title="No FAQs yet"
            description="Create questions visitors ask most — mark favourites for the home page."
            icon="ti ti-help"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-base-hover"
                @click="openCreate"
            >
                Add FAQ
            </button>
        </AdminEmpty>

        <div v-else class="space-y-3">
            <article
                v-for="faq in rows"
                :key="faq.id"
                class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-ink">{{ faq.question }}</h2>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                :class="
                                    faq.is_published
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-pale text-ink/40'
                                "
                            >
                                {{ faq.is_published ? 'Live' : 'Draft' }}
                            </span>
                            <span
                                v-if="faq.is_featured"
                                class="rounded-full bg-coral/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-coral"
                            >
                                Favourited
                                <span v-if="faq.featured_sort">#{{ faq.featured_sort }}</span>
                            </span>
                        </div>
                        <p class="mt-1 text-[12px] font-semibold text-ink/35">{{ faq.category }}</p>
                        <p class="mt-2 line-clamp-2 text-[13px] font-medium leading-relaxed text-ink/50">
                            {{ faq.answer }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:shrink-0">
                        <button
                            type="button"
                            class="rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60"
                            @click="openEdit(faq)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-red-50 px-3 py-2 text-[12px] font-bold text-red-600"
                            @click="deleting = faq"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <AdminDrawer :open="drawerOpen" :title="editing ? 'Edit FAQ' : 'Add FAQ'" @close="closeDrawer">
            <form class="space-y-4" @submit.prevent="save">
                <FormTextInput v-model="form.question" label="Question" required :error="errors.question" />
                <FormTextarea
                    v-model="form.answer"
                    label="Answer"
                    :rows="5"
                    required
                    :error="errors.answer"
                />
                <FormSelect
                    v-model="categoryChoice"
                    label="Category"
                    placeholder="Select a category"
                    :options="categorySelectOptions"
                    searchable
                    search-placeholder="Search categories…"
                    required
                    :error="errors.category"
                />
                <FormTextInput
                    v-if="categoryChoice === '__new__'"
                    v-model="form.category"
                    label="New category name"
                    placeholder="e.g. Getting started"
                    required
                    :error="errors.category"
                />
                <div class="grid gap-3 sm:grid-cols-2">
                    <FormTextInput
                        v-model.number="form.sort_order"
                        type="number"
                        label="Sort order"
                        :error="errors.sort_order"
                    />
                    <FormTextInput
                        v-if="form.is_featured"
                        v-model.number="form.featured_sort"
                        type="number"
                        label="Favourite order"
                        hint="Lower shows first on the landing page"
                        :error="errors.featured_sort"
                    />
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-ink/70">
                    <input v-model="form.is_published" type="checkbox" class="rounded border-ink/20 text-base-action" />
                    Published on /faq
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold text-ink/70">
                    <input v-model="form.is_featured" type="checkbox" class="rounded border-ink/20 text-base-action" />
                    Favourite — show on landing page
                </label>
                <p v-if="formError" class="text-sm font-medium text-red-600">{{ formError }}</p>
                <div class="flex flex-wrap gap-2 pt-2">
                    <FormButton type="submit" variant="primary" :processing="busy">
                        {{ editing ? 'Save changes' : 'Create FAQ' }}
                    </FormButton>
                    <FormButton type="button" variant="secondary" :disabled="busy" @click="closeDrawer">
                        Cancel
                    </FormButton>
                </div>
            </form>
        </AdminDrawer>

        <AdminConfirmDialog
            :open="!!deleting"
            title="Delete this FAQ"
            :description="deleting ? `Remove “${deleting.question}” from the public FAQ page?` : ''"
            confirm-label="Delete FAQ"
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
    faqs: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    featured_count: { type: Number, default: 0 },
});

const rows = ref([...props.faqs]);
const featured_count = ref(props.featured_count);
const drawerOpen = ref(false);
const editing = ref(null);
const deleting = ref(null);
const busy = ref(false);
const formError = ref('');
const errors = reactive({});
const categoryChoice = ref('Getting started');

const blank = () => ({
    question: '',
    answer: '',
    category: 'Getting started',
    sort_order: 100,
    is_published: true,
    is_featured: false,
    featured_sort: null,
});

const form = reactive(blank());

const presetCategories = ['Getting started', 'Reviews', 'Your public page', 'Pricing', 'General'];

const categoryOptions = computed(() => {
    const set = new Set([...(props.categories || []), ...presetCategories].filter(Boolean));
    return [...set].sort((a, b) => a.localeCompare(b));
});

const categorySelectOptions = computed(() => [
    ...categoryOptions.value.map((c) => ({ value: c, label: c })),
    { value: '__new__', label: 'Add new category…' },
]);

watch(categoryChoice, (value) => {
    if (value && value !== '__new__') {
        form.category = value;
    } else if (value === '__new__') {
        form.category = '';
    }
});

watch(
    () => props.faqs,
    (value) => {
        rows.value = [...value];
    },
);

watch(
    () => props.featured_count,
    (value) => {
        featured_count.value = value;
    },
);

const openCreate = () => {
    editing.value = null;
    Object.assign(form, blank());
    categoryChoice.value = 'Getting started';
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    drawerOpen.value = true;
};

const openEdit = (faq) => {
    editing.value = faq;
    Object.assign(form, {
        question: faq.question,
        answer: faq.answer,
        category: faq.category,
        sort_order: faq.sort_order,
        is_published: faq.is_published,
        is_featured: faq.is_featured,
        featured_sort: faq.featured_sort,
    });
    categoryChoice.value = categoryOptions.value.includes(faq.category) ? faq.category : '__new__';
    if (categoryChoice.value === '__new__') {
        form.category = faq.category;
    }
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    drawerOpen.value = true;
};

const closeDrawer = () => {
    if (busy.value) return;
    drawerOpen.value = false;
};

const applyPayload = (payload) => {
    if (payload?.faq) {
        const idx = rows.value.findIndex((r) => r.id === payload.faq.id);
        if (idx >= 0) rows.value.splice(idx, 1, payload.faq);
        else rows.value.unshift(payload.faq);
        featured_count.value = rows.value.filter((r) => r.is_featured && r.is_published).length;
    }
    if (payload?.deleted_id) {
        rows.value = rows.value.filter((r) => r.id !== payload.deleted_id);
        featured_count.value = rows.value.filter((r) => r.is_featured && r.is_published).length;
    }
};

const save = async () => {
    busy.value = true;
    formError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    try {
        const body = {
            question: form.question,
            answer: form.answer,
            category: form.category,
            sort_order: form.sort_order,
            is_published: form.is_published,
            is_featured: form.is_featured,
            featured_sort: form.is_featured ? form.featured_sort : null,
        };
        const { data } = editing.value
            ? await axios.patch(route('admin.faqs.update', editing.value.id), body)
            : await axios.post(route('admin.faqs.store'), body);

        if (data?.toast) toast(data.toast);
        applyPayload(data);
        drawerOpen.value = false;
    } catch (e) {
        const resp = e?.response?.data;
        if (resp?.errors) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(resp.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]),
            ));
        }
        formError.value = resp?.message || 'Could not save FAQ.';
    } finally {
        busy.value = false;
    }
};

const destroy = async () => {
    if (!deleting.value) return;
    busy.value = true;
    try {
        const { data } = await axios.delete(route('admin.faqs.destroy', deleting.value.id));
        if (data?.toast) toast(data.toast);
        applyPayload(data);
        deleting.value = null;
    } catch (e) {
        toast({
            type: 'error',
            title: 'Delete failed',
            message: e?.response?.data?.message || 'Could not delete FAQ.',
        });
    } finally {
        busy.value = false;
    }
};
</script>
