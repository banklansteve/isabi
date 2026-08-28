<template>
    <Head title="Letter templates" />

    <AdminChrome title="Letter templates" eyebrow="Reviewed before each notice is issued" />

    <p class="mb-5 max-w-2xl text-sm font-medium leading-relaxed text-ink/55">
        Templates pre-fill a notice. The case owner still reviews and edits the letter before it is issued. Placeholders are replaced from the case file.
    </p>

    <div class="space-y-4">
        <article v-for="template in templates" :key="template.id" class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-ink">{{ template.name }}</h2>
                    <p class="mt-0.5 text-xs font-medium text-ink/40">{{ template.outcome_label }}</p>
                </div>
                <FormButton v-if="can.manage" variant="secondary" label="Edit" @click="openEdit(template)" />
            </div>
            <pre class="mt-4 whitespace-pre-wrap text-[13px] font-medium leading-relaxed text-ink/65">{{ template.body }}</pre>
        </article>
    </div>

    <AdminDrawer :open="!!editing" :title="editing ? editing.name : 'Template'" eyebrow="Editable letter" @close="editing = null">
        <form v-if="editing" class="space-y-4" @submit.prevent="save">
            <FormTextInput id="tpl-name" v-model="form.name" label="Name" icon="ti ti-heading" :error="form.errors.name" required />
            <FormTextarea id="tpl-body" v-model="form.body" label="Body" :error="form.errors.body" required />
            <p class="text-[12px] font-medium text-ink/40">{{ placeholders.join(', ') }}</p>
            <div class="flex justify-end gap-2">
                <FormButton type="button" variant="secondary" label="Cancel" @click="editing = null" />
                <FormButton type="submit" variant="primary" label="Save template" :loading="form.processing" loading-label="Saving…" />
            </div>
        </form>
    </AdminDrawer>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    placeholders: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const editing = ref(null);
const form = useForm({ name: '', body: '', is_active: true });

const openEdit = (template) => {
    editing.value = template;
    form.name = template.name;
    form.body = template.body;
    form.is_active = template.is_active;
};

const save = () => {
    form.patch(route('admin.hr.discipline.templates.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
};
</script>
