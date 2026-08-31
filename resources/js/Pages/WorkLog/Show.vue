<template>
    <Head :title="entry.description" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl space-y-5 sm:space-y-6">
            <!-- Hero -->
            <section
                class="job-hero relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-6 shadow-premium-ink sm:px-8 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.14]"
                    style="
                        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                        background-size: 18px 18px;
                    "
                    aria-hidden="true"
                />

                <div class="relative">
                    <div class="flex items-center justify-between gap-3">
                        <Link
                            :href="backHref"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.08] px-3 py-1.5 text-xs font-bold text-white/70 ring-1 ring-white/10 transition-colors hover:bg-white/15 hover:text-white"
                        >
                            <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                            {{ backLabel }}
                        </Link>

                        <Link
                            v-if="editFlags.can_edit"
                            :href="route('work-log.edit', entry.uid)"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.08] px-3 py-1.5 text-xs font-bold text-white/70 ring-1 ring-white/10 transition-colors hover:bg-white/15 hover:text-white"
                        >
                            <i class="ti ti-pencil text-sm" aria-hidden="true" />
                            Edit
                        </Link>
                    </div>

                    <p
                        v-if="entry.category_label || entry.job_category"
                        class="mt-5 text-[11px] font-bold uppercase tracking-[0.16em] text-white/45"
                    >
                        {{ entry.category_label || entry.job_category }}
                    </p>

                    <h1
                        class="mt-2 max-w-3xl font-editorial text-[1.7rem] font-semibold leading-[1.15] tracking-tight text-white sm:text-[2.35rem]"
                    >
                        {{ entry.description }}
                    </h1>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span
                            v-for="chip in heroChips"
                            :key="chip.label"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.08] px-3 py-1.5 text-xs font-semibold text-white/75 ring-1 ring-white/10"
                        >
                            <i :class="chip.icon" class="text-sm text-white/50" aria-hidden="true" />
                            {{ chip.label }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Review progress + next step -->
            <section
                class="job-card overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                aria-labelledby="progress-heading"
            >
                <div class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">
                            Review progress
                        </p>
                        <h2
                            id="progress-heading"
                            class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink"
                        >
                            {{ status.title }}
                        </h2>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-bold ring-1"
                        :class="status.pillClass"
                    >
                        <i :class="status.icon" class="text-sm" aria-hidden="true" />
                        {{ status.pill }}
                    </span>
                </div>

                <p class="mt-1.5 px-5 text-sm font-medium leading-relaxed text-ink/50 sm:px-6">
                    {{ status.body }}
                </p>

                <div class="px-5 pt-6 sm:px-6">
                    <ReviewStepper :steps="steps" class="mx-auto max-w-xl" />
                </div>

                <div v-if="hasActions" class="flex flex-wrap gap-2 px-5 pb-5 pt-6 sm:px-6 sm:pb-6">
                    <FormButton
                        v-if="!entry.has_review"
                        variant="accent"
                        icon-left="ti ti-brand-whatsapp"
                        :label="entry.review_requested ? 'Resend review link' : 'Send review link'"
                        :loading="requestingReview"
                        loading-label="Preparing…"
                        @click="requestReview"
                    />
                    <FormButton
                        v-if="!entry.has_review && entry.reminder_due"
                        variant="secondary"
                        icon-left="ti ti-bell"
                        label="Send reminder"
                        :loading="sendingReminder"
                        loading-label="Preparing…"
                        @click="sendReminder"
                    />
                    <a
                        v-if="entry.public_url"
                        :href="entry.public_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition-colors hover:bg-tint"
                    >
                        <i class="ti ti-world" aria-hidden="true" />
                        Public page
                    </a>
                    <a
                        v-if="entry.has_review && pageUrl"
                        :href="pageUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        <i class="ti ti-external-link" aria-hidden="true" />
                        See it on my page
                    </a>
                </div>

                <!-- Allowance / lock strip -->
                <div
                    v-if="quotaNote || lockNote"
                    class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-ink/[0.05] bg-pale/60 px-5 py-3.5 sm:px-6"
                >
                    <p v-if="quotaNote" class="text-xs font-semibold text-ink/55">
                        {{ quotaNote }}
                    </p>
                    <span
                        v-else-if="lockNote"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink/50"
                    >
                        <i :class="lockNote.icon" class="text-sm text-ink/35" aria-hidden="true" />
                        {{ lockNote.label }}
                    </span>
                    <Link
                        v-if="showBuyTokens"
                        :href="route('tokens.buy')"
                        class="tap-target shrink-0 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-base-action ring-1 ring-base-action/25 transition-colors hover:bg-tint"
                    >
                        Get tokens
                    </Link>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-5 lg:gap-6">
                <!-- Main column -->
                <div class="space-y-5 lg:col-span-3">
                    <!-- Client review -->
                    <section
                        v-if="entry.review"
                        class="job-card overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-emerald-200/70"
                    >
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-emerald-100/80 bg-gradient-to-r from-emerald-50/80 to-white px-5 py-4 sm:px-6"
                        >
                            <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                                Client review
                            </h2>
                            <StarDisplay :rating="entry.review.rating" class="ms-auto" />
                            <span
                                v-if="entry.review.would_recommend === true"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-100/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-800"
                            >
                                <i class="ti ti-thumb-up text-[11px]" aria-hidden="true" />
                                Recommends
                            </span>
                            <span
                                v-else-if="entry.review.would_recommend === false"
                                class="inline-flex items-center gap-1 rounded-full bg-ink/[0.05] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-ink/50"
                            >
                                Wouldn’t recommend
                            </span>
                        </div>

                        <div class="px-5 py-5 sm:px-6 sm:py-6">
                            <p
                                v-if="entry.review.comment"
                                class="font-editorial text-lg font-medium leading-relaxed text-ink/80 sm:text-xl"
                            >
                                “{{ entry.review.comment }}”
                            </p>
                            <p class="mt-3 text-xs font-medium text-ink/40">
                                <template v-if="entry.review.client_display_name">
                                    {{ entry.review.client_display_name }} ·
                                </template>
                                {{ entry.review.submitted_at_label }}
                                <template v-if="entry.review.referred_by">
                                    · Heard via {{ entry.review.referred_by }}
                                </template>
                            </p>

                            <button
                                v-if="entry.review.photo_url"
                                type="button"
                                class="mt-5 block w-full overflow-hidden rounded-2xl ring-1 ring-ink/[0.06] transition hover:ring-base/30"
                                aria-label="View client photo"
                                @click="openReviewPhoto"
                            >
                                <img
                                    :src="entry.review.photo_url"
                                    alt="Client photo of finished work"
                                    class="max-h-64 w-full object-cover"
                                />
                            </button>

                            <p
                                class="mt-5 rounded-2xl bg-pale/70 px-4 py-3 text-xs font-medium leading-relaxed text-ink/45"
                            >
                                Written by your client and shown exactly as they left it — you can’t
                                edit it, and neither can we.
                            </p>
                        </div>
                    </section>

                    <!-- Media -->
                    <section
                        class="job-card overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                    >
                        <div
                            class="flex items-center justify-between gap-3 border-b border-ink/[0.05] px-5 py-4 sm:px-6"
                        >
                            <div>
                                <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                                    Photos &amp; video
                                </h2>
                                <p class="mt-0.5 text-xs font-medium text-ink/40">
                                    Proof attached to this job
                                </p>
                            </div>
                            <span
                                v-if="entry.media.length"
                                class="shrink-0 rounded-full bg-tint px-2.5 py-1 text-[11px] font-bold text-deep"
                            >
                                {{ entry.media.length }}
                            </span>
                        </div>

                        <div class="p-4 sm:p-5">
                            <MediaGallery v-if="entry.media.length" :items="entry.media" />
                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-ink/10 bg-pale/60 px-4 py-10 text-center"
                            >
                                <span
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-ink/25 shadow-sm ring-1 ring-ink/[0.04]"
                                >
                                    <i class="ti ti-photo-off" aria-hidden="true" />
                                </span>
                                <p class="mt-3 text-sm font-bold text-ink/55">No media yet</p>
                                <p class="mx-auto mt-1 max-w-xs text-xs font-medium leading-relaxed text-ink/35">
                                    {{
                                        editFlags.can_edit
                                            ? 'Add photos while this job is still editable — they make your page far more convincing.'
                                            : 'This job was logged without photos.'
                                    }}
                                </p>
                                <Link
                                    v-if="editFlags.can_edit"
                                    :href="route('work-log.edit', entry.uid)"
                                    class="tap-target mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-xs font-bold text-base-action ring-1 ring-base-action/25 transition-colors hover:bg-tint"
                                >
                                    <i class="ti ti-camera-plus text-sm" aria-hidden="true" />
                                    Add photos
                                </Link>
                            </div>
                        </div>
                    </section>

                    <!-- Details -->
                    <section
                        class="job-card overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                    >
                        <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                            <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                                Job details
                            </h2>
                            <p class="mt-0.5 text-xs font-medium text-ink/40">
                                Exactly what you recorded
                            </p>
                        </div>

                        <dl class="divide-y divide-ink/[0.05] px-5 sm:px-6">
                            <div
                                v-for="row in detailRows"
                                :key="row.label"
                                class="grid gap-1 py-3.5 sm:grid-cols-[7.5rem_1fr] sm:items-start sm:gap-6"
                            >
                                <dt class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/35">
                                    {{ row.label }}
                                </dt>
                                <dd class="text-sm font-semibold text-ink sm:text-right">
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <!-- Side -->
                <aside class="space-y-5 lg:col-span-2">
                    <ShareEmbedPanel
                        v-if="entry.public_url"
                        :profile-embed-url="profileEmbedUrl"
                        :job-embed-url="entry.embed_url"
                        :public-url="entry.public_url"
                        :reference="entry.reference"
                    />
                    <!-- Amount -->
                    <section
                        class="job-card relative overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <div
                            class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-coral/[0.07] blur-2xl"
                            aria-hidden="true"
                        />
                        <div class="relative flex items-start justify-between gap-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                                Amount charged
                            </p>
                            <span
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-coral-tint/60 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-coral-deep"
                            >
                                <i class="ti ti-lock text-[11px]" aria-hidden="true" />
                                Private
                            </span>
                        </div>
                        <p
                            class="relative mt-3 text-[2.1rem] font-bold leading-none tracking-tight tabular-nums text-ink sm:text-[2.4rem]"
                        >
                            <template v-if="entry.amount_naira != null">
                                ₦{{ formatAmount(entry.amount_naira) }}
                            </template>
                            <template v-else>
                                <span class="text-ink/25">Not recorded</span>
                            </template>
                        </p>
                        <p class="relative mt-3 text-xs font-medium leading-relaxed text-ink/45">
                            Kept to your account only. It never appears on your public page or in a
                            client’s review.
                        </p>
                    </section>

                    <!-- Client -->
                    <section
                        class="job-card overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                            Client
                        </h2>
                        <p class="mt-0.5 text-xs font-medium text-ink/40">
                            Who this job was for
                        </p>

                        <div class="mt-4 space-y-2.5">
                            <div class="flex items-center gap-3 rounded-2xl bg-pale/70 px-3.5 py-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-lg text-deep"
                                >
                                    <i class="ti ti-user" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-ink">
                                        {{ entry.client_name || 'Not saved' }}
                                    </p>
                                    <p class="text-[11px] font-medium text-ink/40">Name · private</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-2xl bg-pale/70 px-3.5 py-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-700"
                                >
                                    <i class="ti ti-brand-whatsapp" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-bold"
                                        :class="entry.client_whatsapp ? 'text-ink' : 'text-ink/45'"
                                    >
                                        {{ entry.client_whatsapp || 'Not saved' }}
                                    </p>
                                    <p class="text-[11px] font-medium text-ink/40">
                                        Where review links go
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a
                            v-if="entry.client_whatsapp"
                            :href="`https://wa.me/${normalizeWa(entry.client_whatsapp)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] px-4 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-12px_rgba(37,211,102,0.6)] transition-opacity hover:opacity-95"
                        >
                            <i class="ti ti-brand-whatsapp text-base" aria-hidden="true" />
                            Chat on WhatsApp
                        </a>
                        <Link
                            v-else-if="editFlags.can_edit"
                            :href="route('work-log.edit', entry.uid)"
                            class="tap-target mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition-colors hover:bg-tint"
                        >
                            <i class="ti ti-plus text-base" aria-hidden="true" />
                            Add client number
                        </Link>
                    </section>
                </aside>
            </div>
        </div>

        <MediaLightbox
            v-model:show="reviewLightboxOpen"
            :items="reviewLightboxItems"
            :start-index="0"
        />

        <ReviewShareSheet
            :show="shareSheetOpen"
            :whatsapp-url="sharePayload?.url || ''"
            :app-url="sharePayload?.whatsapp_app_url || ''"
            :protocol-url="sharePayload?.whatsapp_protocol_url || ''"
            :web-url="sharePayload?.whatsapp_web_url || ''"
            :review-url="sharePayload?.review_url || ''"
            :client-whatsapp="entry.client_whatsapp || ''"
            :message="sharePayload?.message || ''"
            :kind="sharePayload?.kind || 'invite'"
            @close="shareSheetOpen = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import ShareEmbedPanel from '@/Components/App/ShareEmbedPanel.vue';
import MediaGallery from '@/Components/Media/MediaGallery.vue';
import MediaLightbox from '@/Components/Media/MediaLightbox.vue';
import ReviewShareSheet from '@/Components/Reviews/ReviewShareSheet.vue';
import StarDisplay from '@/Components/Reviews/StarDisplay.vue';
import ReviewStepper from '@/Components/WorkLog/ReviewStepper.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { resolveBackTarget } from '@/utils/backNavigation';
import { toast } from '@/utils/toast';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    entry: { type: Object, required: true },
    editFlags: { type: Object, required: true },
    reviewInvite: { type: Object, default: null },
    reviewQuota: { type: Object, default: null },
    whatsappShare: { type: Object, default: null },
    openReviewShare: { type: Boolean, default: false },
});

