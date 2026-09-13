<template>
    <Head :title="pageTitle" />

    <AdminChrome :title="pageTitle" eyebrow="Catalog" />

    <!-- Categories -->
    <section v-if="activeTab === 'categories'">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <p class="max-w-xl text-[13px] font-medium leading-relaxed text-ink/50">
                Categories and subcategories power signup and work-log classification.
                <span class="font-semibold text-ink/65"
                    >{{ categoryRows.length }} categories · {{ subcategoryCount }} subs</span
                >
            </p>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label class="relative block sm:w-56">
                    <span class="sr-only">Search categories</span>
                    <i
                        class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink/30"
                        aria-hidden="true"
                    />
                    <input
                        v-model.trim="categoryQuery"
                        type="search"
                        placeholder="Search…"
                        class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3 text-sm font-medium text-ink ring-1 ring-ink/[0.08] placeholder:text-ink/35 focus:outline-none focus:ring-2 focus:ring-base-action/30"
                    />
                </label>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover"
                    @click="openCategory()"
                >
                    <i class="ti ti-plus" aria-hidden="true" />
                    Add category
                </button>
            </div>
        </div>

        <AdminEmpty
            v-if="!categoryRows.length"
            class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            title="No categories yet"
            description="Add a job category, then nest the trades artisans pick at signup."
            icon="ti ti-briefcase"
        />

        <div
            v-else-if="!filteredCategories.length"
            class="rounded-2xl bg-white px-5 py-10 text-center text-sm font-medium text-ink/45 shadow-premium ring-1 ring-ink/[0.05]"
        >
            No categories match “{{ categoryQuery }}”.
        </div>

        <div v-else class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <div
                v-for="(category, index) in filteredCategories"
                :key="category.id"
                :class="index > 0 ? 'border-t border-ink/[0.05]' : ''"
            >
                <div class="flex flex-col gap-2 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-3 text-left"
                        @click="toggleExpand(category.id)"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-pale text-ink/40 transition-transform"
                            :class="{ 'rotate-90': expanded[category.id] }"
                        >
                            <i class="ti ti-chevron-right text-sm" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-bold text-ink">{{ category.name }}</span>
                                <span
                                    v-if="!category.is_active"
                                    class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/40"
                                >
                                    Off
                                </span>
                            </div>
                            <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-ink/35">
                                {{ category.subcategories?.length || 0 }} subcategories
                            </p>
                        </div>
                    </button>
                    <div class="flex flex-wrap gap-1.5 pl-10 sm:pl-0">
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1.5 text-[12px] font-bold text-base-action hover:bg-tint"
                            @click="openSub(category)"
                        >
                            Add sub
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1.5 text-[12px] font-bold text-ink/50 hover:bg-pale"
                            @click="openCategory(category)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1.5 text-[12px] font-bold text-red-600 hover:bg-red-50"
                            @click="askDeleteCategory(category)"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div v-if="expanded[category.id]" class="border-t border-ink/[0.04] bg-pale/50 px-4 py-3 sm:px-5">
                    <ul v-if="category.subcategories?.length" class="space-y-1.5">
                        <li
                            v-for="sub in category.subcategories"
                            :key="sub.id"
                            class="flex flex-col gap-2 rounded-xl bg-white px-3 py-2.5 ring-1 ring-ink/[0.04] sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <p class="text-[13px] font-bold text-ink">
                                    {{ sub.name }}
                                    <span v-if="!sub.is_active" class="ml-1 text-[11px] font-semibold text-ink/35"
                                        >· Off</span
                                    >
                                </p>
                                <p class="truncate text-[11px] font-medium text-ink/40">
                                    {{ sub.review_phrase || 'recent work' }}
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-ink/50 hover:bg-pale"
                                    @click="openSub(category, sub)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-red-600 hover:bg-red-50"
                                    @click="askDeleteSub(sub)"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="py-3 text-center text-[13px] font-medium text-ink/40">
                        No subcategories yet —
                        <button type="button" class="font-bold text-base-action" @click="openSub(category)">
                            add one
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Skills -->
    <section v-else>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <p class="max-w-xl text-[13px] font-medium leading-relaxed text-ink/50">
                Suggested skills artisans can attach to their public profile.
                <span class="font-semibold text-ink/65">{{ skillRows.length }} tags</span>
            </p>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label class="relative block sm:w-56">
                    <span class="sr-only">Search skills</span>
                    <i
                        class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink/30"
                        aria-hidden="true"
                    />
                    <input
                        v-model.trim="skillQuery"
                        type="search"
                        placeholder="Search…"
                        class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3 text-sm font-medium text-ink ring-1 ring-ink/[0.08] placeholder:text-ink/35 focus:outline-none focus:ring-2 focus:ring-base-action/30"
                    />
                </label>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover"
                    @click="openSkill()"
                >
                    <i class="ti ti-plus" aria-hidden="true" />
                    Add skill
                </button>
            </div>
        </div>

        <AdminEmpty
            v-if="!skillRows.length"
            class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            title="No skills yet"
            description="Add short, client-facing skill tags for profile suggestions."
            icon="ti ti-tags"
        />

        <div
            v-else-if="!filteredSkills.length"
            class="rounded-2xl bg-white px-5 py-10 text-center text-sm font-medium text-ink/45 shadow-premium ring-1 ring-ink/[0.05]"
        >
            No skills match “{{ skillQuery }}”.
        </div>

        <ul v-else class="flex flex-wrap gap-2">
            <li
                v-for="skill in filteredSkills"
                :key="skill.id"
                class="group inline-flex max-w-full items-center gap-1 rounded-full bg-white py-1.5 pl-3.5 pr-1.5 shadow-sm ring-1 ring-ink/[0.06]"
            >
                <span
                    class="truncate text-[13px] font-semibold text-ink"
                    :class="{ 'opacity-40': !skill.is_active }"
                >
                    {{ skill.name }}
                </span>
                <button
                    type="button"
                    class="rounded-full p-1.5 text-ink/30 opacity-70 transition-colors hover:bg-pale hover:text-ink sm:opacity-0 sm:group-hover:opacity-100"
                    aria-label="Edit skill"
                    @click="openSkill(skill)"
                >
                    <i class="ti ti-pencil text-sm" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="rounded-full p-1.5 text-ink/30 opacity-70 transition-colors hover:bg-red-50 hover:text-red-600 sm:opacity-0 sm:group-hover:opacity-100"
                    aria-label="Delete skill"
                    @click="askDeleteSkill(skill)"
                >
                    <i class="ti ti-x text-sm" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </section>

    <AdminDrawer
        :open="categoryDrawer"
        :title="editingCategory ? 'Edit category' : 'Add category'"
        @close="categoryDrawer = false"
    >
        <form class="space-y-4" @submit.prevent="saveCategory">
            <FormTextInput v-model="categoryForm.name" label="Category name" required :error="formErrors.name" />
            <FormTextInput v-model.number="categoryForm.sort_order" type="number" label="Sort order" />
            <label class="flex items-center gap-2 text-sm font-semibold text-ink/70">
                <input v-model="categoryForm.is_active" type="checkbox" class="rounded border-ink/20 text-base-action" />
                Active on signup & job forms
            </label>
            <p v-if="formError" class="text-sm font-medium text-red-600">{{ formError }}</p>
            <FormButton type="submit" variant="primary" :processing="busy">Save category</FormButton>
        </form>
    </AdminDrawer>

    <AdminDrawer
        :open="subDrawer"
        :title="editingSub ? 'Edit subcategory' : 'Add subcategory'"
        :eyebrow="subParent?.name"
        @close="subDrawer = false"
    >
        <form class="space-y-4" @submit.prevent="saveSub">
            <FormTextInput v-model="subForm.name" label="Subcategory / trade name" required :error="formErrors.name" />
            <FormTextInput
                v-model="subForm.review_phrase"
                label="Review invite phrase"
                hint="Used privately in WhatsApp invites — e.g. “plumbing work”"
                :error="formErrors.review_phrase"
            />
            <FormTextInput v-model.number="subForm.sort_order" type="number" label="Sort order" />
            <label class="flex items-center gap-2 text-sm font-semibold text-ink/70">
                <input v-model="subForm.is_active" type="checkbox" class="rounded border-ink/20 text-base-action" />
                Active
            </label>
            <p v-if="formError" class="text-sm font-medium text-red-600">{{ formError }}</p>
            <FormButton type="submit" variant="primary" :processing="busy">Save subcategory</FormButton>
        </form>
    </AdminDrawer>

    <AdminDrawer
        :open="skillDrawer"
        :title="editingSkill ? 'Edit skill' : 'Add skill'"
        @close="skillDrawer = false"
    >
        <form class="space-y-4" @submit.prevent="saveSkill">
            <FormTextInput v-model="skillForm.name" label="Skill name" required :error="formErrors.name" />
            <FormTextInput v-model.number="skillForm.sort_order" type="number" label="Sort order" />
            <label class="flex items-center gap-2 text-sm font-semibold text-ink/70">
                <input v-model="skillForm.is_active" type="checkbox" class="rounded border-ink/20 text-base-action" />
                Active in suggestions
            </label>
            <p v-if="formError" class="text-sm font-medium text-red-600">{{ formError }}</p>
            <FormButton type="submit" variant="primary" :processing="busy">Save skill</FormButton>
        </form>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="!!deleting"
        :title="deleteTitle"
        :description="deleteDescription"
        confirm-label="Delete"
        tone="danger"
        :processing="busy"
        @close="deleting = null"
        @confirm="confirmDelete"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { toast } from '@/utils/adminRange';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
});

