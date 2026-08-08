<template>
    <Head :title="entry.description" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl">
            <AppPageHeader
                title="Job details"
                :description="entry.worked_on_label"
                :back-href="route('work-log.index')"
                back-label="Work log"
            >
                <template #actions>
                    <FormButton
                        v-if="!entry.has_review"
                        variant="primary"
                        icon-left="ti ti-brand-whatsapp"
                        :label="entry.review_requested ? 'Resend review link' : 'Send review link'"
                        :loading="requestingReview"
                        loading-label="Preparing…"
                        @click="requestReview"
                    />
                    <Link
                        v-if="editFlags.can_edit"
                        :href="route('work-log.edit', entry.uid)"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-ink ring-1 ring-ink/10 transition-colors hover:bg-pale"
                    >
                        <i class="ti ti-pencil" aria-hidden="true" />
                        Edit job
                    </Link>
                </template>
                <template v-if="editFlags.review_requested || !editFlags.can_edit" #meta>
                    <span
                        v-if="editFlags.review_requested"
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200/80"
                    >
                        <i class="ti ti-lock text-sm" aria-hidden="true" />
                        Locked — review requested
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/50 ring-1 ring-ink/[0.06]"
                    >
                        <i class="ti ti-eye text-sm" aria-hidden="true" />
                        View only — edit window closed
                    </span>
                </template>
            </AppPageHeader>

            <!-- Hero record -->
            <section
                class="relative overflow-hidden rounded-xl bg-white shadow-premium ring-1 ring-ink/[0.06]"
            >
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-base/35 to-transparent"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 rounded-full bg-base/[0.07] blur-3xl"
                    aria-hidden="true"
                />

                <div class="relative p-6 sm:p-8 lg:p-10">
                    <div v-if="entry.category_label || entry.job_category" class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="entry.category_label || entry.job_subcategory"
                            class="rounded-full bg-tint px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-deep"
                        >
                            {{ entry.category_label || entry.job_subcategory }}
                        </span>
                        <span
                            v-if="entry.job_category && entry.job_subcategory"
                            class="rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/50"
                        >
                            {{ entry.job_category }}
                        </span>
                    </div>

                    <h2
                        class="max-w-3xl text-2xl font-bold tracking-tight text-ink sm:text-[1.85rem] sm:leading-tight"
                        :class="entry.category_label || entry.job_category ? 'mt-4' : ''"
                    >
                        {{ entry.description }}
                    </h2>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60"
                        >
                            <i class="ti ti-calendar text-sm text-base-action" aria-hidden="true" />
                            {{ entry.worked_on_short }}
                        </span>
                        <span
                            v-if="entry.service_label"
                            class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60"
                        >
                            <i class="ti ti-map-pin text-sm text-base-action" aria-hidden="true" />
                            {{ entry.service_label }}
                        </span>
                        <span
                            v-if="entry.media.length"
                            class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60"
                        >
                            <i class="ti ti-photo text-sm text-base-action" aria-hidden="true" />
                            {{ entry.media.length }} media
                        </span>
                    </div>
                </div>
            </section>

            <div class="mt-2.5 grid gap-2.5 lg:grid-cols-5">
                <!-- Details -->
                <section
                    class="rounded-xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7 lg:col-span-3"
                >
                    <h3 class="text-sm font-bold tracking-tight text-ink">Job details</h3>
                    <p class="mt-1 text-xs font-medium text-ink/40">
                        Logged {{ entry.created_at_label }}
                    </p>

                    <dl class="mt-6 divide-y divide-ink/[0.06]">
                        <div
                            v-for="row in detailRows"
                            :key="row.label"
                            class="flex items-start justify-between gap-6 py-3.5 first:pt-0 last:pb-0"
                        >
                            <dt class="shrink-0 text-xs font-semibold uppercase tracking-[0.08em] text-ink/40">
                                {{ row.label }}
                            </dt>
                            <dd class="text-right text-sm font-semibold text-ink">
                                <template v-if="row.href">
                                    <a
                                        :href="row.href"
                                        class="text-base-action transition-colors hover:text-base-hover"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ row.value }}
                                    </a>
                                </template>
                                <template v-else>
                                    {{ row.value }}
                                </template>
                                <span
                                    v-if="row.private"
                                    class="mt-0.5 block text-[10px] font-medium text-ink/35"
                                >
                                    Private — only you see this
                                </span>
                            </dd>
                        </div>
                    </dl>
                </section>

                <!-- Side summary -->
                <aside class="space-y-2.5 lg:col-span-2">
                    <section
                        class="rounded-xl bg-gradient-to-br from-[#123B72] via-[#0B1F3A] to-[#0B1F3A] p-5 text-white shadow-premium-ink sm:p-6"
                    >
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/45">
                            Amount charged
                        </p>
                        <p class="mt-3 font-display text-3xl font-extrabold tracking-tight">
                            <template v-if="entry.amount_naira != null">
                                ₦{{ formatAmount(entry.amount_naira) }}
                            </template>
                            <template v-else>
                                —
                            </template>
                        </p>
                        <p class="mt-2 text-xs font-medium text-white/50">
                            Private to your account. Never shown on your public page.
                        </p>
                    </section>

                    <section
                        class="rounded-xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <p class="text-sm font-bold tracking-tight text-ink">Client</p>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-tint text-base text-deep"
                                >
                                    <i class="ti ti-user" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">
                                        {{ entry.client_name || 'Not saved' }}
                                    </p>
                                    <p class="text-[11px] font-medium text-ink/40">
                                        Name · private
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-pale text-base text-ink/45"
                                >
                                    <i class="ti ti-brand-whatsapp" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">
                                        {{ entry.client_whatsapp || 'Not saved' }}
                                    </p>
                                    <p class="text-[11px] font-medium text-ink/40">
                                        For review requests
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <!-- Client review -->
            <section
                v-if="entry.review"
                class="mt-2.5 rounded-xl border border-base/15 bg-gradient-to-br from-tint/40 to-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
            >
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold tracking-tight text-ink">Client review</h3>
                    <div class="ml-auto flex gap-0.5 text-amber-500">
                        <i
                            v-for="n in 5"
                            :key="n"
                            class="ti text-sm"
                            :class="n <= entry.review.rating ? 'ti-star-filled' : 'ti-star text-ink/15'"
                            aria-hidden="true"
                        />
                    </div>
                </div>
                <p v-if="entry.review.comment" class="mt-3 text-sm font-medium leading-relaxed text-ink/70">
                    “{{ entry.review.comment }}”
                </p>
                <p class="mt-2 text-xs font-medium text-ink/40">
                    <template v-if="entry.review.client_display_name">
                        {{ entry.review.client_display_name }}
                        ·
                    </template>
                    {{ entry.review.submitted_at_label }}
                    <template v-if="entry.review.referred_by">
                        · Heard via {{ entry.review.referred_by }}
                    </template>
                </p>
                <button
                    v-if="entry.review.photo_url"
                    type="button"
                    class="mt-4 block overflow-hidden rounded-xl ring-1 ring-ink/[0.06] transition hover:ring-base/30"
                    aria-label="View client photo"
                    @click="openReviewPhoto"
                >
                    <img
                        :src="entry.review.photo_url"
                        alt="Client photo of finished work"
                        class="max-h-56 w-full object-cover"
                    />
                </button>
            </section>

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
                @close="shareSheetOpen = false"
            />

            <!-- Media -->
            <section
                class="mt-2.5 rounded-xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold tracking-tight text-ink">Photos & video</h3>
                        <p class="mt-1 text-xs font-medium text-ink/40">
                            Proof attached to this job record
                        </p>
                    </div>
                    <span
                        v-if="entry.media.length"
                        class="rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/50"
                    >
                        {{ entry.media.length }} file{{ entry.media.length === 1 ? '' : 's' }}
                    </span>
                </div>

                <MediaGallery
                    v-if="entry.media.length"
                    class="mt-5"
                    :items="entry.media"
                />

                <div
                    v-else
                    class="mt-5 rounded-xl border border-dashed border-ink/10 bg-pale/60 px-4 py-10 text-center"
                >
                    <i class="ti ti-photo-off text-2xl text-ink/25" aria-hidden="true" />
                    <p class="mt-2 text-sm font-semibold text-ink/50">No media attached</p>
                    <p class="mt-1 text-xs font-medium text-ink/35">
                        Photos can be added while this job is still editable.
                    </p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppPageHeader from '@/Components/AppPageHeader.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import MediaGallery from '@/Components/Media/MediaGallery.vue';