const page = usePage();
const backTarget = resolveBackTarget({
    fallbackHref: route('work-log.index'),
    fallbackLabel: 'Work log',
    ownSlug: page.props.auth?.user?.slug,
});
const backHref = backTarget.href;
const backLabel = backTarget.label;
const requestingReview = ref(false);
const sendingReminder = ref(false);
const shareSheetOpen = ref(false);
const sharePayload = ref(null);
const reviewLightboxOpen = ref(false);

const pageUrl = computed(() => page.props.auth?.user?.public_url || '');

const profileEmbedUrl = computed(() => {
    const slug = page.props.auth?.user?.slug;
    if (!slug) {
        return '';
    }
    try {
        return route('embed.profile', slug);
    } catch {
        return '';
    }
});

const heroChips = computed(() => {
    const chips = [{ icon: 'ti ti-calendar-event', label: props.entry.worked_on_short }];

    if (props.entry.service_label) {
        chips.push({ icon: 'ti ti-map-pin', label: props.entry.service_label });
    }
    if (props.entry.media.length) {
        chips.push({
            icon: 'ti ti-photo',
            label: `${props.entry.media.length} ${props.entry.media.length === 1 ? 'file' : 'files'}`,
        });
    }

    return chips;
});

const status = computed(() => {
    if (props.entry.has_review) {
        return {
            pill: 'Reviewed',
            title: 'Your client backed this job up',
            body: 'The review below is live on your public page, next to this job.',
            icon: 'ti ti-rosette-discount-check',
            pillClass: 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
        };
    }

    if (props.entry.review_requested) {
        const sent = props.entry.review_requested_ago
            ? `Link sent ${props.entry.review_requested_ago}. `
            : 'Your client has the link. ';

        return {
            pill: 'Awaiting reply',
            title: 'Waiting on your client',
            body:
                sent +
                (props.entry.reminder_due
                    ? 'It’s been a while — one friendly WhatsApp reminder usually does it.'
                    : 'Give them a little time before you nudge.'),
            icon: 'ti ti-hourglass',
            pillClass: 'bg-amber-50 text-amber-800 ring-amber-200/80',
        };
    }

    return {
        pill: 'Logged',
        title: 'Ask your client for a review',
        body: 'Send a WhatsApp link tied to this job. Only they can write it — that’s what makes it count.',
        icon: 'ti ti-circle-dashed-check',
        pillClass: 'bg-tint text-deep ring-base/15',
    };
});

