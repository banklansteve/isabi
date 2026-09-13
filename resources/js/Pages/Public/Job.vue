<template>
    <component :is="isLoggedIn ? AuthenticatedLayout : 'div'" :full-bleed="isLoggedIn || undefined">
        <div class="min-h-dvh bg-pale text-ink" :class="{ 'font-app': isLoggedIn }">
            <Head :title="`${job.description} · ${profile.business_name}`" />

            <header
                v-if="!isLoggedIn"
                class="sticky top-0 z-40 border-b border-ink/10 bg-white/90 shadow-nav backdrop-blur-xl"
            >
                <div
                    class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-5 py-3 sm:px-8"
                    style="padding-top: max(0.7rem, env(safe-area-inset-top))"
                >
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center gap-2.5 font-display text-[1.35rem] font-extrabold tracking-tight text-ink"
                    >
                        <BrandMark variant="mark" class="h-8 w-8 shrink-0" />
                        Kraftrack
                    </Link>
                    <Link
                        :href="profile.public_url"
                        class="tap-target inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-bold text-ink/60 ring-1 ring-ink/[0.06] transition-colors hover:text-ink"
                    >
                        View page
                    </Link>
                </div>
            </header>

            <main
                class="mx-auto max-w-5xl space-y-5 px-5 py-8 sm:space-y-6 sm:px-8 sm:py-10"
                :class="showQuoteCta ? 'pb-28 sm:pb-10' : ''"
            >
                <nav class="flex flex-wrap items-center gap-2 text-xs font-semibold text-ink/40">
                    <Link :href="route('public.directory')" class="transition-colors hover:text-ink">
                        Artisans
                    </Link>
                    <i class="ti ti-chevron-right text-[11px]" aria-hidden="true" />
                    <Link :href="profile.public_url" class="transition-colors hover:text-ink">
                        {{ profile.business_name }}
                    </Link>
                    <i class="ti ti-chevron-right text-[11px]" aria-hidden="true" />
                    <span class="text-ink/55">This job</span>
                </nav>

                <section
                    class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                >
                    <div class="flex items-center gap-3 border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                        <Link
                            :href="profile.public_url"
                            class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-tint text-sm font-extrabold text-deep"
                        >
                            <img
                                v-if="profile.logo_url || profile.avatar_url"
                                :src="profile.logo_url || profile.avatar_url"
                                :alt="profile.business_name"
                                class="h-full w-full object-cover"
                                :class="profile.logo_url ? 'object-contain p-1.5 bg-white' : ''"
                            />
                            <span v-else>{{ initials }}</span>
                        </Link>
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="profile.public_url"
                                class="block truncate text-sm font-bold text-ink hover:text-base-action"
                            >
                                {{ profile.business_name }}
                            </Link>
                            <p class="truncate text-xs font-medium text-ink/40">
                                {{ [profile.trade, profile.area_label].filter(Boolean).join(' · ') }}
                            </p>
                        </div>
                        <button
                            v-if="showQuoteCta"
                            type="button"
                            class="tap-target hidden shrink-0 items-center gap-1.5 rounded-xl bg-base-action px-3.5 py-2.5 text-xs font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover sm:inline-flex"
                            @click="scrollToQuote"
                        >
                            <i class="ti ti-message-quote text-sm" aria-hidden="true" />
                            Request a quote
                        </button>
                    </div>

                    <div class="px-5 py-5 sm:px-6 sm:py-6">
                        <p
                            v-if="job.category_label || job.job_category"
                            class="text-[11px] font-bold uppercase tracking-[0.16em] text-base"
                        >
                            {{ job.category_label || job.job_category }}
                        </p>
                        <h1
                            class="mt-2 font-editorial text-[1.7rem] font-semibold leading-[1.15] tracking-tight text-ink sm:text-[2.15rem]"
                        >
                            {{ job.description }}
                        </h1>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                v-if="job.worked_on_label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60"
                            >
                                <i class="ti ti-calendar-event text-sm text-ink/35" aria-hidden="true" />
                                <time :datetime="job.worked_on || undefined">{{ job.worked_on_label }}</time>
                            </span>
                            <span
                                v-if="job.reference"
                                class="inline-flex items-center gap-1.5 rounded-full bg-tint px-3 py-1.5 text-xs font-bold text-deep"
                            >
                                Ref {{ job.reference }}
                            </span>
                            <span
                                v-if="job.service_label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60"
                            >
                                <i class="ti ti-map-pin text-sm text-ink/35" aria-hidden="true" />
                                {{ job.service_label }}
                            </span>
                        </div>

                        <button
                            v-if="showQuoteCta"
                            type="button"
                            class="tap-target mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover sm:hidden"
                            @click="scrollToQuote"
                        >
                            <i class="ti ti-message-quote text-lg" aria-hidden="true" />
                            Request a quote
                        </button>
                    </div>
                </section>

                <section
                    v-if="job.media?.length"
                    class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                >
                    <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                        <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                            Photos &amp; video
                        </h2>
                    </div>
                    <div class="p-4 sm:p-5">
                        <MediaGallery :items="job.media" />
                    </div>
                </section>

                <section
                    v-if="job.review"
                    class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-emerald-200/70"
                >
                    <div
                        class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-emerald-100/80 bg-gradient-to-r from-emerald-50/80 to-white px-5 py-4 sm:px-6"
                    >
                        <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                            Client review
                        </h2>
                        <StarDisplay :rating="job.review.rating" class="ms-auto" />
                    </div>
                    <div class="px-5 py-5 sm:px-6 sm:py-6">
                        <p
                            v-if="job.review.comment"
                            class="font-editorial text-lg font-medium leading-relaxed text-ink/80 sm:text-xl"
                        >
                            “{{ job.review.comment }}”
                        </p>
                        <p class="mt-3 text-xs font-medium text-ink/40">
                            {{ job.review.client_display_name || 'Verified client' }}
                            <template v-if="job.review.submitted_at_label">
                                · {{ job.review.submitted_at_label }}
                            </template>
                        </p>
                    </div>
                </section>

                <p v-else class="px-1 text-sm font-medium text-ink/45">
                    This job is on {{ profile.business_name }}’s public page. A client review has not been left yet.
                </p>

                <section
                    v-if="showQuoteCta"
                    id="quote"
                    ref="quoteSection"
                    class="scroll-mt-24 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-base-action/15"
                >
                    <div class="border-b border-ink/[0.05] bg-gradient-to-r from-tint/80 to-white px-5 py-5 sm:px-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">Get a quote</p>
                        <h2 class="mt-1 font-editorial text-xl font-semibold tracking-tight text-ink">
                            Interested in similar work?
                        </h2>
                        <p class="mt-1 text-sm font-medium text-ink/45">
                            Leave your details — {{ profile.business_name }} will follow up directly.
                        </p>
                    </div>
                    <div class="px-5 py-5 sm:px-6 sm:py-6">
                        <QuoteRequestForm
                            :quote-url="quoteUrl"
                            :business-name="profile.business_name"
                        />
                    </div>
                </section>

                <Link
                    :href="profile.public_url"
                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    More work from {{ profile.business_name }}
                    <i class="ti ti-arrow-right" aria-hidden="true" />
                </Link>
            </main>

            <div
                v-if="showQuoteCta"
                class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/10 bg-white/95 px-4 py-3 backdrop-blur-md sm:hidden"
                style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
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
        </div>
    </component>
</template>

<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import MediaGallery from '@/Components/Media/MediaGallery.vue';
import QuoteRequestForm from '@/Components/Public/QuoteRequestForm.vue';
import StarDisplay from '@/Components/Reviews/StarDisplay.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

const showQuoteCta = computed(() => !props.viewerIsOwner && !!props.quoteUrl);

const quoteUrl = computed(
    () => props.quoteUrl || (props.job.reference
        ? route('public.job.quote', [props.profile.slug, props.job.reference])
        : ''),
);

const scrollToQuote = () => {
    quoteSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
