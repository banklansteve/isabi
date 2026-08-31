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
                <span :class="[patrolPill, patrolStatusMeta(record.status).class]">{{ record.status_label }}</span>
                <span :class="[patrolPill, patrolSeverityMeta(record.severity).class]">{{ record.severity_label }}</span>
                <span :class="[patrolPill, patrolVisibilityMeta(record.visibility).class]">{{ visibilityLabel }}</span>
            </div>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Artisan</p>
                        <p class="mt-2 text-sm font-semibold text-ink">{{ record.artisan?.name }}</p>
                        <p v-if="record.artisan?.trade" class="mt-0.5 text-xs font-medium text-ink/45">{{ record.artisan.trade }}</p>
                    </div>
                    <Link
                        v-if="record.user_url"
                        :href="record.user_url"
                        class="text-xs font-semibold text-base-action hover:text-base-hover"
                    >
                        Open Users profile
                    </Link>
                </div>
            </div>

            <div v-if="record.review" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Review</p>
                <p class="mt-3 text-sm font-semibold text-ink">{{ record.review.rating }}★ · {{ record.review.would_recommend ? 'Would recommend' : 'Would not recommend' }}</p>
                <p class="mt-2 text-sm font-medium leading-relaxed text-ink/80">{{ record.review.comment || 'No comment' }}</p>
                <p v-if="record.review.client_display_name" class="mt-2 text-xs font-medium text-ink/45">
                    {{ record.review.client_display_name }}
                    <span v-if="record.review.referred_by"> · Heard from {{ record.review.referred_by }}</span>
                </p>
                <p class="mt-2 text-xs font-medium text-ink/40">Submitted {{ record.review.submitted_label }}</p>
                <img
                    v-if="record.review.photo_url"
                    :src="record.review.photo_url"
                    alt=""
                    class="mt-3 h-28 w-full rounded-xl object-cover"
                />
            </div>

            <div v-if="record.job" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">{{ record.kind === 'review' ? 'Linked job' : 'Job log' }}</p>
                <p class="mt-3 text-sm font-medium leading-relaxed text-ink/80">{{ record.job.description }}</p>
                <p class="mt-2 text-xs font-medium text-ink/40">
                    Performed {{ record.job.worked_on_label }} · Logged {{ record.job.created_label }}
                </p>
                <img
                    v-if="record.job.photo_url"
                    :src="record.job.photo_url"
                    alt=""
                    class="mt-3 h-28 w-full rounded-xl object-cover"
                />
                <p v-else class="mt-3 text-xs font-medium text-ink/40">No photo on this job.</p>
            </div>

            <section>
                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Matched rules</h3>
                <ul class="mt-3 space-y-2">
                    <li
                        v-for="rule in record.matched_rules"
                        :key="rule.key"
                        class="rounded-2xl bg-pale/60 px-4 py-3 ring-1 ring-ink/[0.04]"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-ink">{{ rule.label }}</p>
                            <span :class="[patrolPill, patrolSeverityMeta(rule.severity).class]">{{ rule.severity_label }}</span>
                        </div>
                        <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/65">{{ rule.trigger }}</p>
                        <p class="mt-1 text-xs font-medium text-ink/35">Detected {{ rule.detected_label }}</p>
                    </li>
                </ul>
            </section>

            <section>
                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Context</h3>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-[13px]">
                    <div class="rounded-2xl bg-pale/60 px-4 py-3">
                        <dt class="text-xs font-semibold text-ink/40">Account age</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ record.context.account_age_days }} days</dd>
                    </div>
                    <div class="rounded-2xl bg-pale/60 px-4 py-3">
                        <dt class="text-xs font-semibold text-ink/40">Job volume</dt>
                        <dd class="mt-1 font-semibold text-ink">
                            {{ record.context.job_count }} total · {{ record.context.jobs_last_7_days }} in 7 days
                        </dd>
                    </div>
                    <div class="rounded-2xl bg-pale/60 px-4 py-3">
                        <dt class="text-xs font-semibold text-ink/40">Prior cases</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ record.context.prior_cases }}</dd>
                    </div>
                    <div class="rounded-2xl bg-pale/60 px-4 py-3">
                        <dt class="text-xs font-semibold text-ink/40">{{ record.kind === 'review' ? 'Review volume' : 'Last 30 days' }}</dt>
                        <dd class="mt-1 font-semibold text-ink">
                            <template v-if="record.kind === 'review'">
                                {{ record.context.review_count }} total · {{ record.context.reviews_last_7_days }} in 7 days
                            </template>
                            <template v-else>
                                {{ record.context.jobs_last_30_days }} jobs
                            </template>
                        </dd>
                    </div>
                </dl>
                <div v-if="record.context.open_cases.length" class="mt-3 rounded-2xl bg-pale/60 px-4 py-3 ring-1 ring-ink/[0.04]">
                    <p class="text-xs font-semibold text-ink/40">Other open cases for this artisan</p>
                    <ul class="mt-2 space-y-1.5">
                        <li v-for="item in record.context.open_cases" :key="item.id" class="text-[13px] font-medium text-ink/70">
                            {{ item.kind === 'review' ? 'Review' : 'Job' }} · {{ item.summary }} · {{ item.status_label }}
                        </li>
                    </ul>
                </div>
            </section>

            <section>
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Notes</h3>
                    <button
                        v-if="can.investigate && isOpen"
                        variant="secondary"
                        icon-left="ti ti-note"
                        label="Add note"
                        @click="noteOpen = true"
                    />
                </div>
                <ol v-if="record.notes.length" class="space-y-0">
                    <li v-for="(note, index) in record.notes" :key="note.id" class="relative flex gap-4 pb-5">
                        <div class="flex w-5 shrink-0 flex-col items-center">
                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-base-action" />
                            <span v-if="index < record.notes.length - 1" class="mt-1 w-px flex-1 bg-ink/10" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink/35">{{ note.created_label }}</p>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-ink/75">{{ note.body }}</p>
                            <p class="mt-1 text-xs font-medium text-ink/40">{{ note.author_name }}</p>
                        </div>
                    </li>
                </ol>
                <AdminEmpty
                    v-else
                    title="No notes yet"
                    description="Notes are append-only. Add a further note if a correction is needed."
                    icon="ti ti-notes"
                />
            </section>

            <section v-if="record.audit.length">
                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Audit</h3>
                <ul class="mt-3 space-y-2">
                    <li v-for="item in record.audit" :key="item.id" class="text-[13px] font-medium text-ink/65">
                        <span class="font-semibold text-ink">{{ item.action_label }}</span>
                        · {{ item.actor_name }} · {{ item.created_label }}
                        <span v-if="item.reason" class="block text-ink/45">{{ item.reason }}</span>
                    </li>
                </ul>
            </section>

        </div>

        <template #footer>
            <PatrolCaseActions
                :record="actionRecord"
                :can="can"
                :busy="busy"
                :approve-label="approveLabel"
                @review="reviewOpen = true"
                @recommend="askRecommend"
                @dismiss="askDismiss"
                @hide="hideOpen = true"
                @remove="removeOpen = true"
                @handoff="handoffOpen = true"
                @approve="approveOpen = true"
                @reject="rejectOpen = true"
                @refer="referOpen = true"
            />
        </template>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="noteOpen"
        title="Add a case note?"
        description="Notes are append-only and become part of the permanent case record."
        confirm-label="Add note"
        reason-placeholder="What did you find or decide…"
        :processing="busy === 'note'"
        @close="noteOpen = false"
        @confirm="submitNote"
    />

    <AdminConfirmDialog
        :open="reviewOpen"
        title="Mark this case in review?"
        description="Moves the case into active investigation. Nothing is hidden or removed yet."
        confirm-label="Mark in review"
        :processing="busy === 'review'"
        @close="reviewOpen = false"
        @confirm="submitReview"
    />

    <AdminConfirmDialog
        :open="recommendOpen"
        :title="recommendTitle"
        description="This sends the case to Super Admin. It does not hide, remove, or suspend anyone."
        confirm-label="Send recommendation"
        :processing="busy === 'recommend'"
        @close="recommendOpen = false"
        @confirm="submitRecommend"
    />

    <AdminConfirmDialog
        :open="dismissOpen"
        :title="record?.severity === 'high' ? 'Dismiss this high-severity case?' : 'Dismiss this case?'"
        :description="isReview
            ? 'If the review was auto-hidden by Patrol, it will become public again immediately.'
            : 'If the job was auto-hidden by Patrol, it will become public again immediately.'"
        confirm-label="Dismiss"
        :processing="busy === 'dismiss'"
        @close="dismissOpen = false"
        @confirm="submitDismiss"
    />

    <AdminConfirmDialog
        :open="removeOpen"
        :title="isReview ? 'Remove this review?' : 'Remove this job log?'"
        :description="isReview
            ? 'The review is archived and hidden from the public page. It is never hard-deleted.'
            : 'The entry is archived and hidden from the public page. It is never hard-deleted.'"
        confirm-label="Remove review"
        tone="danger"
        confirm-phrase="DELETE"
        :processing="busy === 'remove'"
        @close="removeOpen = false"
        @confirm="submitRemove"
    />

    <AdminConfirmDialog
        :open="handoffOpen"
        title="Hand off to Users?"
        description="Patrol will record the handoff. Warn or suspend the artisan on their Users profile — that step is not applied here."
        confirm-label="Record and open Users"
        :processing="busy === 'handoff'"
        @close="handoffOpen = false"
        @confirm="submitHandoff"
    />

    <AdminConfirmDialog
        :open="hideOpen"
        title="Hide this review?"
        description="The review stays in the record but is hidden from the public profile. It is never hard-deleted."
        confirm-label="Hide review"
        :processing="busy === 'hide'"
        @close="hideOpen = false"
        @confirm="submitHide"
    />

    <AdminConfirmDialog
        :open="approveOpen"
        :title="`Approve ${record?.recommended_outcome_label || 'this recommendation'}?`"
        description="This finalizes the Ops recommendation and writes the audit trail."
        confirm-label="Approve"
        :processing="busy === 'approve'"
        @close="approveOpen = false"
        @confirm="submitApprove"
    />

    <AdminConfirmDialog
        :open="rejectOpen"
        title="Reject this recommendation?"
        description="The case returns to in review. Nothing is hidden, removed, or handed off."
        confirm-label="Reject recommendation"
        :processing="busy === 'reject'"
        @close="rejectOpen = false"
        @confirm="submitReject"
    />

    <ReferToStaffDialog
        :open="referOpen"
        title="Refer this patrol case?"
        description="Assign a colleague to continue the investigation."
        confirm-label="Refer case"
        :staff="staff"
        default-queue="patrol"
        :show-queue="false"
        :processing="busy === 'refer'"
        @close="referOpen = false"
        @confirm="submitRefer"
    />
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import PatrolCaseActions from '@/Components/Admin/PatrolCaseActions.vue';
import ReferToStaffDialog from '@/Components/Admin/ReferToStaffDialog.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { toast } from '@/utils/adminRange';
import { patrolPill, patrolSeverityMeta, patrolStatusMeta, patrolVisibilityMeta } from '@/utils/patrolStatus';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    row: { type: Object, default: null },
    panel: { type: Object, default: null },
    fallbackCan: { type: Object, default: () => ({}) },
    staff: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'refresh', 'updated']);

