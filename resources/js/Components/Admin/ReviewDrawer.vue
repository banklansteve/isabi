<template>
    <AdminDrawer
        :open="open"
        size="lg"
        :title="heading.title"
        :eyebrow="heading.eyebrow"
        @close="$emit('close')"
        @entered="caseEntered = true"
    >
        <div v-if="!(record && caseEntered)" class="space-y-4">
            <div class="h-8 w-48 animate-pulse rounded-xl bg-pale" />
            <div class="h-24 animate-pulse rounded-2xl bg-pale" />
            <div class="h-40 animate-pulse rounded-2xl bg-pale" />
        </div>

        <div v-else class="space-y-6">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-tint px-2.5 py-0.5 text-[11px] font-bold text-deep">{{ record.rating }}★</span>
                <span :class="[pill, visibilityClass]">{{ record.visibility_label }}</span>
                <span v-if="record.flagged" class="rounded-full bg-coral-tint px-2.5 py-0.5 text-[11px] font-bold text-coral-deep">Flagged</span>
                <span v-if="record.referred" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-bold text-violet-700">Referred</span>
            </div>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Review</p>
                <p class="mt-3 whitespace-pre-wrap text-[15px] font-medium leading-relaxed text-ink">
                    {{ record.comment || 'No written comment.' }}
                </p>
                <p class="mt-3 text-[12px] font-semibold text-ink/40">{{ record.submitted_at }}</p>
                <img
                    v-if="record.photo_url"
                    :src="record.photo_url"
                    alt=""
                    class="mt-4 w-full rounded-2xl object-cover"
                />
            </div>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Client</p>
                <p class="mt-2 text-sm font-semibold text-ink">{{ record.client || 'Anonymous client' }}</p>
                <p class="mt-1 text-[13px] font-medium text-ink/55">
                    Would recommend: {{ record.would_recommend ? 'Yes' : 'No' }}
                </p>
                <p v-if="record.referred_by" class="mt-1 text-[13px] font-medium text-ink/45">
                    Heard about artisan via: {{ record.referred_by }}
                </p>
            </div>

            <div v-if="record.artisan" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Artisan</p>
                        <p class="mt-2 text-sm font-semibold text-ink">{{ record.artisan.name }}</p>
                        <p v-if="record.artisan.trade" class="mt-0.5 text-xs font-medium text-ink/45">{{ record.artisan.trade }}</p>
                        <p class="mt-0.5 text-xs font-medium text-ink/40">{{ record.artisan.email }}</p>
                        <span
                            v-if="record.artisan.suspended"
                            class="mt-2 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-600"
                        >
                            Suspended
                        </span>
                    </div>
                    <Link
                        v-if="record.artisan.user_url && can.view_users"
                        :href="record.artisan.user_url"
                        :show-progress="false"
                        class="text-xs font-semibold text-base-action hover:text-base-hover"
                    >
                        Open profile
                    </Link>
                </div>
            </div>

            <div v-if="record.job" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Linked job</p>
                <p class="mt-2 text-sm font-medium leading-relaxed text-ink/80">{{ record.job.description }}</p>
                <p class="mt-1 text-xs font-medium text-ink/45">
                    {{ record.job.client_name || 'No client' }} · {{ record.job.worked_on || '—' }}
                </p>
                <Link
                    v-if="record.job.url"
                    :href="record.job.url"
                    :show-progress="false"
                    class="mt-2 inline-block text-xs font-semibold text-base-action hover:text-base-hover"
                >
                    Open job log
                </Link>
            </div>

            <div v-if="record.flagged || record.flag_reason" class="rounded-2xl bg-coral-tint/60 p-5 ring-1 ring-coral/10">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">Flag</p>
                <p class="mt-2 text-sm font-medium text-ink/80">{{ record.flag_reason || 'Flagged' }}</p>
                <p v-if="record.flagged_at_label" class="mt-1 text-xs font-medium text-ink/45">{{ record.flagged_at_label }}</p>
            </div>

            <div v-if="record.hidden_reason" class="rounded-2xl bg-amber-50/80 p-5 ring-1 ring-amber-100">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-amber-800">Hidden</p>
                <p class="mt-2 text-sm font-medium text-ink/80">{{ record.hidden_reason }}</p>
                <p v-if="record.hidden_at_label" class="mt-1 text-xs font-medium text-ink/45">{{ record.hidden_at_label }}</p>
            </div>

            <div v-if="record.referral" class="rounded-2xl bg-violet-50/80 p-5 ring-1 ring-violet-100">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">Referred for moderation</p>
                <p class="mt-2 text-sm font-semibold text-ink">{{ record.referral.assignee_name }}</p>
                <p class="mt-1 text-sm font-medium leading-relaxed text-ink/70">{{ record.referral.note }}</p>
                <p class="mt-1 text-xs font-medium text-ink/45">
                    {{ record.referral.referred_at }}
                    <span v-if="record.referral.referred_by"> · {{ record.referral.referred_by }}</span>
                </p>
            </div>
        </div>

        <template v-if="can.manage || can.message" #footer>
            <div class="space-y-3">
                <div class="flex flex-wrap gap-x-3 gap-y-1.5 text-[12px] font-semibold">
                    <Link
                        v-if="record?.artisan?.user_url && can.view_users"
                        :href="record.artisan.user_url"
                        :show-progress="false"
                        class="text-base-action hover:text-base-hover"
                    >
                        Open artisan
                    </Link>
                    <Link
                        v-if="record?.job?.url"
                        :href="record.job.url"
                        :show-progress="false"
                        class="text-base-action hover:text-base-hover"
                    >
                        Open job log
                    </Link>
                    <a
                        v-if="record?.artisan?.public_url"
                        :href="record.artisan.public_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-base-action hover:text-base-hover"
                    >
                        Public page
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <FormButton
                        v-if="can.flag"
                        variant="secondary"
                        class="w-full"
                        :label="record?.flagged ? 'Clear flag' : 'Flag'"
                        @click="record?.flagged ? (unflagOpen = true) : (flagOpen = true)"
                    />
                    <FormButton
                        v-if="can.hide && record?.visibility !== 'removed'"
                        variant="secondary"
                        class="w-full"
                        label="Hide"
                        @click="hideOpen = true"
                    />
                    <FormButton
                        v-if="can.remove && record?.visibility !== 'removed'"
                        variant="secondary"
                        class="w-full"
                        label="Delete"
                        @click="removeOpen = true"
                    />
                    <FormButton
                        v-if="can.refer"
                        variant="secondary"
                        class="w-full"
                        label="Refer"
                        @click="openRefer"
                    />
                    <FormButton
                        v-if="can.suspend_user && record?.artisan && !record.artisan.suspended"
                        variant="secondary"
                        class="w-full"
                        label="Suspend artisan"
                        @click="suspendOpen = true"
                    />
                    <FormButton
                        v-if="can.message"
                        variant="primary"
                        class="w-full"
                        label="Message"
                        @click="messageOpen = true"
                    />
                </div>
            </div>
        </template>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="messageOpen"
        title="Message this artisan?"
        description="Sends in-app and email about this review."
        confirm-label="Send message"
        reason-placeholder="Why you are reaching out…"
        :processing="busy === 'message'"
        @close="messageOpen = false"
        @confirm="submitMessage"
    >
        <FormTextInput id="review-message-subject" v-model="messageForm.subject" label="Subject" class="mt-4" />
        <FormTextarea id="review-message-body" v-model="messageForm.body" label="Message" required class="mt-2" />
    </AdminConfirmDialog>

    <ReferToStaffDialog
        :open="referOpen"
        title="Refer this review for moderation?"
        description="Assign a colleague to investigate authenticity or policy concerns."
        confirm-label="Refer for moderation"
        :staff="staff"
        default-queue="moderation"
        :show-queue="true"
        :processing="busy === 'refer'"
        :initial-assignee-id="record?.referral?.assignee_id ? String(record.referral.assignee_id) : ''"
        @close="referOpen = false"
        @confirm="submitRefer"
    />

    <AdminConfirmDialog
        :open="flagOpen"
        title="Flag this review?"
        description="Marks it for moderation immediately."
        confirm-label="Flag review"
        :processing="busy === 'flag'"
        @close="flagOpen = false"
        @confirm="submitFlag"
    />

    <AdminConfirmDialog
        :open="unflagOpen"
        title="Clear this flag?"
        description="The review leaves the flagged queue."
        confirm-label="Clear flag"
        :processing="busy === 'unflag'"
        @close="unflagOpen = false"
        @confirm="submitUnflag"
    />

    <AdminConfirmDialog
        :open="hideOpen"
        title="Hide this review?"
        description="It will no longer show on the public page."
        confirm-label="Hide review"
        tone="danger"
        :processing="busy === 'hide'"
        @close="hideOpen = false"
        @confirm="submitHide"
    />

    <AdminConfirmDialog
        :open="removeOpen"
        title="Delete this review?"
        description="Soft-removes it from the public page. Super Admin approval is required for operations staff."
        confirm-label="Delete review"
        tone="danger"
        confirm-phrase="DELETE"
        :processing="busy === 'remove'"
        @close="removeOpen = false"
        @confirm="submitRemove"
    />

    <AdminConfirmDialog
        :open="suspendOpen"
        title="Suspend this artisan?"
        description="They will be signed out and blocked from signing in."
        confirm-label="Suspend account"
        tone="danger"
        :processing="busy === 'suspend'"
        @close="suspendOpen = false"
        @confirm="submitSuspend"
    />
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import ReferToStaffDialog from '@/Components/Admin/ReferToStaffDialog.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { toast } from '@/utils/adminRange';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    row: { type: Object, default: null },
    panel: { type: Object, default: null },
});

