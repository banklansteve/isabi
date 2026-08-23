<template>
    <Head title="Message templates" />

    <AdminChrome title="Templates" eyebrow="Reusable copy" />
        <form
            class="mb-4 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
            @submit.prevent="save"
        >
            <h2 class="text-[15px] font-bold text-ink">{{ editingId ? 'Edit template' : 'New template' }}</h2>
            <p class="mt-0.5 text-[13px] font-medium text-ink/45">
                Recurring sends — welcome, renewal, referral milestones — should start here, not from a blank box.
            </p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="block text-[12px] font-semibold text-ink/50">
                    Audience
                    <select
                        v-model="form.audience"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                        :disabled="editingLocked"
                    >
                        <option value="users">Customers</option>
                        <option value="staff">Staff</option>
                    </select>
                </label>
                <label class="block text-[12px] font-semibold text-ink/50">
                    Name
                    <input
                        v-model="form.name"
                        type="text"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </label>
                <label class="block text-[12px] font-semibold text-ink/50 sm:col-span-2">
                    Subject
                    <input
                        v-model="form.subject"
                        type="text"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </label>
                <label class="block text-[12px] font-semibold text-ink/50 sm:col-span-2">
                    Body
                    <textarea
                        v-model="form.body"
                        rows="6"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium leading-relaxed outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </label>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <label
                    v-for="channel in channelsForAudience"
                    :key="channel.value"
                    class="inline-flex items-center gap-2 rounded-full bg-pale px-3 py-1.5 text-[12px] font-semibold text-ink/60"
                >
                    <input
                        v-model="form.channels"
                        type="checkbox"
                        :value="channel.value"
                        class="rounded border-ink/20 text-base-action focus:ring-base/20"
                    />
                    {{ channel.label }}
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
            <p v-if="form.errors.name" class="mt-2 text-xs font-semibold text-red-500">{{ form.errors.name }}</p>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!templates.length"
                title="No templates"
                description="Save the copy you reuse — welcome, renewals, referral notes."
                icon="ti ti-copy"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="item in templates" :key="item.id" class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-ink">{{ item.name }}</p>
                                <span class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/45">
                                    {{ item.audience === 'staff' ? 'Staff' : 'Customers' }}
                                </span>
                                <span
                                    v-if="item.is_system"
                                    class="rounded-full bg-tint px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-deep"
                                >
                                    System
                                </span>
                            </div>
                            <p class="mt-1 text-[13px] font-medium text-ink/50">{{ item.subject }}</p>
                            <p class="mt-1 line-clamp-2 text-[13px] leading-relaxed text-ink/40">{{ item.body }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-xl bg-pale px-3 py-2 text-xs font-bold text-ink/60"
                                @click="edit(item)"
                            >
                                Edit
                            </button>
                            <Link
                                v-if="!item.is_system"
                                :href="route('admin.messaging.templates.destroy', item.id)"
                                method="delete"
                                as="button"
                                class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600"
                            >
                                Delete
                            </Link>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
</template>

<script setup>
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    templates: { type: Array, default: () => [] },
});

const editingId = ref(null);
const editingLocked = ref(false);

const form = useForm({
    audience: 'users',
    name: '',
    subject: '',
    body: '',
    channels: ['in_app', 'email'],
});

const channelsForAudience = computed(() => {
    const channels = [
        { value: 'in_app', label: 'In-app' },
        { value: 'email', label: 'Email' },
    ];
    if (form.audience === 'users') {
        channels.push({ value: 'whatsapp', label: 'WhatsApp' });
    }
    return channels;
});

const resetForm = () => {
    editingId.value = null;
    editingLocked.value = false;
    form.reset();
    form.audience = 'users';
    form.channels = ['in_app', 'email'];
    form.clearErrors();
};

const edit = (item) => {
    editingId.value = item.id;
    editingLocked.value = !!item.is_system;
    form.audience = item.audience;
    form.name = item.name;
    form.subject = item.subject;
    form.body = item.body;
    form.channels = [...(item.channels || [])];
};

const save = () => {
    if (form.audience === 'staff') {
        form.channels = form.channels.filter((channel) => channel !== 'whatsapp');
    }

    if (editingId.value) {
        form.patch(route('admin.messaging.templates.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
        return;
    }

    form.post(route('admin.messaging.templates.store'), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};
</script>
