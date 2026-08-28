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
                <span :class="[pillBase, statusMeta(record.status).class]">{{ record.status_label }}</span>
                <span :class="[pillBase, severityMeta(record.severity).class]">{{ record.severity_label }}</span>
                <span class="text-xs font-semibold text-ink/40">{{ record.category_label }}</span>
                <span v-if="record.is_archived" class="text-xs font-semibold text-ink/35">Archived</span>
            </div>

            <div
                v-if="record.needs_access_followup"
                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] font-medium text-amber-950"
            >
                A serious outcome is on this file. Confirm any change to platform access in Admin &amp; staff, and any employment-status change on the HR profile. Those steps are not applied automatically.
                <div class="mt-2 flex flex-wrap gap-3">
                    <Link v-if="can.access" :href="route('admin.staff.index')" class="font-semibold text-amber-900 underline-offset-2 hover:underline">Open Access &amp; staff</Link>
                    <Link v-if="can.hr_profile" :href="route('admin.hr.staff.show', record.staff_id)" class="font-semibold text-amber-900 underline-offset-2 hover:underline">Open HR profile</Link>
                </div>
            </div>

            <section>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Case record</h3>
                    <div class="flex flex-wrap gap-2">
                        <FormButton v-if="can.manage && !record.is_archived" variant="secondary" icon-left="ti ti-note" label="Add note" @click="noteOpen = true" />
                        <FormButton v-if="can.manage && !record.is_archived" variant="secondary" icon-left="ti ti-paperclip" label="Attach evidence" @click="evidenceOpen = true" />
                    </div>
                </div>

                <ol v-if="record.timeline.length" class="space-y-0">
                    <li v-for="(event, index) in record.timeline" :key="event.id" class="relative flex gap-4 pb-6">
                        <div class="flex w-5 shrink-0 flex-col items-center">
                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-base-action" />
                            <span v-if="index < record.timeline.length - 1" class="mt-1 w-px flex-1 bg-ink/10" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink/35">{{ event.occurred_label }}</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ event.type_label }}</p>
                            <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/65">{{ event.summary }}</p>
                            <p v-if="event.reason" class="mt-2 rounded-xl bg-pale/80 px-3 py-2 text-[13px] font-medium leading-relaxed text-ink/70">
                                {{ event.reason }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-ink/40">
                                {{ event.actor_name }}
                                <span v-if="event.confidential"> · Restricted note</span>
                            </p>
                        </div>
                    </li>
                </ol>
                <AdminEmpty
                    v-else
                    title="No timeline entries yet"
                    description="Every status change, note, and notice will appear here in order."
                    icon="ti ti-timeline"
                />
            </section>

            <div class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Incident</h3>
                <p class="mt-3 text-sm font-medium leading-relaxed text-ink/75">{{ record.description }}</p>
                <p class="mt-3 text-xs font-medium text-ink/40">Occurred {{ record.incident_label }}</p>
                <p class="mt-1 text-xs font-medium text-ink/40">Owner {{ record.owner_name }}</p>
                <p class="mt-1 text-xs font-medium text-ink/40">Opened by {{ record.opened_by_name }}</p>
            </div>

            <div v-if="can.manage && record.allowed_transitions.length && !record.is_archived" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Advance the case</h3>
                <p class="mt-2 text-[13px] font-medium text-ink/50">Each stage is a deliberate step. A reason is required.</p>
                <div class="mt-3 space-y-2">
                    <FormButton
                        v-for="step in record.allowed_transitions"
                        :key="step.value"
                        variant="secondary"
                        class="w-full"
                        :label="step.label"
                        @click="askTransition(step)"
                    />
                </div>
            </div>

            <div v-if="pendingAppeal" class="rounded-2xl bg-violet-50 p-5 ring-1 ring-violet-100">
                <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">Pending appeal</h3>
                <p class="mt-2 text-[13px] font-medium leading-relaxed text-ink/75">{{ pendingAppeal.grounds }}</p>
                <p class="mt-1 text-xs font-medium text-ink/40">Raised {{ pendingAppeal.raised_label }} by {{ pendingAppeal.raised_by_name }}</p>
            </div>

            <div v-if="record.latest_action" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Latest notice</h3>
                <p class="mt-2 text-sm font-semibold text-ink">{{ record.latest_action.type_label }}</p>
                <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/60">{{ record.latest_action.justification }}</p>
                <p class="mt-2 text-xs font-medium text-ink/40">
                    Issued {{ record.latest_action.issued_label }}
                    <span v-if="record.latest_action.acknowledged"> · Acknowledged {{ record.latest_action.acknowledged_label }}</span>
                    <span v-else> · Not yet acknowledged</span>
                </p>
                <a
                    :href="route('admin.hr.discipline.letter', record.latest_action.id)"
                    class="mt-3 inline-flex text-xs font-semibold text-base-action hover:text-base-hover"
                >
                    Download PDF
                </a>
            </div>

            <div v-if="record.evidence.length" class="rounded-2xl bg-pale/60 p-5 ring-1 ring-ink/[0.04]">
                <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Evidence</h3>
                <ul class="mt-3 space-y-2">
                    <li v-for="item in record.evidence" :key="item.id">
                        <a :href="route('admin.hr.discipline.evidence.download', item.id)" class="text-sm font-semibold text-ink hover:text-base-action">
                            {{ item.title }}
                        </a>
                        <p class="text-xs font-medium text-ink/40">{{ item.created_label }} · {{ item.uploaded_by_name }}</p>
                    </li>
                </ul>
            </div>

            <div v-if="can.manage && record.can_archive">
                <FormButton variant="secondary" class="w-full" label="Archive case" @click="archiveOpen = true" />
            </div>
        </div>

        <template #footer>
            <div v-if="record && can.manage && !record.is_archived" class="flex flex-wrap gap-2">
                <FormButton
                    v-if="pendingAppeal && can.review_appeal"
                    variant="primary"
                    icon-left="ti ti-gavel"
                    label="Decide appeal"
                    @click="reviewOpen = true"
                />
                <FormButton
                    v-else-if="record.can_issue_action"
                    variant="primary"
                    icon-left="ti ti-file-text"
                    label="Record formal action"
                    @click="openAction"
                />
                <FormButton
                    v-else-if="nextTransition"
                    variant="primary"
                    :label="nextTransition.label"
                    @click="askTransition(nextTransition)"
                />
                <FormButton
                    v-if="record.can_raise_appeal"
                    variant="secondary"
                    label="Record appeal"
                    @click="appealOpen = true"
                />
                <FormButton variant="secondary" icon-left="ti ti-note" label="Add note" @click="noteOpen = true" />
            </div>
        </template>
    </AdminDrawer>

    <AdminDrawer :open="noteOpen" title="Add a case note" eyebrow="Append-only" @close="noteOpen = false">
        <form class="space-y-4" @submit.prevent="submitNote">
            <p class="text-[13px] font-medium text-ink/50">Notes cannot be edited or removed. Add a further note if a correction is needed.</p>
            <FormTextarea id="note-body" v-model="noteForm.body" label="Note" :error="noteForm.errors.body" required />
            <label class="flex items-start gap-2 text-[13px] font-medium text-ink/65">
                <input v-model="noteForm.confidential" type="checkbox" class="mt-0.5 rounded border-ink/20" />
                Restrict this note to Super Admin
            </label>
            <div class="flex justify-end gap-2">
                <FormButton type="button" variant="secondary" label="Cancel" @click="noteOpen = false" />
                <FormButton type="submit" variant="primary" label="Add note" :loading="busy === 'note'" loading-label="Saving…" />
            </div>
        </form>
    </AdminDrawer>

    <AdminDrawer :open="evidenceOpen" title="Attach supporting material" eyebrow="Documents and statements" @close="evidenceOpen = false">
        <form class="space-y-4" @submit.prevent="submitEvidence">
            <FormTextInput id="ev-title" v-model="evidenceForm.title" label="Title" icon="ti ti-file" :error="evidenceForm.errors.title" required />
            <label class="block">
                <span class="text-[12px] font-semibold text-ink/50">File</span>
                <input
                    ref="evidenceInput"
                    type="file"
                    class="mt-1.5 block w-full text-sm text-ink/70 file:mr-3 file:rounded-xl file:border-0 file:bg-tint file:px-4 file:py-2 file:text-sm file:font-semibold file:text-deep"
                    @change="evidenceForm.file = $event.target.files[0]"
                />
                <p v-if="evidenceForm.errors.file" class="mt-1 text-xs font-medium text-coral-deep">{{ evidenceForm.errors.file }}</p>
            </label>
            <div class="flex justify-end gap-2">
                <FormButton type="button" variant="secondary" label="Cancel" @click="evidenceOpen = false" />
                <FormButton type="submit" variant="primary" label="Attach" :loading="busy === 'evidence'" loading-label="Uploading…" />
            </div>
        </form>
    </AdminDrawer>

    <AdminDrawer :open="actionOpen" title="Record a formal action" eyebrow="Requires justification" @close="actionOpen = false">
        <form class="space-y-4" @submit.prevent="confirmActionOpen = true">
            <FormSelect id="act-type" v-model="actionForm.type" label="Outcome" icon="ti ti-file-text" :options="options.outcomes" :error="actionForm.errors.type" />
            <div v-if="actionForm.type === 'suspension'" class="grid gap-3 sm:grid-cols-2">
                <FormTextInput id="act-from" v-model="actionForm.suspension_starts_on" type="date" label="Suspension starts" icon="ti ti-calendar" :error="actionForm.errors.suspension_starts_on" />
                <FormTextInput id="act-to" v-model="actionForm.suspension_ends_on" type="date" label="Suspension ends" icon="ti ti-calendar" :error="actionForm.errors.suspension_ends_on" />
            </div>
            <FormTextarea id="act-just" v-model="actionForm.justification" label="Justification" :error="actionForm.errors.justification" required />
            <FormSelect v-if="matchingTemplates.length" id="act-tpl" v-model="actionForm.template_id" label="Letter template" icon="ti ti-template" :options="matchingTemplates" :error="actionForm.errors.template_id" />
            <FormTextInput id="act-subject" v-model="actionForm.letter_subject" label="Notice title" icon="ti ti-heading" :error="actionForm.errors.letter_subject" />
            <FormTextarea id="act-letter" v-model="actionForm.letter_body" label="Notice (review before issuing)" :error="actionForm.errors.letter_body" required />
            <p class="text-[12px] font-medium text-ink/40">Placeholders: {{ placeholders.join(', ') }}</p>
            <div class="flex justify-end gap-2">
                <FormButton type="button" variant="secondary" label="Cancel" @click="actionOpen = false" />
                <FormButton type="submit" variant="primary" label="Review and issue" />
            </div>
        </form>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="transitionOpen"
        :title="pendingTransition ? `Move to ${pendingTransition.label}` : 'Update status'"
        description="This change is recorded with your name and the time. It cannot be deleted."
        confirm-label="Update status"
        :processing="busy === 'transition'"
        @close="transitionOpen = false"
        @confirm="submitTransition"
    />

    <AdminConfirmDialog
        :open="confirmActionOpen"
        title="Issue this notice?"
        description="The letter will be finalized and added to the case record. The staff member will be able to view the notice, not the investigation file."
        confirm-label="Issue notice"
        :require-reason="false"
        :processing="busy === 'action'"
        @close="confirmActionOpen = false"
        @confirm="submitAction"
    />

    <AdminConfirmDialog
        :open="archiveOpen"
        title="Archive this case?"
        description="The case stays on file. It will no longer appear in the open list."
        confirm-label="Archive"
        :processing="busy === 'archive'"
        @close="archiveOpen = false"
        @confirm="submitArchive"
    />

    <AdminDrawer :open="appealOpen" title="Record an appeal" eyebrow="Grounds for appeal" @close="appealOpen = false">
        <form class="space-y-4" @submit.prevent="submitAppeal">
            <FormTextarea id="ap-grounds" v-model="appealForm.grounds" label="Grounds" :error="appealForm.errors.grounds" required />
            <div class="flex justify-end gap-2">
                <FormButton type="button" variant="secondary" label="Cancel" @click="appealOpen = false" />
                <FormButton type="submit" variant="primary" label="Record appeal" :loading="busy === 'appeal'" loading-label="Saving…" />
            </div>
        </form>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="reviewOpen"
        title="Record the appeal outcome"
        description="The original action and this outcome both remain on the timeline."
        confirm-label="Record outcome"
        :processing="busy === 'review'"
        @close="reviewOpen = false"
        @confirm="submitReview"
    >
        <label class="mt-4 block">
            <span class="text-[12px] font-semibold text-ink/50">Outcome</span>
            <select v-model="reviewForm.outcome" class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15">
                <option v-for="opt in options.appeal_outcomes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
        </label>
    </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { toast } from '@/utils/adminRange';
import { disciplineSeverityMeta, disciplineStatusMeta, pillBase } from '@/utils/hrStatus';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    row: { type: Object, default: null },
    panel: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'refresh', 'updated']);