const emit = defineEmits(['close', 'refresh', 'updated']);

const record = computed(() => props.panel?.record || null);
const can = computed(() => props.panel?.can || {});
const staff = computed(() => props.panel?.staff || []);
const heading = computed(() => ({
    title: record.value?.client ? `${record.value.rating}★ from ${record.value.client}` : `${record.value?.rating || ''}★ review`,
    eyebrow: record.value?.uid || props.row?.uid || 'Review',
}));
const pill = 'rounded-full px-2.5 py-0.5 text-[11px] font-bold';
const visibilityClass = computed(() => {
    if (record.value?.visibility === 'removed') return 'bg-ink/10 text-ink/55';
    if (record.value?.visibility === 'hidden') return 'bg-amber-50 text-amber-800';
    if (record.value?.visibility === 'flagged') return 'bg-coral-tint text-coral-deep';
    return 'bg-emerald-50 text-emerald-700';
});

const caseEntered = ref(false);
const flagOpen = ref(false);
const unflagOpen = ref(false);
const hideOpen = ref(false);
const removeOpen = ref(false);
const suspendOpen = ref(false);
const referOpen = ref(false);
const messageOpen = ref(false);
const busy = ref('');

const messageForm = reactive({
    subject: 'About a recent review on Kraftrack',
    body: '',
});

