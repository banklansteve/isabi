<template>
    <AdminConfirmDialog
        :open="open"
        :title="dialogTitle"
        :description="dialogDescription"
        confirm-label="Send message"
        :require-reason="false"
        :processing="busy"
        wide
        @close="emit('close')"
        @confirm="send"
    >
        <div v-if="loading" class="mt-4 text-[13px] font-medium text-ink/45">Loading templates…</div>
        <div v-else-if="!messagingEnabled" class="mt-4 text-[13px] font-medium text-ink/45">
            Templated messaging is disabled in Super Admin settings.
        </div>
        <div v-else class="mt-4 space-y-3">
            <p v-if="user" class="text-[13px] font-semibold text-ink">
                {{ user.business_name || user.name || user.person }}
                <span v-if="user.email" class="font-medium text-ink/45">· {{ user.email }}</span>
            </p>

            <label class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Template</span>
                <select
                    v-model="templateUid"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2.5 text-sm font-semibold outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                >
                    <option disabled value="">Pick a template</option>
                    <option v-for="tpl in filteredTemplates" :key="tpl.uid" :value="tpl.uid">{{ tpl.title }}</option>
                </select>
            </label>

            <label v-if="canEditSubject" class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Subject</span>
                <input
                    v-model="subject"
                    type="text"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
            </label>

            <label class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Email / in-app message</span>
                <textarea
                    v-model="body"
                    rows="5"
                    :readonly="!canEditBody"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
            </label>

            <label v-if="canEditWhatsapp || whatsappBody" class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">WhatsApp text</span>
                <textarea
                    v-model="whatsappBody"
                    rows="3"
                    :readonly="!canEditWhatsapp"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
            </label>

            <div class="flex flex-wrap gap-3">
                <label class="inline-flex items-center gap-2 text-[13px] font-semibold text-ink">
                    <input v-model="channels" type="checkbox" value="in_app" class="rounded border-ink/20 text-base-action" />
                    In-app
                </label>
                <label class="inline-flex items-center gap-2 text-[13px] font-semibold text-ink">
                    <input v-model="channels" type="checkbox" value="email" class="rounded border-ink/20 text-base-action" />
                    Email
                </label>
            </div>

            <a
                v-if="waHref"
                :href="waHref"
                target="_blank"
                rel="noopener"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-[13px] font-bold text-emerald-700 transition-colors hover:bg-emerald-100"
            >
                <i class="ti ti-brand-whatsapp text-base" aria-hidden="true" />
                Open WhatsApp with this message
            </a>
            <p v-else-if="user" class="text-[12px] font-medium text-ink/40">
                No WhatsApp number on file for this artisan.
            </p>

            <p v-if="requiresApproval" class="text-[12px] font-medium text-amber-800/80">
                Email / in-app sends may wait for Super Admin approval.
            </p>
        </div>
    </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import { toast } from '@/utils/adminRange';
import { waLink } from '@/utils/waLink';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: null },
    category: { type: String, default: '' },
    categories: { type: Array, default: () => [] },
    title: { type: String, default: 'Reach out' },
    description: {
        type: String,
        default: 'Send a templated email and/or in-app message. WhatsApp opens a pre-filled chat.',
    },
});

const emit = defineEmits(['close', 'sent']);

const busy = ref(false);
const loading = ref(false);
const templates = ref([]);
const messagingEnabled = ref(true);
const requiresApproval = ref(false);
const templateUid = ref('');
const subject = ref('');
const body = ref('');
const whatsappBody = ref('');
const channels = ref(['in_app', 'email']);

const dialogTitle = computed(() => props.title);
const dialogDescription = computed(() => props.description);

const filteredTemplates = computed(() => {
    const allowed = props.categories.length
        ? props.categories
        : (props.category ? [props.category] : []);

    if (!allowed.length) {
        return templates.value;
    }

    return templates.value.filter((item) => allowed.includes(item.category));
});

const activeTemplate = computed(() => filteredTemplates.value.find((item) => item.uid === templateUid.value));
const canEditSubject = computed(() => (activeTemplate.value?.editable_keys || []).includes('subject'));
const canEditBody = computed(() => (activeTemplate.value?.editable_keys || []).includes('body'));
const canEditWhatsapp = computed(() => (activeTemplate.value?.editable_keys || []).includes('whatsapp_body'));

const personalize = (text) => {
    const first = props.user?.person || props.user?.first_name || String(props.user?.name || '').split(' ')[0] || 'there';
    const name = props.user?.name || props.user?.person || first;
    const business = props.user?.business_name || name;

    return String(text || '')
        .replaceAll('{{first_name}}', first)
        .replaceAll('{{name}}', name)
        .replaceAll('{{business_name}}', business)
        .replaceAll('{first_name}', first)
        .replaceAll('{name}', name)
        .replaceAll('{business_name}', business);
};

const waHref = computed(() => {
    if (!props.user?.whatsapp || !whatsappBody.value.trim()) {
        return '';
    }

    return waLink(props.user.whatsapp, personalize(whatsappBody.value));
});

watch(activeTemplate, (tpl) => {
    if (!tpl) {
        return;
    }
    subject.value = personalize(tpl.subject);
    body.value = personalize(tpl.body);
    whatsappBody.value = personalize(tpl.whatsapp_body || tpl.body);
});

const reset = () => {
    templateUid.value = '';
    subject.value = '';
    body.value = '';
    whatsappBody.value = '';
    channels.value = ['in_app', 'email'];
};

const loadOptions = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(route('admin.ops-messages.options'));
        templates.value = data.templates || [];
        messagingEnabled.value = data.messaging_enabled !== false;
        requiresApproval.value = !!data.requires_approval;

        const first = filteredTemplates.value[0];
        if (first) {
            templateUid.value = first.uid;
        }
    } catch {
        toast({ type: 'error', title: 'Couldn’t load templates', message: 'Try again in a moment.' });
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            reset();
            loadOptions();
        }
    },
);

const send = async () => {
    if (!props.user?.id || !templateUid.value || !channels.value.length) {
        toast({ type: 'error', title: 'Pick a template and at least one channel' });
        return;
    }

    busy.value = true;
    try {
        const { data } = await axios.post(route('admin.ops-messages.send'), {
            user_id: props.user.id,
            template_uid: templateUid.value,
            subject: subject.value,
            body: body.value,
            channels: channels.value,
        });
        toast(data.toast || { type: 'success', title: 'Sent', message: 'Message queued.' });
        emit('sent', data);
        emit('close');
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t send',
            message: error?.response?.data?.message || 'Try again in a moment.',
        });
    } finally {
        busy.value = false;
    }
};
</script>