const record = computed(() => props.panel?.record || null);
const actionRecord = computed(() => props.panel?.record || props.row || null);
const can = computed(() => ({
    investigate: false,
    resolve: false,
    dismiss_low: false,
    ...(props.fallbackCan || {}),
    ...(props.panel?.can || {}),
}));
const isReview = computed(() => record.value?.kind === 'review');
const isOpen = computed(() => ['new', 'in_review', 'pending_approval'].includes(record.value?.status));
const visibilityLabel = computed(() => patrolVisibilityMeta(record.value?.visibility).label);
const heading = computed(() => ({
    title: record.value?.artisan?.name || props.row?.artisan?.name || 'Patrol case',
    eyebrow: isReview.value
        ? (record.value?.review_excerpt || props.row?.review_excerpt || 'Review')
        : (record.value?.job_summary || props.row?.job_summary || 'Job log'),
}));
const approveLabel = computed(() => {
    const label = record.value?.recommended_outcome_label;
    return label ? `Approve ${label.toLowerCase()}` : 'Approve recommendation';
});

const caseEntered = ref(false);
const noteOpen = ref(false);
const reviewOpen = ref(false);
const recommendOpen = ref(false);
const dismissOpen = ref(false);
const removeOpen = ref(false);
const hideOpen = ref(false);
const approveOpen = ref(false);
const rejectOpen = ref(false);
const handoffOpen = ref(false);
const referOpen = ref(false);
const pendingOutcome = ref('');
const busy = ref('');