const statusMeta = (status) => disciplineStatusMeta(status);
const severityMeta = (severity) => disciplineSeverityMeta(severity);

const record = computed(() => props.panel?.record || null);
const templates = computed(() => props.panel?.templates || []);
const options = computed(() => props.panel?.options || {
    outcomes: [],
    appeal_outcomes: [],
});
const placeholders = computed(() => props.panel?.placeholders || []);
const can = computed(() => props.panel?.can || {});

const heading = computed(() => ({
    title: record.value?.reference || props.row?.reference || 'Case file',
    eyebrow: record.value?.staff_name || props.row?.staff_name || 'Disciplinary case',
}));

const caseEntered = ref(false);
const noteOpen = ref(false);
const evidenceOpen = ref(false);
const actionOpen = ref(false);
const confirmActionOpen = ref(false);
const transitionOpen = ref(false);
const archiveOpen = ref(false);
const appealOpen = ref(false);
const reviewOpen = ref(false);
const pendingTransition = ref(null);
const evidenceInput = ref(null);
const busy = ref('');

const pendingAppeal = computed(() => record.value?.appeals?.find((appeal) => !appeal.is_reviewed) || null);
const nextTransition = computed(() => record.value?.allowed_transitions?.[0] || null);

const matchingTemplates = computed(() =>
    templates.value
        .filter((template) => template.outcome_type === actionForm.type)
        .map((template) => ({ value: template.id, label: template.name })),
);

