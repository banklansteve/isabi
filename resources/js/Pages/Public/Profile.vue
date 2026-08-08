<template>
    <div class="min-h-dvh bg-pale text-ink">
        <Head :title="`${profile.business_name} · ${profile.trade || 'Artisan'} · Isabi`" />

        <!-- Compact public chrome -->
        <header
            class="sticky top-0 z-40 border-b border-ink/10 bg-white/95 shadow-nav backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-5 py-3 sm:px-8"
                style="padding-top: max(0.75rem, env(safe-area-inset-top))"
            >
                <Link
                    :href="route('home')"
                    class="font-display text-[1.35rem] font-extrabold tracking-tight text-ink"
                >
                    Isabi
                </Link>
                <a
                    v-if="profile.whatsapp_url"
                    :href="profile.whatsapp_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tap-target inline-flex items-center gap-1.5 rounded-full bg-[#25D366]/10 px-3 py-1.5 text-xs font-bold text-[#128C7E] ring-1 ring-[#25D366]/25"
                >
                    <i class="ti ti-brand-whatsapp text-sm" aria-hidden="true" />
                    WhatsApp
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-5 pb-28 pt-8 sm:px-8 sm:pt-10">
            <!-- Identity -->
            <section class="relative overflow-hidden rounded-xl bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-base/35 to-transparent"
                    aria-hidden="true"
                />
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <div
                        class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-tint text-2xl font-bold text-deep ring-1 ring-ink/[0.06] sm:h-24 sm:w-24"
                    >
                        <img
                            v-if="profile.avatar_url"
                            :src="profile.avatar_url"
                            :alt="profile.business_name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ initials }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.15rem]">
                            {{ profile.business_name }}
                        </h1>
                        <p v-if="profile.trade" class="mt-1 text-base font-semibold text-deep">
                            {{ profile.trade }}
                        </p>
                        <p v-if="profile.area_label" class="mt-2 flex items-center gap-1.5 text-sm font-medium text-ink/50">
                            <i class="ti ti-map-pin text-base-action" aria-hidden="true" />
                            {{ profile.area_label }}
                        </p>
                        <p v-if="profile.bio" class="mt-4 text-sm font-medium leading-relaxed text-ink/60">
                            {{ profile.bio }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <span
                                v-if="profile.avg_rating"
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 ring-1 ring-amber-200/80"
                            >
                                <i class="ti ti-star-filled text-amber-500" aria-hidden="true" />
                                {{ profile.avg_rating }}
                                <span class="font-medium text-amber-700/70">
                                    · {{ profile.review_count }} review{{ profile.review_count === 1 ? '' : 's' }}
                                </span>
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3 py-1.5 text-xs font-semibold text-ink/55"
                            >
                                <i class="ti ti-briefcase text-base-action" aria-hidden="true" />
                                {{ profile.jobs_count }} job{{ profile.jobs_count === 1 ? '' : 's' }} logged
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Timeline -->
            <section class="mt-8" aria-labelledby="timeline-heading">
                <h2 id="timeline-heading" class="text-lg font-bold tracking-tight text-ink">
                    Work log
                </h2>
                <p class="mt-1 text-sm font-medium text-ink/45">
                    Dated jobs with client reviews where available.
                </p>

                <ol v-if="timeline.length" class="mt-5 space-y-3">
                    <li
                        v-for="job in timeline"
                        :key="job.uid"
                        class="rounded-xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-if="job.category_label || job.job_category"
                                class="rounded-full bg-tint px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.1em] text-deep"
                            >
                                {{ job.category_label || job.job_category }}
                            </span>
                            <span class="text-xs font-semibold text-ink/40">
                                {{ job.worked_on_label }}
                            </span>
                        </div>
                        <p class="mt-3 text-base font-semibold tracking-tight text-ink">
                            {{ job.description }}
                        </p>
                        <p v-if="job.service_label" class="mt-1.5 text-xs font-medium text-ink/45">
                            <i class="ti ti-map-pin mr-0.5" aria-hidden="true" />
                            {{ job.service_label }}
                        </p>

                        <MediaGallery
                            v-if="job.media?.length"
                            class="mt-4"
                            :items="job.media"
                            grid-class="grid-cols-3 gap-2 sm:grid-cols-4"
                        />

                        <!-- Testimonial beside this job -->
                        <div
                            v-if="job.review"
                            class="mt-4 rounded-xl border border-base/15 bg-gradient-to-br from-tint/50 to-pale/80 p-4"
                        >
                            <div class="flex items-center gap-2">
                                <div class="flex gap-0.5 text-amber-500">
                                    <i
                                        v-for="n in 5"
                                        :key="n"
                                        class="ti text-sm"
                                        :class="n <= job.review.rating ? 'ti-star-filled' : 'ti-star text-ink/15'"
                                        aria-hidden="true"
                                    />
                                </div>
                                <span
                                    v-if="job.review.client_display_name"
                                    class="text-xs font-semibold text-ink/50"
                                >
                                    {{ job.review.client_display_name }}
                                </span>
                                <span
                                    v-if="job.review.submitted_at_label"
                                    class="ml-auto text-[11px] font-medium text-ink/35"
                                >
                                    {{ job.review.submitted_at_label }}
                                </span>
                            </div>
                            <p
                                v-if="job.review.comment"
                                class="mt-2 text-sm font-medium leading-relaxed text-ink/70"
                            >
                                “{{ job.review.comment }}”
                            </p>
                            <p
                                v-if="job.review.referred_by"
                                class="mt-2 text-xs font-semibold text-deep"
                            >
                                <i class="ti ti-users mr-1" aria-hidden="true" />
                                Heard about them via {{ job.review.referred_by }}
                            </p>
                            <button
                                v-if="job.review.photo_url"
                                type="button"
                                class="mt-3 block overflow-hidden rounded-lg ring-1 ring-ink/[0.06] transition hover:ring-base/30"
                                aria-label="View client photo"
                                @click="openReviewPhoto(job.review.photo_url)"
                            >
                                <img
                                    :src="job.review.photo_url"
                                    alt="Client photo of finished work"
                                    class="max-h-48 w-full object-cover"
                                />
                            </button>
                        </div>
                    </li>
                </ol>

                <div
                    v-else
                    class="mt-5 rounded-xl border border-dashed border-ink/10 bg-white px-5 py-12 text-center"
                >
                    <i class="ti ti-notebook text-2xl text-ink/25" aria-hidden="true" />
                    <p class="mt-2 text-sm font-semibold text-ink/50">No jobs published yet</p>
                </div>

                <MediaLightbox
                    v-model:show="reviewLightboxOpen"
                    :items="reviewLightboxItems"
                    :start-index="0"
                />
            </section>
        </main>

        <!-- Sticky contact -->
        <div
            v-if="profile.whatsapp_url"
            class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/10 bg-white/95 px-5 py-3 backdrop-blur-md"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
        >
            <div class="mx-auto max-w-3xl">
                <a
                    :href="profile.whatsapp_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(37,211,102,0.55)] transition-opacity hover:opacity-95"
                >
                    <i class="ti ti-brand-whatsapp text-lg" aria-hidden="true" />
                    Contact via WhatsApp
                </a>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>

<script setup>
import MediaGallery from '@/Components/Media/MediaGallery.vue';
import MediaLightbox from '@/Components/Media/MediaLightbox.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    timeline: { type: Array, default: () => [] },
});

const reviewLightboxOpen = ref(false);
const reviewLightboxItems = ref([]);

const openReviewPhoto = (url) => {
    reviewLightboxItems.value = [
        { url, kind: 'image', original_name: 'Client photo of finished work' },
    ];
    reviewLightboxOpen.value = true;
};

const initials = computed(() => {
    const parts = String(props.profile.business_name || 'A').trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return (parts[0]?.[0] || 'A').toUpperCase();
});
</script>