const noteForm = useForm({ body: '' });
const reasonForm = useForm({ reason: '', outcome: '' });

const recommendTitle = computed(() => {
    const map = {
        dismiss: 'Recommend dismiss?',
        hide: 'Recommend hide?',
        remove: isReview.value ? 'Recommend remove?' : 'Recommend removal?',
        suspend: 'Recommend suspension?',
    };
    return map[pendingOutcome.value] || 'Recommend an outcome?';
});

const formErrors = (error) => {
    const raw = error.response?.data?.errors || {};
    return Object.fromEntries(
        Object.entries(raw).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : String(messages)]),
    );
};

const submitAxios = async (form, key, request, onSuccess) => {
    form.clearErrors();
    busy.value = key;
    try {
        const { data } = await request();
        if (data?.toast) {
            toast(data.toast);
        }
        onSuccess?.(data);
        emit('updated', data?.record || record.value);
        emit('refresh');
    } catch (error) {
        const errors = formErrors(error);
        if (Object.keys(errors).length) {
            form.setError(errors);
        } else {
            toast({
                type: 'error',
                message: error.response?.data?.message || 'Could not save that change.',
            });
        }
    } finally {
        busy.value = '';
    }
};

const resetOverlays = () => {
    noteOpen.value = false;
    reviewOpen.value = false;
    recommendOpen.value = false;
    dismissOpen.value = false;
    removeOpen.value = false;
    hideOpen.value = false;
    approveOpen.value = false;
    rejectOpen.value = false;
    handoffOpen.value = false;
    referOpen.value = false;
    pendingOutcome.value = '';
};