const { tab } = useAdminTabs({ tab: 'categories' });
const activeTab = computed(() => (tab.value === 'skills' ? 'skills' : 'categories'));
const pageTitle = computed(() => (activeTab.value === 'skills' ? 'Skills' : 'Categories'));

const categoryRows = ref([...props.categories]);
const skillRows = ref([...props.skills]);
const categoryQuery = ref('');
const skillQuery = ref('');
const expanded = reactive({});
const busy = ref(false);
const formError = ref('');
const formErrors = reactive({});

const categoryDrawer = ref(false);
const subDrawer = ref(false);
const skillDrawer = ref(false);
const editingCategory = ref(null);
const editingSub = ref(null);
const editingSkill = ref(null);
const subParent = ref(null);
const deleting = ref(null);

const categoryForm = reactive({ name: '', sort_order: 100, is_active: true });
const subForm = reactive({ name: '', review_phrase: '', sort_order: 100, is_active: true });
const skillForm = reactive({ name: '', sort_order: 100, is_active: true });

watch(
    () => props.categories,
    (v) => {
        categoryRows.value = [...v];
    },
);
watch(
    () => props.skills,
    (v) => {
        skillRows.value = [...v];
    },
);

watch(activeTab, () => {
    categoryQuery.value = '';
    skillQuery.value = '';
});

