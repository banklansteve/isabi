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
                <span :class="[pill, visibilityClass]">{{ record.visibility_label }}</span>
                <span v-if="record.flagged" class="rounded-full bg-coral-tint px-2.5 py-0.5 text-[11px] font-bold text-coral-deep">
                    Flagged
                </span>
                <span v-if="record.referred" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-bold text-violet-700">
                    Referred
                </span>
                <span v-if="record.patrol" class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-800">
                    Patrol case
                </span>
            </div>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Artisan</p>
                        <p class="mt-2 text-sm font-semibold text-ink">{{ record.artisan?.name || '—' }}</p>
                        <p v-if="record.artisan?.trade" class="mt-0.5 text-xs font-medium text-ink/45">{{ record.artisan.trade }}</p>
                        <p v-if="record.artisan?.email" class="mt-0.5 text-xs font-medium text-ink/40">{{ record.artisan.email }}</p>
                        <span
                            v-if="record.artisan?.suspended"
                            class="mt-2 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-600"
                        >
                            Suspended
                        </span>
                    </div>
                    <Link
                        v-if="record.artisan?.user_url && can.view_users"
                        :href="record.artisan.user_url"
                        :show-progress="false"
                        class="text-xs font-semibold text-base-action hover:text-base-hover"
                    >
                        Open Users profile
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Job log</p>
                <p class="mt-3 text-sm font-medium leading-relaxed text-ink/80">{{ record.description_full || record.description }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-[13px]">
                    <div>
                        <dt class="text-xs font-semibold text-ink/40">Public UID</dt>
                        <dd class="mt-1 break-all font-semibold text-ink">{{ record.uid }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-ink/40">Date performed</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ record.worked_on_label || record.worked_on || '—' }}</dd>
                    </div>
                    <div v-if="record.location" class="col-span-2">
                        <dt class="text-xs font-semibold text-ink/40">Location</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ record.location }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-ink/40">Client</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ record.client_name || 'Not stored' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-ink/40">Client WhatsApp</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ record.client_whatsapp || 'Not stored' }}</dd>
                    </div>
                </dl>
                <p v-if="record.backdated_days >= 30" class="mt-3 text-xs font-semibold text-coral">
                    Backdated {{ record.backdated_days }} days
                </p>
            </div>

            <div v-if="record.media?.length" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Media</p>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a
                        v-for="item in record.media"
                        :key="item.id"
                        :href="item.preview_url || item.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="overflow-hidden rounded-xl bg-white"
                    >
                        <img v-if="item.kind !== 'video'" :src="item.url" alt="" class="h-28 w-full object-cover" />
                        <div v-else class="flex h-28 items-center justify-center text-xs font-semibold text-ink/45">
                            Video
                        </div>
                    </a>
                </div>
            </div>
            <p v-else class="text-xs font-medium text-ink/40">No media on this job.</p>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Review request</p>
                <p class="mt-3 text-sm font-semibold text-ink">{{ record.review_request?.label }}</p>
                <p v-if="record.review_request?.requested_at" class="mt-1 text-xs font-medium text-ink/45">
                    Requested {{ record.review_request.requested_at }}
                </p>
                <p v-if="record.review" class="mt-3 text-sm font-medium leading-relaxed text-ink/75">
                    {{ record.review.rating }}★
                    <span v-if="record.review.client"> · {{ record.review.client }}</span>
                    <span class="mt-1 block text-ink/60">{{ record.review.comment || 'No comment' }}</span>
                </p>
            </div>

            <div v-if="record.flagged || record.flag_reason" class="rounded-2xl bg-coral-tint/60 p-5 ring-1 ring-coral/10">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">Flag</p>
                <p class="mt-2 text-sm font-medium text-ink/80">{{ record.flag_reason || 'Flagged' }}</p>
                <p v-if="record.flagged_at_label" class="mt-1 text-xs font-medium text-ink/45">{{ record.flagged_at_label }}</p>
            </div>

            <div v-if="record.referral" class="rounded-2xl bg-violet-50/80 p-5 ring-1 ring-violet-100">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">Referred to operations</p>
                <p class="mt-2 text-sm font-semibold text-ink">{{ record.referral.assignee_name }}</p>
                <p class="mt-1 text-sm font-medium leading-relaxed text-ink/70">{{ record.referral.note }}</p>
                <p class="mt-1 text-xs font-medium text-ink/45">
                    {{ record.referral.referred_at }}
                    <span v-if="record.referral.referred_by"> · {{ record.referral.referred_by }}</span>
                </p>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-[13px]">
                <div class="rounded-2xl bg-pale/60 px-4 py-3">
                    <dt class="text-xs font-semibold text-ink/40">Logged</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ record.created_at || '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-pale/60 px-4 py-3">
                    <dt class="text-xs font-semibold text-ink/40">Updated</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ record.updated_at || '—' }}</dd>
                </div>
            </dl>
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
                        Open Users
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
                    <Link
                        v-if="record?.patrol?.url && can.view_patrol"
                        :href="record.patrol.url"
                        :show-progress="false"
                        class="text-base-action hover:text-base-hover"
                    >
                        Open in Patrol
                    </Link>
                    <button
                        v-if="record?.public_url"
                        type="button"
                        class="text-base-action hover:text-base-hover"
                        @click="copyPublicLink"
                    >
                        Copy public job link
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <FormButton
                        v-if="can.flag"
                        variant="secondary"
                        class="w-full"
                        :label="record?.flagged ? 'Unflag' : 'Flag'"
                        @click="record?.flagged ? (unflagOpen = true) : (flagOpen = true)"
                    />
                    <FormButton
                        v-if="can.hide && record?.visibility !== 'removed'"
                        variant="secondary"
                        class="w-full"
                        :label="record?.hidden ? 'Unhide' : 'Hide'"
                        @click="record?.hidden ? (unhideOpen = true) : (hideOpen = true)"
                    />
                    <FormButton
                        v-if="can.refer"
                        variant="secondary"
                        class="w-full"
                        label="Refer to ops"
                        @click="openRefer"
                    />
                    <FormButton
                        v-if="can.remove && record?.visibility !== 'removed'"
                        variant="secondary"
                        class="w-full"
                        label="Delete"
                        @click="removeOpen = true"
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
                        label="Message artisan"
                        @click="openMessage"
                    />
                </div>
            </div>
        </template>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="removeOpen"
        title="Delete this job log?"
        description="Soft-removes it from the public page. Super Admin approval is required for operations staff."
        confirm-label="Delete job"
        tone="danger"
        confirm-phrase="DELETE"
        :processing="busy === 'remove'"
        @close="removeOpen = false"
        @confirm="submitRemove"
    />

    <AdminConfirmDialog
        :open="messageOpen"
        title="Message this artisan?"
        description="Sends through email, WhatsApp, or in-app depending on the channel you pick."
        confirm-label="Send message"
        reason-placeholder="Why you are reaching out…"
        :processing="busy === 'message'"
        @close="messageOpen = false"
        @confirm="submitMessage"
    >
        <div class="mt-4 flex flex-wrap gap-1.5">
            <button
                v-for="item in channels"
                :key="item.value"
                type="button"
                class="rounded-full px-3 py-1.5 text-[13px] font-semibold transition-colors duration-150"
                :class="messageForm.channel === item.value
                    ? 'bg-base-action text-white'
                    : 'bg-pale text-ink/55 hover:bg-tint'"
                :disabled="item.value === 'whatsapp' && !record?.artisan?.whatsapp_url"
                @click="messageForm.channel = item.value"
            >
                {{ item.label }}
            </button>
        </div>
        <p v-if="messageForm.channel === 'whatsapp' && !record?.artisan?.whatsapp_url" class="mt-2 text-[13px] font-medium text-coral">
            This artisan has no WhatsApp number on file.
        </p>
        <p class="mt-2 text-[13px] font-medium text-ink/50">{{ channelHint }}</p>
        <FormTextInput
            v-if="messageForm.channel !== 'whatsapp'"
            id="job-message-subject"
            v-model="messageForm.subject"
            label="Subject"
            :error="messageForm.errors.subject"
            class="mt-3"
        />
        <FormTextarea
            id="job-message-body"
            v-model="messageForm.body"
            label="Message"
            :error="messageForm.errors.body"
            required
            class="mt-2"
        />
    </AdminConfirmDialog>

    <AdminConfirmDialog
        :open="flagOpen"
        title="Flag this job?"
        description="It stays on the artisan’s file and is marked for moderation. This is not a Patrol case."
        confirm-label="Flag job"
        :processing="busy === 'flag'"
        @close="flagOpen = false"
        @confirm="submitFlag"
    />

    <AdminConfirmDialog
        :open="unflagOpen"
        title="Clear this flag?"
        description="The job leaves the flagged queue. Add a reason for the audit log."
        confirm-label="Clear flag"
        :processing="busy === 'unflag'"
        @close="unflagOpen = false"
        @confirm="submitUnflag"
    />

    <AdminConfirmDialog
        :open="hideOpen"
        title="Hide this job from the public page?"
        description="It stays in the record and is never hard-deleted. The artisan can still see it in their own logs."
        confirm-label="Hide job"
        tone="danger"
        :processing="busy === 'hide'"
        @close="hideOpen = false"
        @confirm="submitHide"
    />

    <AdminConfirmDialog
        :open="unhideOpen"
        title="Restore this job to the public page?"
        description="It can show on the artisan’s public page again."
        confirm-label="Unhide"
        :processing="busy === 'unhide'"
        @close="unhideOpen = false"
        @confirm="submitUnhide"
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

    <AdminConfirmDialog
        :open="referOpen"
        title="Refer this job to operations?"
        description="This is a handoff on the job file — it does not open a Patrol case."
        confirm-label="Refer"
        reason-placeholder="What should they look at…"
        :processing="busy === 'refer'"
        @close="referOpen = false"
        @confirm="submitRefer"
    >
        <label class="mt-4 block">
            <span class="text-[12px] font-semibold text-ink/50">Operations staff</span>
            <select
                v-model="referAssigneeId"
                required
                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            >
                <option value="" disabled>Pick a staff member</option>
                <option v-for="person in staff" :key="person.id" :value="String(person.id)">{{ person.name }}</option>
            </select>
            <p v-if="referError" class="mt-1.5 text-[12px] font-medium text-coral">{{ referError }}</p>
        </label>
    </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { toast } from '@/utils/adminRange';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

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
    title: record.value?.artisan?.name || props.row?.user?.name || 'Job log',
    eyebrow: record.value?.uid || props.row?.uid || 'Job file',
}));
const pill = 'rounded-full px-2.5 py-0.5 text-[11px] font-bold';
const visibilityClass = computed(() => {
    if (record.value?.visibility === 'removed') {
        return 'bg-ink/10 text-ink/55';
    }
    if (record.value?.visibility === 'hidden') {
        return 'bg-amber-50 text-amber-800';
    }
    return 'bg-emerald-50 text-emerald-700';
});

