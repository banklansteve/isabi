<template>
    <component :is="isLoggedIn ? AuthenticatedLayout : 'div'" :full-bleed="isLoggedIn || undefined">
        <div class="job-page min-h-dvh bg-[#F1F3F6] text-ink" :class="{ 'font-app': isLoggedIn }">
            <Head :title="pageTitle" />

            <header
                v-if="!isLoggedIn"
                class="sticky top-0 z-40 border-b border-white/[0.06] bg-[#071427]/90 backdrop-blur-md"
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
                        class="tap-target inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white/90 ring-1 ring-white/15 transition-colors hover:bg-white/15 hover:text-white"
                    >
                        View page
                        <i class="ti ti-arrow-up-right text-sm" aria-hidden="true" />
                    </Link>
                </div>
            </header>

            <!-- Hero banner -->
            <section class="relative overflow-hidden bg-[#071427]">
                <div
                    class="pointer-events-none absolute inset-0"
                    aria-hidden="true"
                >
                    <div class="absolute inset-0 bg-gradient-to-b from-[#071427] via-[#0a1c36] to-[#0d2748]" />
                    <div
                        class="absolute inset-0 opacity-90"
                        style="
                            background:
                                radial-gradient(ellipse 75% 55% at 12% 20%, rgba(47, 111, 237, 0.28), transparent 58%),
                                radial-gradient(ellipse 55% 45% at 92% 8%, rgba(255, 106, 61, 0.16), transparent 52%),
                                radial-gradient(ellipse 50% 40% at 70% 90%, rgba(26, 79, 181, 0.18), transparent 55%);
                        "
                    />
                    <div
                        class="absolute inset-0 opacity-[0.18]"
                        style="
                            background-image: radial-gradient(rgba(255, 255, 255, 0.11) 0.7px, transparent 0.7px);
                            background-size: 18px 18px;
                        "
                    />
                    <div
                        class="absolute -right-16 top-10 font-display text-[7.5rem] font-extrabold leading-none tracking-tight text-white/[0.035] sm:text-[10rem]"
                    >
                        Work
                    </div>
                </div>

                <div
                    class="relative mx-auto max-w-6xl px-2.5 pb-14 pt-5 sm:px-6 sm:pb-16 sm:pt-7 lg:px-8"
                >
                    <nav
                        class="mb-7 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-white/40"
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

                    <div class="w-full">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-coral-tint/95 ring-1 ring-white/10"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                                Completed work
                            </span>
                            <span
                                v-if="job.category_label || job.job_category"
                                class="inline-flex items-center rounded-full bg-white/8 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-white/65 ring-1 ring-white/10"
                            >
                                {{ job.category_label || job.job_category }}
                            </span>
                        </div>

                        <h1
                            class="mt-4 w-full font-editorial text-[clamp(1.85rem,4.6vw,3rem)] font-semibold leading-[1.1] tracking-tight text-white"
                        >
                            {{ jobTitle }}
                        </h1>

                        <div
                            v-if="job.worked_on_label || job.service_label"
                            class="mt-5 flex w-full flex-wrap gap-2"
                        >
                            <span
                                v-if="job.worked_on_label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/80 ring-1 ring-white/12 backdrop-blur-sm"
                            >
                                <i class="ti ti-calendar-event text-sm text-coral-tint/90" aria-hidden="true" />
                                <time :datetime="job.worked_on || undefined">{{
                                    job.worked_on_label
                                }}</time>
                            </span>
                            <span
                                v-if="job.service_label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/80 ring-1 ring-white/12 backdrop-blur-sm"
                            >
                                <i class="ti ti-map-pin text-sm text-coral-tint/90" aria-hidden="true" />
                                {{ job.service_label }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-9 flex w-full flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6"
                    >
                        <Link
                            :href="profile.public_url"
                            class="group flex min-w-0 items-center gap-3.5 transition-opacity hover:opacity-95"
                        >
                            <span
                                class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-deep to-ink text-sm font-extrabold text-white shadow-[0_16px_40px_-16px_rgba(0,0,0,0.65)] ring-2 ring-white/20 sm:h-14 sm:w-14"
                            >
                                <span
                                    class="pointer-events-none absolute -inset-1 rounded-[1.15rem] bg-gradient-to-br from-base/35 via-transparent to-coral/25 opacity-80 blur-[2px]"
                                    aria-hidden="true"
                                />
                                <img
                                    v-if="profile.logo_url || profile.avatar_url"
                                    :src="profile.logo_url || profile.avatar_url"
                                    :alt="profile.business_name"
                                    class="relative h-full w-full object-cover"
                                    :class="profile.logo_url ? 'bg-white object-contain p-1.5' : ''"
                                />
                                <span v-else class="relative font-display">{{ initials }}</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-white/40">
                                    Crafted by
                                </span>
                                <span
                                    class="mt-0.5 block truncate text-sm font-bold text-white transition-colors group-hover:text-coral-tint sm:text-[15px]"
                                >
                                    {{ profile.business_name }}
                                </span>
                                <span
                                    v-if="artisanMeta"
                                    class="mt-0.5 block truncate text-xs font-medium text-white/45"
                                >
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
                class="relative z-[1] -mt-6 rounded-t-[1.5rem] bg-[#F1F3F6] pt-7 sm:-mt-8 sm:rounded-t-[1.85rem] sm:pt-10"
                :class="showQuoteCta ? 'pb-28 sm:pb-20' : 'pb-16 sm:pb-20'"
            >
                <div class="mx-auto max-w-6xl space-y-6 px-2.5 sm:space-y-8 sm:px-6 lg:px-8">
                    <!-- About -->
                    <section
                        v-if="showDescription"
                        class="job-fade overflow-hidden rounded-[1.35rem] bg-white shadow-[0_20px_55px_-30px_rgba(15,23,42,0.38)] ring-1 ring-zinc-200/70 sm:rounded-[1.5rem]"
                    >
                        <div class="px-5 py-6 sm:px-8 sm:py-8">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-tint text-base-action ring-1 ring-base-action/15"
                                >
                                    <i class="ti ti-file-description text-lg" aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 class="font-editorial text-lg font-semibold tracking-tight text-zinc-900 sm:text-xl">
                                        About this job
                                    </h2>
                                    <p class="mt-0.5 text-xs font-medium text-zinc-500">
                                        What was delivered on this project
                                    </p>
                                </div>
                            </div>
                            <p
                                class="job-about-copy mt-5 w-full text-[15px] font-medium leading-[1.75] sm:text-[1rem] sm:leading-[1.8]"
                            >
                                {{ job.description }}
                            </p>

                            <button
                                v-if="showQuoteCta"
                                type="button"
                                class="tap-target mt-7 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover sm:hidden"
                                @click="scrollToQuote"
                            >
                                <i class="ti ti-message-quote text-lg" aria-hidden="true" />
                                Request a quote
                            </button>
                        </div>
                    </section>

                    <!-- Fallback when no separate description — keep mobile quote CTA reachable -->
                    <div
                        v-else-if="showQuoteCta"
                        class="job-fade sm:hidden"
                    >
                        <button
                            type="button"
                            class="tap-target inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                            @click="scrollToQuote"
                        >
                            <i class="ti ti-message-quote text-lg" aria-hidden="true" />
                            Request a quote
                        </button>
                    </div>

                    <!-- Finished work gallery -->
                    <section
                        v-if="job.media?.length"
                        class="job-fade job-fade-delay-1 overflow-hidden rounded-[1.35rem] bg-white shadow-[0_20px_55px_-30px_rgba(15,23,42,0.38)] ring-1 ring-zinc-200/70 sm:rounded-[1.5rem]"
                    >
                        <div class="flex items-end justify-between gap-3 border-b border-zinc-100/90 px-5 py-5 sm:px-8 sm:py-6">
                            <div>
                                <h2
                                    class="font-editorial text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl"
                                >
                                    Finished work
                                </h2>
                                <p class="mt-1 text-sm font-medium text-zinc-500">
                                    Photos and video from this job — tap to enlarge
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full bg-zinc-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.1em] text-zinc-600 ring-1 ring-zinc-200/80"
                            >
                                {{ job.media.length }}
                                {{ job.media.length === 1 ? 'item' : 'items' }}
                            </span>
                        </div>

                        <div class="p-3 sm:p-5">
                            <MediaGallery
                                :items="job.media"
                                layout="masonry"
                            />
                        </div>
                    </section>

                    <!-- Client review -->
                    <section
                        v-if="job.review"
                        class="job-fade job-fade-delay-2 overflow-hidden rounded-[1.35rem] bg-white shadow-[0_20px_55px_-30px_rgba(15,23,42,0.38)] ring-1 ring-emerald-200/50 sm:rounded-[1.5rem]"
                    >
                        <div
                            class="flex flex-wrap items-center gap-x-4 gap-y-3 border-b border-emerald-100/80 bg-gradient-to-r from-emerald-50/95 via-white to-white px-5 py-5 sm:px-8 sm:py-6"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-700/75">
                                    Client review
                                </p>
                                <p class="mt-1 text-sm font-bold text-zinc-900">
                                    {{ job.review.client_display_name || 'Verified client' }}
                                </p>
                                <p
                                    v-if="job.review.submitted_at_label"
                                    class="mt-0.5 text-xs font-medium text-zinc-500"
                                >
                                    {{ job.review.submitted_at_label }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2 rounded-2xl bg-white px-3 py-2 shadow-sm ring-1 ring-emerald-100">
                                <StarDisplay :rating="job.review.rating" size="md" />
                                <span class="text-sm font-extrabold tabular-nums tracking-tight text-zinc-900">
                                    {{ Number(job.review.rating).toFixed(1) }}
                                </span>
                            </div>
                        </div>

                        <div class="px-5 py-6 sm:px-8 sm:py-8">
                            <p
                                v-if="job.review.comment"
                                class="w-full font-editorial text-[1.05rem] font-medium leading-relaxed text-[#3F4654] sm:text-[1.125rem] sm:leading-relaxed"
                            >
                                &ldquo;{{ job.review.comment }}&rdquo;
                            </p>
                            <p v-else class="text-sm font-medium text-zinc-600">
                                Verified client left a rating for this job.
                            </p>

                            <button
                                v-if="job.review.photo_url"
                                type="button"
                                class="group/photo mt-5 block w-full max-w-sm overflow-hidden rounded-2xl ring-1 ring-zinc-200/80 transition duration-300 hover:ring-base-action/35 sm:max-w-md"
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

                    <div
                        v-else
                        class="job-fade job-fade-delay-2 rounded-[1.25rem] bg-white px-5 py-5 text-sm font-medium text-zinc-600 shadow-[0_14px_42px_-26px_rgba(15,23,42,0.3)] ring-1 ring-zinc-200/70 sm:px-6"
                    >
                        This job is on {{ profile.business_name }}&rsquo;s public page. A client
                        review has not been left yet.
                    </div>

                    <!-- Quote -->
                    <section
                        v-if="showQuoteCta"
                        id="quote"
                        ref="quoteSection"
                        class="job-fade job-fade-delay-3 scroll-mt-24 overflow-hidden rounded-[1.35rem] bg-white shadow-[0_20px_55px_-30px_rgba(15,23,42,0.38)] ring-1 ring-base-action/15 sm:rounded-[1.5rem]"
                    >
                        <div
                            class="border-b border-zinc-100 bg-gradient-to-r from-tint/95 via-white to-white px-5 py-5 sm:px-8 sm:py-6"
                        >
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action"
                            >
                                Get a quote
                            </p>
                            <h2
                                class="mt-1.5 font-editorial text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl"
                            >
                                Interested in similar work?
                            </h2>
                            <p class="mt-1.5 max-w-xl text-sm font-medium leading-relaxed text-zinc-600">
                                Leave your details — {{ profile.business_name }} will follow up
                                directly.
                            </p>
                        </div>
                        <div class="px-5 py-5 sm:px-8 sm:py-6">
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
                class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200/80 bg-white/95 px-3 py-3 backdrop-blur-md sm:hidden"
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
.job-about-copy {
    color: #3f4654;
}

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