const submitAxios = async (key, request, onSuccess) => {
    busy.value = key;
    try {
        const { data } = await request();
        if (data?.toast) toast(data.toast);
        onSuccess?.(data);
        if (data?.review || data?.record) emit('updated', data);
        emit('refresh');
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t save',
            message: error.response?.data?.message || error.response?.data?.toast?.message || 'Try that again in a moment.',
        });
    } finally {
        busy.value = '';
    }
};

const resetOverlays = () => {
    flagOpen.value = false;
    unflagOpen.value = false;
    hideOpen.value = false;
    removeOpen.value = false;
    suspendOpen.value = false;
    referOpen.value = false;
    messageOpen.value = false;
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            caseEntered.value = !!props.panel?.record;
            return;
        }
        resetOverlays();
    },
);

watch(
    () => record.value?.uid,
    () => resetOverlays(),
);

watch(
    () => record.value,
    (row) => {
        if (!row?.artisan) return;
        messageForm.body = `Hi ${row.artisan.first_name || 'there'}, we wanted to follow up on the ${row.rating}★ review from ${row.client || 'a client'}.`;
    },
);

const openRefer = () => {
    referOpen.value = true;
};

const submitFlag = ({ reason }) => {
    submitAxios('flag', () => axios.post(route('admin.reviews.flag', record.value.uid), { reason }), () => {
        flagOpen.value = false;
    });
};

const submitUnflag = ({ reason }) => {
    submitAxios('unflag', () => axios.post(route('admin.reviews.unflag', record.value.uid), { reason }), () => {
        unflagOpen.value = false;
    });
};

const submitHide = ({ reason }) => {
    submitAxios('hide', () => axios.post(route('admin.reviews.hide', record.value.uid), { reason }), () => {
        hideOpen.value = false;
    });
};

const submitRemove = ({ reason }) => {
    submitAxios('remove', () => axios.post(route('admin.reviews.remove', record.value.uid), { reason }), () => {
        removeOpen.value = false;
    });
};

const submitSuspend = ({ reason }) => {
    submitAxios('suspend', () => axios.post(route('admin.users.suspend', record.value.artisan.id), { reason }), () => {
        suspendOpen.value = false;
    });
};

const submitRefer = ({ assignee_id, note, queue }) => {
    submitAxios(
        'refer',
        () => axios.post(route('admin.referrals.store'), {
            subject_type: 'review',
            subject_uid: record.value.uid,
            assignee_id,
            note,
            queue: queue || 'moderation',
        }),
        () => {
            referOpen.value = false;
        },
    );
};

const submitMessage = ({ reason }) => {
    if (!record.value?.artisan?.id) return;
    if (!messageForm.body.trim()) {
        toast({ type: 'error', message: 'Write a message before sending.' });
        return;
    }
    submitAxios(
        'message',
        () => axios.post(route('admin.messaging.store'), {
            audience: 'users',
            title: messageForm.subject,
            subject: messageForm.subject,
            body: messageForm.body,
            channels: ['in_app', 'email'],
            segment: { user_id: record.value.artisan.id },
            action: 'send',
            reason,
        }),
        () => {
            messageOpen.value = false;
        },
    );
};
</script>
