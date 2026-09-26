<template>
    <component :is="isLoggedIn ? AuthenticatedLayout : 'div'" :full-bleed="isLoggedIn || undefined">
        <div class="job-page min-h-dvh bg-pale text-ink" :class="{ 'font-app': isLoggedIn }">
            <Head :title="pageTitle" />

            <header
                v-if="!isLoggedIn"
                class="sticky top-0 z-40 border-b border-white/10 bg-[#071427]"
            >
                <div
                    class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-2.5 py-3 sm:gap-4 sm:px-6 sm:py-3.5 lg:px-8"
                    :style="headerPadStyle"
                >
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center gap-2.5 font-display text-[1.35rem] font-extrabold tracking-tight text-white"
                    >
                        <BrandMark variant="mark" color="#FFFFFF" class="h-8 w-8 shrink-0" />
                        Kraftrack
                    </Link>
                    <Link
                        :href="profile.public_url"
                        class="tap-target inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white/85 ring-1 ring-white/15 transition-colors hover:bg-white/15 hover:text-white"
                    >
                        View page
                        <i class="ti ti-arrow-up-right text-sm" aria-hidden="true" />
                    </Link>
                </div>
            </header>

            <!-- Compact artisan strip -->
            <section class="relative overflow-hidden bg-[#071427]">
                <div
                    class="pointer-events-none absolute inset-0"
                    :style="heroWashStyle"
                    aria-hidden="true"
                />
                <div
                    class="relative mx-auto max-w-6xl px-2.5 pb-8 pt-5 sm:px-6 sm:pb-10 sm:pt-7 lg:px-8"
                >
                    <nav
                        class="mb-5 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-white/40"
                        aria-label="Breadcrumb"
                    >
                        <Link
                            :href="route('public.directory')"
                            class="transition-colors hover:text-white"
                        >
                            Artisans
                        </Link>
                        <i class="ti ti-chevron-right text-[10px] text-white/25" aria-hidden="true" />
                        <Link
                            :href="profile.public_url"
                            class="max-w-[12rem] truncate transition-colors hover:text-white sm:max-w-none"
                        >
                            {{ profile.business_name }}
                        </Link>
                        <i class="ti ti-chevron-right text-[10px] text-white/25" aria-hidden="true" />
                        <span class="text-white/70">This job</span>
                    </nav>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <Link
                            :href="profile.public_url"
                            class="flex min-w-0 items-center gap-3.5 transition-opacity hover:opacity-90"
                        >
                            <span
                                class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-deep to-ink text-sm font-extrabold text-white shadow-premium-ink ring-2 ring-white/20 sm:h-14 sm:w-14"
                            >
                                <img
                                    v-if="profile.logo_url || profile.avatar_url"
                                    :src="profile.logo_url || profile.avatar_url"
                                    :alt="profile.business_name"
                                    class="h-full w-full object-cover"
                                    :class="profile.logo_url ? 'bg-white object-contain p-1.5' : ''"
                                />
                                <span v-else class="font-display">{{ initials }}</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-white sm:text-[15px]">
                                    {{ profile.business_name }}
                                </span>
                                <span class="mt-0.5 block truncate text-xs font-medium text-white/45">
                                    {{ artisanMeta }}
                                </span>
                            </span>
                        </Link>

                        <button
                            v-if="showQuoteCta"
                            type="button"
                            class="tap-target hidden shrink-0 items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_14px_32px_-12px_rgba(26,79,181,0.65)] transition-colors hover:bg-base-hover sm:inline-flex"
                            @click="scrollToQuote"
                        >
                            <i class="ti ti-message-quote text-lg" aria-hidden="true" />
                            Request a quote
                        </button>
                    </div>
                </div>
            </section>

            <main
                class="relative z-[1] -mt-4 rounded-t-xl bg-pale pt-6 sm:-mt-6 sm:rounded-t-[1.5rem] sm:pt-10"
                :class="showQuoteCta ? 'pb-28 sm:pb-20' : 'pb-16 sm:pb-20'"
            >
                <div class="mx-auto max-w-6xl space-y-10 px-2.5 sm:space-y-12 sm:px-6 lg:px-8">
                    <!-- 1. Job details — full content width -->
                    <section class="job-fade">
                        <p
                            v-if="job.category_label || job.job_category"
                            class="text-[11px] font-bold uppercase tracking-[0.18em] text-zinc-500"
                        >
                            {{ job.category_label || job.job_category }}
                        </p>
                        <h1
                            class="mt-2.5 w-full font-editorial text-[clamp(1.85rem,4.5vw,3rem)] font-semibold leading-[1.12] tracking-tight text-zinc-900"
                        >
                            {{ jobTitle }}
                        </h1>

                        <div
                            v-if="job.worked_on_label || job.service_label"
                            class="mt-5 flex flex-wrap gap-2"
                        >
                            <span
                                v-if="job.worked_on_label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-zinc-600 ring-1 ring-zinc-200/80"
                            >
                                <i class="ti ti-calendar-event text-sm text-zinc-400" aria-hidden="true" />
                                <time :datetime="job.worked_on || undefined">{{
                                    job.worked_on_label
                                }}</time>
                            </span>
                            <span
                                v-if="job.service_label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-zinc-600 ring-1 ring-zinc-200/80"
                            >
                                <i class="ti ti-map-pin text-sm text-zinc-400" aria-hidden="true" />
                                {{ job.service_label }}
                            </span>
                        </div>

                        <p
                            v-if="showDescription"
                            class="mt-6 w-full text-[15px] font-medium leading-relaxed text-zinc-700 sm:text-base"
                        >
                            {{ job.description }}
                        </p>

                        <button
                            v-if="showQuoteCta"
                            type="button"
                            class="tap-target mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover sm:hidden"
                            @click="scrollToQuote"
                        >
                            <i class="ti ti-message-quote text-lg" aria-hidden="true" />
                            Request a quote
                        </button>
                    </section>

                    <!-- 2. Finished work — premium grid / mobile masonry -->
                    <section v-if="job.media?.length" class="job-fade job-fade-delay-1">
                        <div class="mb-4 flex items-end justify-between gap-3 sm:mb-5">
                            <div>
                                <h2
                                    class="font-editorial text-xl font-semibold tracking-tight text-ink sm:text-2xl"
                                >
                                    Finished work
                                </h2>
                                <p class="mt-1 text-sm font-medium text-ink/40">
                                    Photos and video from this job
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-[11px] font-bold uppercase tracking-[0.1em] text-ink/30"
                            >
                                {{ job.media.length }}
                                {{ job.media.length === 1 ? 'item' : 'items' }}
                            </span>
                        </div>

                        <div
                            class="rounded-[1.25rem] bg-white p-2.5 shadow-premium ring-1 ring-ink/[0.05] sm:rounded-[1.5rem] sm:p-4"
                        >
                            <MediaGallery
                                :items="job.media"
                                layout="masonry"
                            />
                        </div>
                    </section>

                    <!-- 3. Client review — full width, below media -->
                    <section
                        v-if="job.review"
                        class="job-fade job-fade-delay-2 overflow-hidden rounded-[1.25rem] bg-white shadow-premium ring-1 ring-emerald-200/50 sm:rounded-[1.5rem]"
                    >
                        <div
                            class="flex flex-wrap items-center gap-x-4 gap-y-3 border-b border-emerald-100/80 bg-gradient-to-r from-emerald-50/90 via-white to-white px-4 py-4 sm:px-6 sm:py-5"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-ink">
                                    {{ job.review.client_display_name || 'Verified client' }}
                                </p>
                                <p
                                    v-if="job.review.submitted_at_label"
                                    class="mt-0.5 text-xs font-medium text-ink/40"
                                >
                                    {{ job.review.submitted_at_label }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <StarDisplay :rating="job.review.rating" size="md" />
                                <span class="text-sm font-extrabold tabular-nums tracking-tight text-ink">
                                    {{ Number(job.review.rating).toFixed(1) }}
                                </span>
                            </div>
                        </div>

                        <div class="px-4 py-6 sm:px-6 sm:py-8">
                            <p
                                v-if="job.review.comment"
                                class="w-full font-editorial text-[1.05rem] font-medium leading-relaxed text-ink/80 sm:text-lg"
                            >
                                &ldquo;{{ job.review.comment }}&rdquo;
                            </p>
                            <p v-else class="text-sm font-medium text-ink/45">
                                Verified client left a rating for this job.
                            </p>

                            <button
                                v-if="job.review.photo_url"
                                type="button"
                                class="group/photo mt-5 block w-full max-w-sm overflow-hidden rounded-2xl ring-1 ring-ink/[0.06] transition duration-300 hover:ring-base-action/35 sm:max-w-md"
                                aria-label="View client photo"
                                @click="openReviewPhoto"
                            >
                                <img
                                    :src="job.review.photo_thumb_url || job.review.photo_url"
                                    alt="Client photo of finished work"
                                    loading="lazy"
                                    class="aspect-[4/3] w-full object-cover transition-transform duration-500 group-hover/photo:scale-[1.015]"
                                />
                            </button>
                        </div>
                    </section>

                    <p
                        v-else
                        class="job-fade job-fade-delay-2 text-sm font-medium text-ink/45"
                    >
                        This job is on {{ profile.business_name }}&rsquo;s public page. A client
                        review has not been left yet.
                    </p>

                    <!-- Quote -->
                    <section
                        v-if="showQuoteCta"
                        id="quote"
                        ref="quoteSection"
                        class="job-fade job-fade-delay-3 scroll-mt-24 overflow-hidden rounded-[1.25rem] bg-white shadow-premium ring-1 ring-base-action/12 sm:rounded-[1.5rem]"
                    >
                        <div
                            class="border-b border-ink/[0.05] bg-gradient-to-r from-tint/90 via-white to-white px-4 py-5 sm:px-6 sm:py-6"
                        >
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action"
                            >
                                Get a quote
                            </p>
                            <h2
                                class="mt-1.5 font-editorial text-xl font-semibold tracking-tight text-ink sm:text-2xl"
                            >
                                Interested in similar work?
                            </h2>
                            <p class="mt-1.5 max-w-xl text-sm font-medium leading-relaxed text-ink/45">
                                Leave your details — {{ profile.business_name }} will follow up
                                directly.
                            </p>
                        </div>
                        <div class="px-4 py-5 sm:px-6 sm:py-6">
                            <QuoteRequestForm
                                :quote-url="quoteUrl"
                                :business-name="profile.business_name"
                            />
                        </div>
                    </section>

                    <Link
                        :href="profile.public_url"
                        class="job-fade job-fade-delay-3 tap-target group inline-flex items-center gap-2.5 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        More work from {{ profile.business_name }}
                        <i
                            class="ti ti-arrow-right transition-transform group-hover:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </main>

            <div
                v-if="showQuoteCta"
                class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/10 bg-white/95 px-3 py-3 backdrop-blur-md sm:hidden"
                :style="mobileCtaPadStyle"
            >
                <button
                    type="button"
                    class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)]"
                    @click="scrollToQuote"
                >
                    <i class="ti ti-message-quote text-lg" aria-hidden="true" />
                    Request a quote
                </button>
            </div>

            <SiteFooter v-if="!isLoggedIn" />

            <MediaLightbox
                v-model:show="lightboxOpen"
                :items="lightboxItems"
                :start-index="lightboxIndex"
            />
        </div>
    </component>