const channels = [
    { value: 'email', label: 'Email' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'in_app', label: 'In-app' },
];

const channelHint = computed(() => {
    if (messageForm.channel === 'email') {
        return 'Sends through the existing announcement email.';
    }
    if (messageForm.channel === 'whatsapp') {
        return 'Opens wa.me with this message prefilled. Nothing is auto-sent.';
    }
    return 'Uses the artisan’s existing in-app inbox.';
});

const caseEntered = ref(false);
const flagOpen = ref(false);
const unflagOpen = ref(false);
const hideOpen = ref(false);
const unhideOpen = ref(false);
const removeOpen = ref(false);
const suspendOpen = ref(false);
const referOpen = ref(false);
const messageOpen = ref(false);
const referAssigneeId = ref('');
const referError = ref('');
const busy = ref('');

const messageForm = useForm({
    channel: 'email',
    subject: 'A note from Isabi',
    body: '',
});

const formErrors = (error) => {
    const raw = error.response?.data?.errors || {};
    return Object.fromEntries(
        Object.entries(raw).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : String(messages)]),
    );
};

const submitAxios = async (key, request, onSuccess) => {
    busy.value = key;
    try {
        const { data } = await request();
        if (data?.toast) {
            toast(data.toast);
        }
        onSuccess?.(data);
        if (data?.job || data?.record) {
            emit('updated', data);
        }
        emit('refresh');
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t save',
            message: error.response?.data?.message || error.response?.data?.toast?.message || 'Try that again in a moment.',
        });
        return error;
    } finally {
        busy.value = '';
    }
    return null;
};

