<template>
    <AdminConfirmDialog
        :open="open"
        title="Warn this user"
        description="Send a templated policy or moderation message. Your send is logged."
        confirm-label="Send"
        :require-reason="false"
        :processing="busy"
        @close="emit('close')"
        @confirm="send"
    >
        <div v-if="loading" class="mt-4 text-[13px] font-medium text-ink/45">Loading templates…</div>
        <div v-else-if="!messagingEnabled" class="mt-4 text-[13px] font-medium text-ink/45">
            Templated messaging is disabled in Super Admin settings.
        </div>
        <div v-else class="mt-4 space-y-3">
            <p v-if="user" class="text-[13px] font-semibold text-ink">
                {{ user.business_name || user.name }}
                <span class="font-medium text-ink/45">· {{ user.email }}</span>
            </p>

            <label class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Template</span>
                <select
                    v-model="templateUid"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2.5 text-sm font-semibold outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                >
                    <option disabled value="">Pick a template</option>
                    <option v-for="tpl in templates" :key="tpl.uid" :value="tpl.uid">{{ tpl.title }}</option>
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
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Message</span>
                <textarea
                    v-model="body"
                    rows="6"
                    :readonly="!canEditBody"
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

            <p v-if="requiresApproval" class="text-[12px] font-medium text-amber-800/80">
                This send will wait for Super Admin approval.
            </p>
        </div>
    </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import { toast } from '@/utils/adminRange';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: null },
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
const channels = ref(['in_app']);

const activeTemplate = computed(() => templates.value.find((item) => item.uid === templateUid.value));
const canEditSubject = computed(() => (activeTemplate.value?.editable_keys || []).includes('subject'));
const canEditBody = computed(() => (activeTemplate.value?.editable_keys || []).includes('body'));

watch(activeTemplate, (tpl) => {
    if (!tpl) {
        return;
    }
    subject.value = tpl.subject;
    body.value = tpl.body;
});

const reset = () => {
    templateUid.value = '';
    subject.value = '';
    body.value = '';
    channels.value = ['in_app'];
};

const loadOptions = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(route('admin.ops-messages.options'));
        templates.value = data.templates || [];
        messagingEnabled.value = data.messaging_enabled !== false;
        requiresApproval.value = !!data.requires_approval;
    } catch {
        toast({ type: 'error', title: 'Couldn’t load templates', message: 'Try again in a moment.' });
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.open,
    (value) => {
        if (value) {
            reset();
            loadOptions();
        }
    },
);

const send = async () => {
    if (!props.user?.id || !templateUid.value || !channels.value.length || !messagingEnabled.value || loading.value) {
        return;
    }

    busy.value = true;
    try {
        await axios.post(route('admin.ops-messages.send'), {
            user_id: props.user.id,
            template_uid: templateUid.value,
            subject: subject.value,
            body: body.value,
            channels: channels.value,
        });
        toast({ type: 'success', title: 'Message sent', message: requiresApproval.value ? 'Waiting for Super Admin approval.' : 'Delivered to the user.' });
        emit('sent');
        emit('close');
    } catch (error) {
        toast({ type: 'error', title: 'Couldn’t send', message: error.response?.data?.message || 'Try again.' });
    } finally {
        busy.value = false;
    }
};

</script>