</template>

<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import MediaGallery from '@/Components/Media/MediaGallery.vue';
import MediaLightbox from '@/Components/Media/MediaLightbox.vue';
import QuoteRequestForm from '@/Components/Public/QuoteRequestForm.vue';
import StarDisplay from '@/Components/Reviews/StarDisplay.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { warmMediaItem } from '@/utils/mediaWarm';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    job: { type: Object, required: true },
    quoteUrl: { type: String, default: '' },
    viewerIsOwner: { type: Boolean, default: false },
});

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);
const quoteSection = ref(null);
const lightboxOpen = ref(false);
const lightboxItems = ref([]);
const lightboxIndex = ref(0);

const showQuoteCta = computed(() => !props.viewerIsOwner && !!props.quoteUrl);
const quoteUrl = computed(() => props.quoteUrl || '');

const jobTitle = computed(
    () => props.job.subject || props.job.description || 'Completed work',
);

const pageTitle = computed(() => `${jobTitle.value} · ${props.profile.business_name}`);

const artisanMeta = computed(() =>
    [props.profile.trade, props.profile.area_label].filter(Boolean).join(' · '),
);

const headerPadStyle = {
    paddingTop: 'max(0.7rem, env(safe-area-inset-top))',
};

const heroWashStyle = {
    background: 'linear-gradient(180deg, #071427 0%, #0a1c36 55%, #0c2140 100%)',
};