const resetOverlays = () => {
    flagOpen.value = false;
    unflagOpen.value = false;
    hideOpen.value = false;
    unhideOpen.value = false;
    removeOpen.value = false;
    suspendOpen.value = false;
    referOpen.value = false;
    messageOpen.value = false;
    referAssigneeId.value = '';
    referError.value = '';
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
    () => {
        resetOverlays();
    },
);

const openRefer = () => {
    referAssigneeId.value = record.value?.referral?.assignee_id
        ? String(record.value.referral.assignee_id)
        : '';
    referError.value = '';
    referOpen.value = true;
};

const openMessage = () => {
    const first = record.value?.artisan?.first_name || 'there';
    const job = record.value?.description || 'your recent job';
    messageForm.channel = record.value?.artisan?.whatsapp_url ? messageForm.channel : 'email';
    messageForm.subject = 'A note from Isabi';
    messageForm.body = `Hi ${first}, I’m writing about this job on your Isabi page: ${job}`;
    messageForm.clearErrors();
    messageOpen.value = true;
};

const submitFlag = ({ reason }) => {
    submitAxios('flag', () => axios.post(route('admin.jobs.flag', record.value.uid), { reason }), () => {
        flagOpen.value = false;
    });
};

const submitUnflag = ({ reason }) => {
    submitAxios('unflag', () => axios.post(route('admin.jobs.unflag', record.value.uid), { reason }), () => {
        unflagOpen.value = false;
    });
};