import MediaLightbox from '@/Components/Media/MediaLightbox.vue';
import ReviewShareSheet from '@/Components/Reviews/ReviewShareSheet.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    entry: { type: Object, required: true },
    editFlags: { type: Object, required: true },
    reviewInvite: { type: Object, default: null },
    whatsappShare: { type: Object, default: null },
    openReviewShare: { type: Boolean, default: false },
});

const page = usePage();
const requestingReview = ref(false);
const shareSheetOpen = ref(false);
const sharePayload = ref(null);
const reviewLightboxOpen = ref(false);
const reviewLightboxItems = computed(() => {
    if (!props.entry.review?.photo_url) {
        return [];
    }
    return [{
        url: props.entry.review.photo_url,
        kind: 'image',
        original_name: 'Client photo of finished work',
    }];
});

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
    const reviewUrl = raw.review_url || '';
    if (!appUrl && !webUrl && !protocolUrl) {
        return null;
    }
    return {
        url: appUrl,
        whatsapp_app_url: appUrl,
        whatsapp_web_url: webUrl,
        whatsapp_protocol_url: protocolUrl,
        review_url: reviewUrl,
        message: raw.message || '',
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

const requestReview = () => {
    // Open the share modal immediately when a link already exists (resend).
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
            onSuccess: () => {
                const share = normalizeShare(
                    page.props.whatsappShare || page.props.reviewInvite || props.whatsappShare,
                );

                if (!share) {
                    window.dispatchEvent(
                        new CustomEvent('isabi:toast', {
                            detail: {
                                type: 'error',
                                message: 'Could not prepare the review link. Please try again.',
                                duration: 5500,
                            },
                        }),
                    );
                    return;
                }

                openShareSheet(share);
            },
            onError: () => {
                window.dispatchEvent(
                    new CustomEvent('isabi:toast', {
                        detail: {
                            type: 'error',
                            message: 'Could not prepare the review link. Please try again.',
                            duration: 4500,
                        },
                    }),
                );
            },
            onFinish: () => {
                requestingReview.value = false;
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

const detailRows = computed(() => {
    const rows = [
        { label: 'Date', value: props.entry.worked_on_label || '—' },
        {
            label: 'Category',
            value: props.entry.job_subcategory && props.entry.job_category
                ? `${props.entry.job_subcategory} · ${props.entry.job_category}`
                : (props.entry.category_label || props.entry.job_category || '—'),
        },
        { label: 'Location', value: props.entry.service_label || '—' },
        {
            label: 'Client',
            value: props.entry.client_name || '—',
            private: !!props.entry.client_name,
        },
        {
            label: 'WhatsApp',
            value: props.entry.client_whatsapp || '—',
            href: props.entry.client_whatsapp
                ? `https://wa.me/${normalizeWa(props.entry.client_whatsapp)}`
                : null,
        },
    ];

    return rows;
});

const normalizeWa = (value) => {
    const digits = String(value).replace(/\D/g, '');
    if (digits.startsWith('0') && digits.length === 11) {
        return `234${digits.slice(1)}`;
    }
    return digits;
};

const formatAmount = (value) =>
    Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
</script>
