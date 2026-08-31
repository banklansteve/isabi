<template>
    <Head title="Message templates" />

    <AdminChrome title="Message templates" eyebrow="Templated outreach" />

    <div v-if="!messaging_enabled" class="rounded-2xl bg-white p-6 text-sm font-medium text-ink/50 shadow-premium ring-1 ring-ink/[0.05]">
        Templated messaging is disabled in Super Admin settings.
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="space-y-4">
            <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                <form class="flex gap-2" @submit.prevent="search">
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search name, UID, email, business…"
                        class="min-w-0 flex-1 rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                    <FormButton type="submit" variant="primary" label="Search" />
                </form>

                <ul v-if="artisans.length" class="mt-4 divide-y divide-ink/[0.06]">
                    <li v-for="user in artisans" :key="user.id">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 py-3 text-left hover:bg-pale/60"
                            @click="selectedUser = user"
                        >
                            <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                                <img v-if="user.avatar_url" :src="user.avatar_url" alt="" class="h-full w-full object-cover" />
                                <span v-else>{{ initials(user.name) }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-bold text-ink">{{ user.business_name || user.name }}</span>
                                <span class="block truncate text-[12px] font-medium text-ink/40">{{ user.email }} · {{ user.uid }}</span>
                            </span>
                        </button>
                    </li>
                </ul>
                <p v-else-if="q" class="mt-4 text-[13px] font-medium text-ink/40">No site users matched.</p>
            </div>

            <div v-if="selectedUser" class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">To</p>
                <p class="mt-1 text-sm font-bold text-ink">{{ selectedUser.business_name || selectedUser.name }}</p>
                <p class="text-[12px] font-medium text-ink/45">{{ selectedUser.email }}</p>

                <label class="mt-4 block">
                    <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Template</span>
                    <select
                        v-model="templateUid"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2.5 text-sm font-semibold outline-none"
                    >
                        <option disabled value="">Pick a template</option>
                        <option v-for="tpl in templates" :key="tpl.uid" :value="tpl.uid">{{ tpl.title }}</option>
                    </select>
                </label>

                <label v-if="canEditSubject" class="mt-3 block">
                    <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Subject</span>
                    <input v-model="subject" type="text" class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15" />
                </label>

                <label class="mt-3 block">
                    <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Message</span>
                    <textarea
                        v-model="body"
                        rows="7"
                        :readonly="!canEditBody"
                        class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </label>

                <div class="mt-3 flex flex-wrap gap-3">
                    <label class="inline-flex items-center gap-2 text-[13px] font-semibold text-ink">
                        <input v-model="channels" type="checkbox" value="in_app" class="rounded border-ink/20 text-base-action" />
                        In-app
                    </label>
                    <label class="inline-flex items-center gap-2 text-[13px] font-semibold text-ink">
                        <input v-model="channels" type="checkbox" value="email" class="rounded border-ink/20 text-base-action" />
                        Email
                    </label>
                </div>

                <p v-if="requires_approval" class="mt-3 text-[12px] font-medium text-amber-800/80">
                    This send will wait for Super Admin approval.
                </p>

                <FormButton
                    class="mt-4"
                    variant="primary"
                    label="Send"
                    :disabled="!templateUid || !channels.length"
                    @click="send"
                />
            </div>
        </section>

        <aside v-if="is_super" class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
            <h2 class="text-sm font-bold text-ink">Templates</h2>
            <p class="mt-1 text-[12px] font-medium text-ink/45">Create policy and moderation templates for ops.</p>
            <form class="mt-4 space-y-2" @submit.prevent="saveTemplate">
                <input v-model="newTpl.title" required placeholder="Title" class="w-full rounded-xl border border-ink/10 px-3 py-2 text-[13px] outline-none" />
                <input v-model="newTpl.subject" required placeholder="Subject" class="w-full rounded-xl border border-ink/10 px-3 py-2 text-[13px] outline-none" />
                <textarea v-model="newTpl.body" required rows="5" placeholder="Body — use {name}" class="w-full rounded-xl border border-ink/10 px-3 py-2 text-[13px] outline-none" />
                <FormButton type="submit" variant="primary" label="Save template" />
            </form>
            <ul class="mt-4 space-y-2">
                <li v-for="tpl in all_templates" :key="tpl.uid" class="rounded-xl bg-pale px-3 py-2 text-[12px] font-semibold text-ink">
                    {{ tpl.title }}
                    <span class="text-ink/40">· {{ tpl.is_active ? 'active' : 'off' }}</span>
                </li>
            </ul>
        </aside>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    q: { type: String, default: '' },
    artisans: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    all_templates: { type: Array, default: () => [] },
    messaging_enabled: { type: Boolean, default: true },
    requires_approval: { type: Boolean, default: false },
    is_super: { type: Boolean, default: false },
});

const query = ref(props.q);
const selectedUser = ref(null);
const templateUid = ref('');
const subject = ref('');
const body = ref('');
const channels = ref(['in_app']);
const newTpl = reactive({ title: '', subject: '', body: '' });

const activeTemplate = computed(() => props.templates.find((item) => item.uid === templateUid.value));
const canEditSubject = computed(() => (activeTemplate.value?.editable_keys || []).includes('subject'));
const canEditBody = computed(() => (activeTemplate.value?.editable_keys || []).includes('body'));

watch(activeTemplate, (tpl) => {
    if (!tpl) return;
    subject.value = tpl.subject;
    body.value = tpl.body;
});

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const search = () => {
    router.get(route('admin.ops-messages.index'), { q: query.value }, { preserveState: true, replace: true });
};

const send = () => {
    if (!selectedUser.value || !templateUid.value) return;
    router.post(route('admin.ops-messages.send'), {
        user_id: selectedUser.value.id,
        template_uid: templateUid.value,
        subject: subject.value,
        body: body.value,
        channels: channels.value,
    }, { preserveScroll: true });
};

const saveTemplate = () => {
    router.post(route('admin.ops-messages.templates.store'), {
        ...newTpl,
        editable_keys: ['body'],
        category: 'general',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newTpl.title = '';
            newTpl.subject = '';
            newTpl.body = '';
        },
    });
};
</script>
