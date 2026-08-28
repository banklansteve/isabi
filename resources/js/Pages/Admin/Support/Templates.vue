<template>
    <Head title="Chat templates" />

    <AdminChrome title="Chat templates" eyebrow="Open, close, and review nudges" />

    <SupportWorkspaceNav />

    <form
        class="mb-4 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        @submit.prevent="save"
    >
        <h2 class="text-[15px] font-bold text-ink">{{ editingId ? 'Edit template' : 'New team template' }}</h2>
        <p class="mt-0.5 text-[13px] font-medium text-ink/45">
            Operations staff insert these from the composer. Placeholders fill in on insert:
            artisan name and the agent’s first name.
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="block text-[12px] font-semibold text-ink/50">
                When to use it
                <select
                    v-model="form.moment"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                >
                    <option v-for="moment in moments" :key="moment.key" :value="moment.key">
                        {{ moment.label }}
                    </option>
                </select>
            </label>
            <label class="block text-[12px] font-semibold text-ink/50">
                Topic
                <select
                    v-model="form.topic_key"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                >
                    <option value="">Any topic</option>
                    <option v-for="topic in topics" :key="topic.key" :value="topic.key">
                        {{ topic.label }}
                    </option>
                </select>
            </label>
            <label class="block text-[12px] font-semibold text-ink/50 sm:col-span-2">
                Title
                <input
                    v-model="form.title"
                    type="text"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
            </label>
            <label class="block text-[12px] font-semibold text-ink/50 sm:col-span-2">
                Body
                <textarea
                    v-model="form.body"
                    rows="5"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium leading-relaxed outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
            </label>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button
                type="submit"
                class="rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-base-hover disabled:opacity-50"
                :disabled="form.processing"
            >
                {{ editingId ? 'Update template' : 'Save template' }}
            </button>
            <button
                v-if="editingId"
                type="button"
                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/50 hover:bg-pale"
                @click="resetForm"
            >
                Cancel
            </button>
        </div>
    </form>

    <section
        v-for="group in grouped"
        :key="group.key"
        class="mb-4 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
    >
        <h2 class="text-[15px] font-bold text-ink">{{ group.label }}</h2>
        <p class="mt-0.5 text-[13px] font-medium text-ink/45">{{ group.hint }}</p>
        <AdminEmpty
            v-if="!group.items.length"
            class="mt-3"
            title="None yet"
            description="Add a template above for this moment."
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
                        </p>
                        <p class="mt-1 whitespace-pre-wrap text-[13px] font-medium leading-relaxed text-ink/55">{{ item.body }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-[12px] font-semibold text-base-action hover:bg-tint"
                            @click="edit(item)"
                        >
                            Edit
                        </button>
                        <button
                            v-if="!item.is_system"
                            type="button"
                            class="rounded-xl px-3 py-2 text-[12px] font-semibold text-coral-deep hover:bg-coral/10"
                            @click="remove(item)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </li>
        </ul>
    </section>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import SupportWorkspaceNav from '@/Components/Admin/SupportWorkspaceNav.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    moments: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
});

const editingId = ref(null);

const form = useForm({
    title: '',
    body: '',
    moment: props.moments[0]?.key || 'open',
    topic_key: '',
    scope: 'team',
});

const grouped = computed(() =>
    (props.moments || []).map((moment) => ({
        ...moment,
        items: props.templates.filter((item) => item.moment === moment.key),
    })),
);

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.moment = props.moments[0]?.key || 'open';
    form.scope = 'team';
    form.topic_key = '';
};

const edit = (item) => {
    editingId.value = item.id;
    form.title = item.title;
    form.body = item.body;
    form.moment = item.moment;
    form.topic_key = item.topic_key || '';
    form.scope = 'team';
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

const remove = (item) => {
    router.delete(route('admin.support.canned.destroy', item.id), { preserveScroll: true });
};
</script>