const subcategoryCount = computed(() =>
    categoryRows.value.reduce((n, c) => n + (c.subcategories?.length || 0), 0),
);

const filteredCategories = computed(() => {
    const q = categoryQuery.value.toLowerCase();
    if (!q) return categoryRows.value;
    return categoryRows.value.filter((c) => {
        if (c.name.toLowerCase().includes(q)) return true;
        return (c.subcategories || []).some((s) => s.name.toLowerCase().includes(q));
    });
});

const filteredSkills = computed(() => {
    const q = skillQuery.value.toLowerCase();
    if (!q) return skillRows.value;
    return skillRows.value.filter((s) => s.name.toLowerCase().includes(q));
});

const deleteTitle = computed(() => {
    if (!deleting.value) return '';
    if (deleting.value.type === 'category') return 'Delete category';
    if (deleting.value.type === 'sub') return 'Delete subcategory';
    return 'Delete skill';
});

const deleteDescription = computed(() => {
    if (!deleting.value) return '';
    if (deleting.value.type === 'category') {
        return `Delete “${deleting.value.item.name}” and all of its subcategories?`;
    }
    return `Remove “${deleting.value.item.name}”?`;
});

const clearErrors = () => {
    formError.value = '';
    Object.keys(formErrors).forEach((k) => delete formErrors[k]);
};

const toggleExpand = (id) => {
    expanded[id] = !expanded[id];
};

const openCategory = (category = null) => {
    editingCategory.value = category;
    Object.assign(
        categoryForm,
        category
            ? { name: category.name, sort_order: category.sort_order, is_active: category.is_active }
            : { name: '', sort_order: 100, is_active: true },
    );
    clearErrors();
    categoryDrawer.value = true;
};

const openSub = (category, sub = null) => {
    subParent.value = category;
    editingSub.value = sub;
    expanded[category.id] = true;
    Object.assign(
        subForm,
        sub
            ? {
                  name: sub.name,
                  review_phrase: sub.review_phrase || '',
                  sort_order: sub.sort_order,
                  is_active: sub.is_active,
              }
            : { name: '', review_phrase: '', sort_order: 100, is_active: true },
    );
    clearErrors();
    subDrawer.value = true;
};

const openSkill = (skill = null) => {
    editingSkill.value = skill;
    Object.assign(
        skillForm,
        skill
            ? { name: skill.name, sort_order: skill.sort_order, is_active: skill.is_active }
            : { name: '', sort_order: 100, is_active: true },
    );
    clearErrors();
    skillDrawer.value = true;
};