watch(() => props.open, (open) => {
    if (open) {
        caseEntered.value = !!props.panel?.record;
        return;
    }
    resetOverlays();
});

watch(() => record.value?.id, () => {
    resetOverlays();
});

const submitNote = ({ reason }) => {
    submitAxios(noteForm, 'note', () => axios.post(route('admin.patrol.notes.store', record.value.id), {
        body: reason,
    }), () => {
        noteOpen.value = false;
    });
};

const submitReview = ({ reason }) => {
    submitAxios(reasonForm, 'review', () => axios.post(route('admin.patrol.review', record.value.id), { reason }), () => {
        reviewOpen.value = false;
    });
};

const askRecommend = (outcome) => {
    pendingOutcome.value = outcome;
    recommendOpen.value = true;
};

const askDismiss = () => {
    dismissOpen.value = true;
};

const submitRecommend = ({ reason }) => {
    submitAxios(reasonForm, 'recommend', () => axios.post(route('admin.patrol.recommend', record.value.id), {
        outcome: pendingOutcome.value,
        reason,
    }), () => {
        recommendOpen.value = false;
    });
};

const submitDismiss = ({ reason }) => {
    submitAxios(reasonForm, 'dismiss', () => axios.post(route('admin.patrol.dismiss', record.value.id), { reason }), () => {
        dismissOpen.value = false;
    });
};

const submitRemove = ({ reason }) => {
    submitAxios(reasonForm, 'remove', () => axios.post(route('admin.patrol.remove', record.value.id), { reason }), () => {
        removeOpen.value = false;
    });
};

const submitHandoff = ({ reason }) => {
    submitAxios(reasonForm, 'handoff', () => axios.post(route('admin.patrol.handoff', record.value.id), { reason }), (data) => {
        handoffOpen.value = false;
        if (data?.user_url) {
            window.location.assign(data.user_url);
        }
    });
};

const submitRefer = async ({ assignee_id, note }) => {
    const caseId = record.value?.id || props.row?.id;
    if (!caseId) {
        return;
    }
    busy.value = 'refer';
    try {
        const { data } = await axios.post(route('admin.referrals.store'), {
            subject_type: 'patrol',
            subject_uid: String(caseId),
            assignee_id,
            note,
            queue: 'patrol',
        });
        referOpen.value = false;
        toast(data.toast || { type: 'success', title: 'Referred', message: 'Case handed to a colleague.' });
        emit('updated', data?.referral || record.value);
        emit('refresh');
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t refer',
            message: error?.response?.data?.errors?.assignee_id?.[0]
                || error?.response?.data?.message
                || 'Try that again in a moment.',
        });
    } finally {
        busy.value = '';
    }
};

const submitHide = ({ reason }) => {
    submitAxios(reasonForm, 'hide', () => axios.post(route('admin.patrol.hide', record.value.id), { reason }), () => {
        hideOpen.value = false;
    });
};

const submitApprove = ({ reason }) => {
    submitAxios(reasonForm, 'approve', () => axios.post(route('admin.patrol.approve', record.value.id), { reason }), (data) => {
        approveOpen.value = false;
        if (data?.user_url) {
            window.location.assign(data.user_url);
        }
    });
};

const submitReject = ({ reason }) => {
    submitAxios(reasonForm, 'reject', () => axios.post(route('admin.patrol.reject', record.value.id), { reason }), () => {
        rejectOpen.value = false;
    });
};
</script>