const mobileCtaPadStyle = {
    paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))',
};

const showDescription = computed(() => {
    const description = String(props.job.description || '').trim();
    if (!description) {
        return false;
    }
    const subject = String(props.job.subject || '').trim();
    return description !== subject;
});

const scrollToQuote = () => {
    quoteSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const openReviewPhoto = () => {
    const review = props.job.review;
    if (!review?.photo_url) {
        return;
    }
    const item = {
        url: review.photo_url,
        preview_url: review.photo_preview_url || review.photo_url,
        thumb_url: review.photo_thumb_url || review.photo_url,
        kind: 'image',
        original_name: 'Client photo of finished work',
    };
    lightboxItems.value = [item];
    lightboxIndex.value = 0;
    warmMediaItem(item);
    lightboxOpen.value = true;
};

onMounted(() => {
    if (showQuoteCta.value && window.location.hash === '#quote') {
        window.setTimeout(scrollToQuote, 120);
    }
});

const initials = computed(() => {
    const parts = String(props.profile.business_name || '')
        .trim()
        .split(/\s+/);
    if (parts.length >= 2) {
        return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
    }
    return (parts[0]?.[0] || 'I').toUpperCase();
});
</script>

<style scoped>
.job-fade {
    animation: job-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.job-fade-delay-1 {
    animation-delay: 70ms;
}

.job-fade-delay-2 {
    animation-delay: 140ms;
}

.job-fade-delay-3 {
    animation-delay: 200ms;
}

@keyframes job-rise {
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
    .job-fade {
        animation: none;
    }
}
</style>