const submitHide = ({ reason }) => {
    submitAxios('hide', () => axios.post(route('admin.jobs.hide', record.value.uid), { reason }), () => {
        hideOpen.value = false;
    });
};

const submitUnhide = ({ reason }) => {
    submitAxios('unhide', () => axios.post(route('admin.jobs.unhide', record.value.uid), { reason }), () => {
        unhideOpen.value = false;
    });
};

const submitRemove = ({ reason }) => {
    submitAxios('remove', () => axios.post(route('admin.jobs.remove', record.value.uid), { reason }), () => {
        removeOpen.value = false;
    });
};

const submitSuspend = ({ reason }) => {
    submitAxios('suspend', () => axios.post(route('admin.users.suspend', record.value.artisan.id), { reason }), () => {
        suspendOpen.value = false;
    });
};

const submitRefer = ({ reason }) => {
    if (!referAssigneeId.value) {
        referError.value = 'Pick an operations staff member.';
        return;
    }
    referError.value = '';
    submitAxios(
        'refer',
        () => axios.post(route('admin.jobs.refer', record.value.uid), {
            assignee_id: Number(referAssigneeId.value),
            note: reason,
        }),
        () => {
            referOpen.value = false;
        },
    );
};

const submitMessage = async ({ reason }) => {
    messageForm.clearErrors();
    if (!messageForm.body.trim()) {
        toast({ type: 'error', message: 'Write a message before sending.' });
        return;
    }
    const error = await submitAxios(
        'message',
        () => axios.post(route('admin.jobs.message', record.value.uid), {
            ...messageForm.data(),
            reason,
        }),
        (data) => {
            if (data?.whatsapp_url) {
                window.open(data.whatsapp_url, '_blank', 'noopener,noreferrer');
            }
            messageOpen.value = false;
        },
    );
    if (error) {
        const errors = formErrors(error);
        if (Object.keys(errors).length) {
            messageForm.setError(errors);
        }
    }
};

const copyPublicLink = async () => {
    if (!record.value?.public_url) {
        return;
    }
    try {
        await navigator.clipboard.writeText(record.value.public_url);
        toast({ type: 'success', title: 'Copied', message: 'Public job link is on the clipboard.' });
    } catch {
        toast({ type: 'error', message: 'Could not copy the link.' });
    }
};
</script>