const steps = computed(() => [
    {
        key: 'logged',
        label: 'Logged',
        sub: props.entry.created_at_short || '—',
        icon: 'ti ti-briefcase',
        state: 'done',
    },
    {
        key: 'sent',
        label: 'Link sent',
        sub: props.entry.review_requested
            ? props.entry.review_requested_short || 'Sent'
            : 'Not sent',
        icon: 'ti ti-send',
        state:
            props.entry.review_requested || props.entry.has_review ? 'done' : 'current',
    },
    {
        key: 'reviewed',
        label: 'Reviewed',
        sub: props.entry.has_review
            ? props.entry.review?.submitted_at_label || 'Received'
            : props.entry.review_requested
              ? 'Waiting'
              : '—',
        icon: 'ti ti-star',
        state: props.entry.has_review
            ? 'done'
            : props.entry.review_requested
              ? 'current'
              : 'pending',
    },
]);

const hasActions = computed(
    () => !props.entry.has_review || !!pageUrl.value || !!props.entry.public_url,
);

const quota = computed(() => props.reviewQuota);

const showBuyTokens = computed(() => {
    if (props.entry.has_review || props.entry.review_requested || !quota.value) {
        return false;
    }
    return !quota.value.can_send_new || quota.value.next_send_uses_token;
});