const noteForm = useForm({ body: '', confidential: false });
const evidenceForm = useForm({ title: '', file: null });
const actionForm = useForm({
    type: 'written_warning',
    justification: '',
    letter_subject: '',
    letter_body: '',
    template_id: '',
    suspension_starts_on: '',
    suspension_ends_on: '',
});
const transitionForm = useForm({ status: '', reason: '' });
const archiveForm = useForm({ reason: '' });
const appealForm = useForm({ grounds: '' });
const reviewForm = useForm({ outcome: 'upheld', reason: '' });

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

const applyTemplate = () => {
    const template = templates.value.find((item) => String(item.id) === String(actionForm.template_id))
        || templates.value.find((item) => item.outcome_type === actionForm.type);
    if (template) {
        actionForm.template_id = template.id;
        actionForm.letter_subject = template.name;
        actionForm.letter_body = template.body;
    }
};

watch(() => actionForm.type, applyTemplate);

const resetOverlays = () => {
    noteOpen.value = false;
    evidenceOpen.value = false;
    actionOpen.value = false;
    confirmActionOpen.value = false;
    transitionOpen.value = false;
    archiveOpen.value = false;
    appealOpen.value = false;
    reviewOpen.value = false;
    pendingTransition.value = null;
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

const openAction = () => {
    applyTemplate();
    actionOpen.value = true;
};

const askTransition = (step) => {
    pendingTransition.value = step;
    transitionForm.status = step.value;
    transitionOpen.value = true;
};

const submitTransition = ({ reason }) => {
    if (!record.value) {
        return;
    }
    transitionForm.reason = reason;
    submitAxios(transitionForm, 'transition', () => axios.post(route('admin.hr.discipline.transition', record.value.id), {
        status: transitionForm.status,
        reason,
    }), () => {
        transitionOpen.value = false;
    });
};

const submitNote = () => {
    if (!record.value) {
        return;
    }
    submitAxios(noteForm, 'note', () => axios.post(route('admin.hr.discipline.notes.store', record.value.id), {
        body: noteForm.body,
        confidential: noteForm.confidential,
    }), () => {
        noteOpen.value = false;
        noteForm.reset();
    });
};

const submitEvidence = () => {
    if (!record.value) {
        return;
    }
    const payload = new FormData();
    payload.append('title', evidenceForm.title);
    if (evidenceForm.file) {
        payload.append('file', evidenceForm.file);
    }
    submitAxios(evidenceForm, 'evidence', () => axios.post(route('admin.hr.discipline.evidence.store', record.value.id), payload), () => {
        evidenceOpen.value = false;
        evidenceForm.reset();
        if (evidenceInput.value) {
            evidenceInput.value.value = '';
        }
    });
};

const submitAction = () => {
    if (!record.value) {
        return;
    }
    submitAxios(actionForm, 'action', () => axios.post(route('admin.hr.discipline.actions.store', record.value.id), actionForm.data()), () => {
        confirmActionOpen.value = false;
        actionOpen.value = false;
    });
};

const submitArchive = ({ reason }) => {
    if (!record.value) {
        return;
    }
    archiveForm.reason = reason;
    submitAxios(archiveForm, 'archive', () => axios.post(route('admin.hr.discipline.archive', record.value.id), { reason }), () => {
        archiveOpen.value = false;
    });
};

const submitAppeal = () => {
    if (!record.value) {
        return;
    }
    submitAxios(appealForm, 'appeal', () => axios.post(route('admin.hr.discipline.appeals.store', record.value.id), {
        grounds: appealForm.grounds,
    }), () => {
        appealOpen.value = false;
        appealForm.reset();
    });
};

const submitReview = ({ reason }) => {
    if (!pendingAppeal.value) {
        return;
    }
    reviewForm.reason = reason;
    submitAxios(reviewForm, 'review', () => axios.post(route('admin.hr.discipline.appeals.review', pendingAppeal.value.id), {
        outcome: reviewForm.outcome,
        reason,
    }), () => {
        reviewOpen.value = false;
    });
};
</script>
