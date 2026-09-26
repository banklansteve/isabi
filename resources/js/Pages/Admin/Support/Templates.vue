<template>
    <Head title="Chat templates" />

    <AdminChrome title="Chat templates" eyebrow="Team replies for Customer support" />

    <SupportWorkspaceNav />

    <p class="mb-4 text-[13px] font-medium leading-relaxed text-ink/50">
        Ops insert these from the CS composer. Placeholders:
        <span class="font-semibold text-ink/65">{first_name}</span> (artisan),
        <span class="font-semibold text-ink/65">{agent}</span> (your first name).
        <span class="font-semibold text-ink/65">{name}</span> also maps to first name.
    </p>

    <form
        class="mb-5 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        @submit.prevent="save"
    >
        <h2 class="text-[15px] font-bold text-ink">
            {{ editingId ? 'Edit template' : 'New team template' }}
        </h2>
        <p class="mt-0.5 text-[13px] font-medium text-ink/45">
            Pick a category, write the copy, and every Customer Support agent can insert it.
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <FormSelect
                id="tpl-moment"
                v-model="form.moment"
                label="Category"
                :options="momentSelectOptions"
                :error="form.errors.moment"
            />
            <FormSelect
                id="tpl-topic"
                v-model="form.topic_key"
                label="Topic (optional)"
                placeholder="Any topic"
                :options="topicSelectOptions"
                :error="form.errors.topic_key"
            />
            <div class="sm:col-span-2">
                <FormTextInput
                    id="tpl-title"
                    v-model="form.title"
                    label="Title"
                    placeholder="Short label in the picker"
                    :error="form.errors.title"
                />
            </div>
            <div class="sm:col-span-2">
                <FormTextarea
                    id="tpl-body"
                    v-model="form.body"
                    label="Body"
                    :rows="5"
                    placeholder="Hi {first_name}, …"
                    :error="form.errors.body"
                />
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <FormButton
                type="submit"
                variant="primary"
                :label="editingId ? 'Update template' : 'Save template'"
                :loading="form.processing"
                loading-label="Saving…"
                class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
            />
            <FormButton
                v-if="editingId"
                type="button"
                variant="secondary"
                label="Cancel"
                class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                @click="resetForm"
            />
        </div>
    </form>

    <section
        v-for="group in grouped"
        :key="group.key"
        class="mb-4 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-[15px] font-bold text-ink">{{ group.label }}</h2>
                <p class="mt-0.5 text-[13px] font-medium text-ink/45">{{ group.hint }}</p>
            </div>
            <FormButton
                type="button"
                variant="secondary"
                label="Add in this category"
                icon-left="ti ti-plus"
                class="!shrink-0 !rounded-xl !px-3 !py-2 !text-[12px]"
                @click="startInCategory(group.key)"
            />
        </div>

        <AdminEmpty
            v-if="!group.items.length"
            class="mt-3"
            title="None yet"
            description="Add a template for this category."
            icon="ti ti-message-2"
        />
        <ul v-else class="mt-3 divide-y divide-ink/[0.06]">
            <li v-for="item in group.items" :key="item.id" class="py-3">
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-ink">
                            {{ item.title }}
                            <span
                                v-if="item.is_system"
                                class="ms-1 rounded-full bg-pale px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/40"
                            >
                                Built-in
                            </span>
                            <span
                                v-if="item.topic_key"
                                class="ms-1 rounded-full bg-tint px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-deep"
                            >
                                {{ topicLabel(item.topic_key) }}
                            </span>
                        </p>
                        <p class="mt-1 whitespace-pre-wrap text-[13px] font-medium leading-relaxed text-ink/55">{{ item.body }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-[12px] font-semibold text-base-action transition-colors hover:bg-tint"
                            @click="edit(item)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-[12px] font-semibold text-coral-deep transition-colors hover:bg-coral/10"
                            @click="askRemove(item)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </li>
        </ul>
    </section>

    <AdminConfirmDialog
        :open="!!removing"
        title="Delete this template?"
        :description="removing?.is_system
            ? 'This built-in reply will be removed for every Customer Support agent. You can add a new one later.'
            : 'Ops will no longer see this reply in the composer.'"
        confirm-label="Delete template"
        tone="danger"
        :require-reason="false"
        :processing="removingBusy"
        @close="removing = null"
        @confirm="confirmRemove"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import SupportWorkspaceNav from '@/Components/Admin/SupportWorkspaceNav.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    moments: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
});

const editingId = ref(null);
const removing = ref(null);
const removingBusy = ref(false);

const form = useForm({
    title: '',
    body: '',
    moment: props.moments[0]?.key || 'open',
    topic_key: '',
    scope: 'team',
});

const momentSelectOptions = computed(() =>
    (props.moments || []).map((moment) => ({
        value: moment.key,
        label: moment.label,
    })),
);

const topicSelectOptions = computed(() => [
    { value: '', label: 'Any topic' },
    ...(props.topics || []).map((topic) => ({
        value: topic.key,
        label: topic.label,
    })),
]);

const grouped = computed(() =>
    (props.moments || []).map((moment) => ({
        ...moment,
        items: props.templates.filter((item) => item.moment === moment.key),
    })),
);

const topicLabel = (key) =>
    (props.topics || []).find((topic) => topic.key === key)?.label || key;

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.moment = props.moments[0]?.key || 'open';
    form.scope = 'team';
    form.topic_key = '';
};

const startInCategory = (momentKey) => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.moment = momentKey;
    form.scope = 'team';
    form.topic_key = '';
    form.title = '';
    form.body = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const edit = (item) => {
    editingId.value = item.id;
    form.title = item.title;
    form.body = item.body;
    form.moment = item.moment;
    form.topic_key = item.topic_key || '';
    form.scope = 'team';
    form.clearErrors();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const save = () => {
    const payload = {
        title: form.title,
        body: form.body,
        moment: form.moment,
        topic_key: form.topic_key || null,
        scope: 'team',
    };

    if (editingId.value) {
        form.transform(() => payload).patch(route('admin.support.canned.update', editingId.value), {
            preserveScroll: true,
            onSuccess: resetForm,
        });
        return;
    }

    form.transform(() => payload).post(route('admin.support.canned.store'), {
        preserveScroll: true,
        onSuccess: resetForm,
    });
};

const askRemove = (item) => {
    removing.value = item;
};

const confirmRemove = () => {
    if (!removing.value) {
        return;
    }
    removingBusy.value = true;
    router.delete(route('admin.support.canned.destroy', removing.value.id), {
        preserveScroll: true,
        onFinish: () => {
            removingBusy.value = false;
            removing.value = null;
        },
    });
};
</script>