const quotaNote = computed(() => {
    if (props.entry.has_review || props.entry.review_requested || !quota.value) {
        return '';
    }

    const q = quota.value;

    if (q.has_annual) {
        return 'Annual plan — review links are unlimited.';
    }
    if (q.next_send_is_free) {
        return `${q.free_remaining} of ${q.free_limit} free review links left this month.`;
    }
    if (q.token_balance >= q.token_cost) {
        return `Free links used up — sending costs ${q.token_cost} token (you have ${q.token_balance}).`;
    }
    return 'Free links used up and your token balance is empty.';
});

const lockNote = computed(() => {
    if (props.entry.has_review) {
        return { label: 'Locked — a client review is attached', icon: 'ti ti-lock' };
    }
    if (props.editFlags.review_requested) {
        return { label: 'Locked — a review link is already out', icon: 'ti ti-lock' };
    }
    if (!props.editFlags.can_edit) {
        return { label: 'View only — the edit window has closed', icon: 'ti ti-eye' };
    }
    return null;
});

const reviewLightboxItems = computed(() => {
    if (!props.entry.review?.photo_url) {
        return [];
    }
    return [
        {
            url: props.entry.review.photo_url,
            preview_url: props.entry.review.photo_preview_url || props.entry.review.photo_url,
            thumb_url: props.entry.review.photo_thumb_url || props.entry.review.photo_url,
            kind: 'image',
            original_name: 'Client photo of finished work',
        },
    ];
});