const askDeleteCategory = (item) => {
    deleting.value = { type: 'category', item };
};
const askDeleteSub = (item) => {
    deleting.value = { type: 'sub', item };
};
const askDeleteSkill = (item) => {
    deleting.value = { type: 'skill', item };
};

const handleError = (e) => {
    const resp = e?.response?.data;
    if (resp?.errors) {
        Object.assign(
            formErrors,
            Object.fromEntries(Object.entries(resp.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])),
        );
    }
    formError.value = resp?.message || 'Something went wrong.';
};

const upsertCategory = (category) => {
    const idx = categoryRows.value.findIndex((c) => c.id === category.id);
    if (idx >= 0) {
        const existing = categoryRows.value[idx];
        categoryRows.value.splice(idx, 1, {
            ...category,
            subcategories: category.subcategories?.length
                ? category.subcategories
                : existing.subcategories,
        });
    } else {
        categoryRows.value.unshift({ ...category, subcategories: category.subcategories || [] });
    }
};

const saveCategory = async () => {
    busy.value = true;
    clearErrors();
    try {
        const body = { ...categoryForm };
        const { data } = editingCategory.value
            ? await axios.patch(route('admin.taxonomy.categories.update', editingCategory.value.id), body)
            : await axios.post(route('admin.taxonomy.categories.store'), body);
        if (data?.toast) toast(data.toast);
        if (data?.category) upsertCategory(data.category);
        categoryDrawer.value = false;
    } catch (e) {
        handleError(e);
    } finally {
        busy.value = false;
    }
};

const saveSub = async () => {
    if (!subParent.value) return;
    busy.value = true;
    clearErrors();
    try {
        const body = { ...subForm };
        const { data } = editingSub.value
            ? await axios.patch(route('admin.taxonomy.subcategories.update', editingSub.value.id), body)
            : await axios.post(
                  route('admin.taxonomy.categories.subcategories.store', subParent.value.id),
                  body,
              );
        if (data?.toast) toast(data.toast);
        if (data?.subcategory) {
            const cat = categoryRows.value.find((c) => c.id === (data.category_id || subParent.value.id));
            if (cat) {
                const list = [...(cat.subcategories || [])];
                const idx = list.findIndex((s) => s.id === data.subcategory.id);
                if (idx >= 0) list.splice(idx, 1, data.subcategory);
                else list.push(data.subcategory);
                list.sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name));
                cat.subcategories = list;
            }
        }
        subDrawer.value = false;
    } catch (e) {
        handleError(e);
    } finally {
        busy.value = false;
    }
};

const saveSkill = async () => {
    busy.value = true;
    clearErrors();
    try {
        const body = { ...skillForm };
        const { data } = editingSkill.value
            ? await axios.patch(route('admin.taxonomy.skills.update', editingSkill.value.id), body)
            : await axios.post(route('admin.taxonomy.skills.store'), body);
        if (data?.toast) toast(data.toast);
        if (data?.skill) {
            const idx = skillRows.value.findIndex((s) => s.id === data.skill.id);
            if (idx >= 0) skillRows.value.splice(idx, 1, data.skill);
            else skillRows.value.unshift(data.skill);
        }
        skillDrawer.value = false;
    } catch (e) {
        handleError(e);
    } finally {
        busy.value = false;
    }
};

const confirmDelete = async () => {
    if (!deleting.value) return;
    busy.value = true;
    try {
        const { type, item } = deleting.value;
        let data;
        if (type === 'category') {
            ({ data } = await axios.delete(route('admin.taxonomy.categories.destroy', item.id)));
            categoryRows.value = categoryRows.value.filter((c) => c.id !== item.id);
        } else if (type === 'sub') {
            ({ data } = await axios.delete(route('admin.taxonomy.subcategories.destroy', item.id)));
            for (const cat of categoryRows.value) {
                cat.subcategories = (cat.subcategories || []).filter((s) => s.id !== item.id);
            }
        } else {
            ({ data } = await axios.delete(route('admin.taxonomy.skills.destroy', item.id)));
            skillRows.value = skillRows.value.filter((s) => s.id !== item.id);
        }
        if (data?.toast) toast(data.toast);
        deleting.value = null;
    } catch (e) {
        toast({
            type: 'error',
            title: 'Delete failed',
            message: e?.response?.data?.message || 'Could not delete.',
        });
    } finally {
        busy.value = false;
    }
};
</script>
