<template>
    <Head title="HR settings" />

    <AdminChrome title="HR settings" eyebrow="Leave types & checklists" />
        <!-- Leave types -->
        <section class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Leave types</h2>
                    <p class="mt-1 text-sm font-medium text-ink/55">Configure the leave categories and their yearly allowance.</p>
                </div>
                <FormButton v-if="can.leave" variant="secondary" icon-left="ti ti-plus" label="Add type" @click="openLeaveType()" />
            </div>
            <div v-if="leaveTypes.length === 0" class="mt-6 rounded-2xl border border-dashed border-ink/15 px-6 py-10 text-center">
                <p class="text-sm font-semibold text-ink">No leave types yet</p>
                <p class="mt-1 text-sm font-medium text-ink/45">Add Annual, Sick, or any category your team uses.</p>
                <FormButton v-if="can.leave" class="mx-auto mt-4" variant="primary" icon-left="ti ti-plus" label="Add a leave type" @click="openLeaveType()" />
            </div>
            <div v-else class="mt-4 grid gap-3 sm:grid-cols-2">
                <div v-for="type in leaveTypes" :key="type.id" class="rounded-2xl bg-pale/60 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" :class="dotClass(type.color)" />
                                <p class="text-sm font-semibold text-ink">{{ type.name }}</p>
                                <span v-if="!type.is_active" class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">Inactive</span>
                            </div>
                            <p class="mt-1 text-xs font-medium text-ink/50">
                                {{ type.allowance_days }} days / year
                                <span v-if="type.seated"> · {{ type.seated }} request{{ type.seated === 1 ? '' : 's' }}</span>
                                <span v-if="type.description"> · {{ type.description }}</span>
                            </p>
                        </div>
                        <div v-if="can.leave" class="flex shrink-0 gap-1">
                            <button type="button" class="text-xs font-semibold text-base-action hover:text-base-hover" @click="openLeaveType(type)">Edit</button>
                            <button type="button" class="text-ink/35 hover:text-coral-deep" aria-label="Delete" @click="deleteLeaveType(type)"><i class="ti ti-trash text-sm" aria-hidden="true" /></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Checklist templates -->
        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <div v-for="tpl in templates" :key="tpl.id" class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">{{ tpl.kind === 'onboarding' ? 'Onboarding checklist' : 'Offboarding checklist' }}</h2>
                <p class="mt-1 text-sm font-medium text-ink/55">{{ tpl.name }}</p>
                <ul class="mt-3 space-y-1.5">
                    <li v-for="item in tpl.items" :key="item.id" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-pale">
                        <span class="text-sm font-medium text-ink/75">{{ item.label }}</span>
                        <button v-if="can.manage" type="button" class="text-ink/30 hover:text-coral-deep" aria-label="Remove item" @click="deleteTemplateItem(item)"><i class="ti ti-x text-sm" aria-hidden="true" /></button>
                    </li>
                </ul>
                <form v-if="can.manage" class="mt-3 flex items-center gap-2" @submit.prevent="addTemplateItem(tpl)">
                    <input v-model="newItemLabel[tpl.id]" placeholder="Add a step…" class="flex-1 rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm outline-none focus:border-base focus:ring-2 focus:ring-base/15" />
                    <button type="submit" class="tap-target rounded-xl bg-tint px-3 py-2 text-xs font-semibold text-deep disabled:opacity-40" :disabled="!newItemLabel[tpl.id]">Add</button>
                </form>
            </div>
        </section>

        <AppModal :show="leaveTypeOpen" :title="editing ? 'Edit leave type' : 'Add leave type'" icon="ti ti-calendar-cog" @close="leaveTypeOpen = false">
            <form class="space-y-4" @submit.prevent="submitLeaveType">
                <FormTextInput id="lt-name" v-model="leaveTypeForm.name" label="Name" icon="ti ti-tag" :error="leaveTypeForm.errors.name" required />
                <FormTextInput id="lt-desc" v-model="leaveTypeForm.description" label="Description (optional)" icon="ti ti-text-caption" :error="leaveTypeForm.errors.description" />
                <FormTextInput id="lt-days" v-model="leaveTypeForm.allowance_days" type="number" label="Allowance (days / year)" icon="ti ti-hash" :error="leaveTypeForm.errors.allowance_days" required />
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-ink">Colour</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="token in colorTokens"
                            :key="token"
                            type="button"
                            class="h-8 w-8 rounded-full ring-2 ring-offset-2 transition"
                            :class="[dotClass(token), leaveTypeForm.color === token ? 'ring-ink/40' : 'ring-transparent']"
                            :aria-label="token"
                            @click="leaveTypeForm.color = token"
                        />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-ink/70">
                    <input v-model="leaveTypeForm.is_active" type="checkbox" class="rounded border-ink/30" />
                    Active
                </label>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="leaveTypeOpen = false" />
                    <FormButton type="submit" variant="primary" label="Save" :loading="leaveTypeForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { leaveDotClass } from '@/utils/hrStatus';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    leaveTypes: { type: Array, default: () => [] },
    colorTokens: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const dotClass = (token) => leaveDotClass(token);

const leaveTypeOpen = ref(false);
const editing = ref(null);
const leaveTypeForm = useForm({ name: '', description: '', allowance_days: 0, color: 'base', is_active: true });

const openLeaveType = (type = null) => {
    editing.value = type;
    leaveTypeForm.clearErrors();
    leaveTypeForm.name = type?.name || '';
    leaveTypeForm.description = type?.description || '';
    leaveTypeForm.allowance_days = type?.allowance_days ?? 0;
    leaveTypeForm.color = type?.color || 'base';
    leaveTypeForm.is_active = type ? type.is_active : true;
    leaveTypeOpen.value = true;
};

const submitLeaveType = () => {
    if (editing.value) {
        leaveTypeForm.patch(route('admin.hr.leave-types.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { leaveTypeOpen.value = false; },
        });
    } else {
        leaveTypeForm.post(route('admin.hr.leave-types.store'), {
            preserveScroll: true,
            onSuccess: () => { leaveTypeOpen.value = false; },
        });
    }
};

const deleteLeaveType = (type) => {
    if (confirm(`Delete the ${type.name} leave type?`)) {
        router.delete(route('admin.hr.leave-types.destroy', type.id), { preserveScroll: true });
    }
};

const newItemLabel = reactive({});
const addTemplateItem = (tpl) => {
    const label = newItemLabel[tpl.id];
    if (!label) return;
    router.post(route('admin.hr.templates.items.store', tpl.id), { label }, {
        preserveScroll: true,
        onSuccess: () => { newItemLabel[tpl.id] = ''; },
    });
};
const deleteTemplateItem = (item) => {
    router.delete(route('admin.hr.templates.items.destroy', item.id), { preserveScroll: true });
};
</script>