const detailRows = computed(() => [
    { label: 'Date', value: props.entry.worked_on_label || '—' },
    {
        label: 'Category',
        value:
            props.entry.job_subcategory && props.entry.job_category
                ? `${props.entry.job_subcategory} · ${props.entry.job_category}`
                : props.entry.category_label || props.entry.job_category || '—',
    },
    { label: 'Location', value: props.entry.service_label || '—' },
    { label: 'Logged', value: props.entry.created_at_label || '—' },
]);

const openReviewPhoto = () => {
    reviewLightboxOpen.value = true;
};

const normalizeShare = (raw) => {
    if (!raw) {
        return null;
    }
    const appUrl = raw.whatsapp_app_url || raw.url || raw.whatsapp_url || '';
    const webUrl = raw.whatsapp_web_url || '';
    const protocolUrl = raw.whatsapp_protocol_url || '';
    const message = raw.message || '';
    let reviewUrl = raw.review_url || '';
    if (!reviewUrl && message) {
        const match = String(message).match(/https?:\/\/[^\s]+/i);
        reviewUrl = match?.[0]?.replace(/[).,;]+$/, '') || '';
    }
    if (!appUrl && !webUrl && !protocolUrl && !reviewUrl) {
        return null;
    }
    return {
        url: appUrl,
        whatsapp_app_url: appUrl,
        whatsapp_web_url: webUrl,
        whatsapp_protocol_url: protocolUrl,
        review_url: reviewUrl,
        message,
        kind: raw.kind || 'invite',
    };
};

const openShareSheet = (payload) => {
    const share = normalizeShare(payload);
    if (!share) {
        return;
    }
    sharePayload.value = share;
    shareSheetOpen.value = true;
};

const openPreparedShare = (fallbackMessage) => {
    const share = normalizeShare(
        page.props.whatsappShare || page.props.reviewInvite || props.whatsappShare,
    );

    if (!share) {
        toast(fallbackMessage, 'error', 5500);
        return;
    }

    openShareSheet(share);
};

const requestReview = () => {
    const existing = normalizeShare(props.reviewInvite);
    if (existing) {
        openShareSheet(existing);
    }

    requestingReview.value = true;
    router.post(
        route('work-log.request-review', props.entry.uid),
        {},
        {
            preserveScroll: true,
            onSuccess: () => openPreparedShare('Could not prepare the review link. Please try again.'),
            onError: () => {
                toast('Could not prepare the review link. Please try again.', 'error', 4500);
            },
            onFinish: () => {
                requestingReview.value = false;
            },
        },
    );
};

const sendReminder = () => {
    sendingReminder.value = true;
    router.post(
        route('work-log.remind-review', props.entry.uid),
        {},
        {
            preserveScroll: true,
            onSuccess: () => openPreparedShare('Could not prepare the reminder. Please try again.'),
            onError: () => {
                toast('Could not prepare the reminder. Please try again.', 'error', 4500);
            },
            onFinish: () => {
                sendingReminder.value = false;
            },
        },
    );
};

watch(
    () => [props.openReviewShare, props.whatsappShare, props.reviewInvite],
    ([shouldOpen, share, invite]) => {
        if (shouldOpen) {
            openShareSheet(share || invite);
        }
    },
    { immediate: true },
);

function normalizeWa(value) {
    const digits = String(value).replace(/\D/g, '');
    if (digits.startsWith('0') && digits.length === 11) {
        return `234${digits.slice(1)}`;
    }
    return digits;
}

function formatAmount(value) {
    return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}
</script>

<style scoped>
.job-hero,
.job-card {
    animation: rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.job-card {
    animation-delay: 0.06s;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .job-hero,
    .job-card {
        animation: none;
    }
}
</style>
