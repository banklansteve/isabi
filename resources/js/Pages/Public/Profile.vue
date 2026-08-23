<template>
    <component :is="isLoggedIn ? AuthenticatedLayout : 'div'" :full-bleed="isLoggedIn || undefined">
    <div class="min-h-dvh bg-pale text-ink" :class="{ 'font-app': isLoggedIn }">
        <Head :title="`${profile.business_name} · ${profile.trade || 'Artisan'}`" />

        <!-- Guest chrome only — signed-in users rely on AuthenticatedLayout. -->
        <header
            v-if="!isLoggedIn"
            ref="headerRef"
            class="fixed inset-x-0 top-0 z-40 bg-[#071427]"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-3 py-3 sm:gap-4 sm:px-8 sm:py-3.5"
                style="padding-top: max(0.7rem, env(safe-area-inset-top))"
            >
                <Link
                    :href="route('home')"
                    class="font-display text-[1.35rem] font-extrabold tracking-tight text-white"
                >
                    Isabi
                </Link>

                <button
                    v-if="profile.whatsapp_url"
                    type="button"
                    class="tap-target inline-flex items-center gap-1.5 rounded-full bg-[#25D366] px-3 py-1.5 text-xs font-bold text-white shadow-[0_8px_24px_-10px_rgba(37,211,102,0.8)]"
                    @click="openWhatsAppChat"
                >
                    <i class="ti ti-brand-whatsapp text-sm" aria-hidden="true" />
                    Log in to chat
                </button>
            </div>
        </header>

        <!-- Hero identity — solid navy under the bar, soft wash only below it -->
        <section class="relative overflow-hidden bg-[#071427]">
            <div
                class="pointer-events-none absolute inset-0"
                style="background:
                    linear-gradient(180deg, #071427 0%, #071427 140px, #0a1c36 48%, #0c2140 100%);"
                aria-hidden="true"
            />
            <!-- Decorative washes start well below the nav so the top strip stays one flat navy -->
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 top-28 bg-[radial-gradient(ellipse_80%_60%_at_15%_30%,rgba(47,111,237,0.26),transparent_58%),radial-gradient(ellipse_70%_50%_at_90%_40%,rgba(255,106,61,0.14),transparent_52%)]"
                aria-hidden="true"
            />

            <div class="relative mx-auto max-w-7xl px-3 pb-14 pt-4 sm:px-8 sm:pb-28 sm:pt-8">
                <!-- Clears the fixed guest bar only -->
                <div
                    v-if="!isLoggedIn"
                    :style="{ height: `${headerHeight}px` }"
                    aria-hidden="true"
                />

                <div class="profile-hero flex flex-col items-center text-center lg:flex-row lg:items-end lg:gap-10 lg:text-left">
                    <div class="relative shrink-0">
                        <div
                            class="absolute -inset-3 rounded-[2rem] bg-gradient-to-br from-base/40 via-transparent to-coral/30 blur-md"
                            aria-hidden="true"
                        />
                        <div
                            class="relative flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-deep to-ink text-3xl font-extrabold tracking-tight text-white shadow-premium-ink ring-2 ring-white/20 sm:h-36 sm:w-36 sm:rounded-[1.75rem] sm:text-4xl lg:h-40 lg:w-40"
                        >
                            <img
                                v-if="profile.avatar_url"
                                :src="profile.avatar_url"
                                :alt="profile.business_name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="font-display">{{ initials }}</span>
                        </div>
                    </div>

                    <div class="mt-7 min-w-0 flex-1 lg:mt-0">
                        <p
                            v-if="profile.trade"
                            class="text-[11px] font-bold uppercase tracking-[0.22em] text-coral-tint/90"
                        >
                            {{ profile.trade }}
                        </p>
                        <h1
                            class="mt-2 font-editorial text-[clamp(2.1rem,5vw,3.6rem)] font-semibold leading-[1.05] tracking-tight text-white"
                        >
                            {{ profile.business_name }}
                        </h1>
                        <p
                            v-if="profile.area_label"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-white/55"
                        >
                            <i class="ti ti-map-pin text-coral" aria-hidden="true" />
                            {{ profile.area_label }}
                        </p>
                        <p
                            v-if="profile.bio"
                            class="mx-auto mt-4 max-w-2xl text-sm font-medium leading-relaxed text-white/70 lg:mx-0 sm:text-[15px]"
                        >
                            {{ profile.bio }}
                        </p>

                        <div
                            v-if="profile.skills?.length"
                            class="mt-5 flex flex-wrap justify-center gap-2 lg:justify-start"
                        >
                            <span
                                v-for="skill in profile.skills"
                                :key="skill"
                                class="rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-bold tracking-wide text-white ring-1 ring-white/15 backdrop-blur-sm"
                            >
                                {{ skill }}
                            </span>
                        </div>

                        <p
                            v-if="profile.coverage_summary"
                            class="mx-auto mt-4 flex max-w-2xl items-start gap-2 text-sm font-medium leading-relaxed text-white/55 lg:mx-0"
                        >
                            <i class="ti ti-route mt-0.5 shrink-0 text-base text-coral" aria-hidden="true" />
                            <span>
                                <span class="font-bold text-white/70">Usually works within</span>
                                {{ profile.coverage_summary }}
                            </span>
                        </p>

                        <div
                            class="mt-6 flex flex-wrap items-center justify-center gap-2 sm:mt-7 sm:gap-3 lg:justify-start"
                        >
                            <div
                                class="inline-flex items-center gap-2.5 rounded-xl bg-white/10 px-3.5 py-2.5 ring-1 ring-white/15 sm:rounded-2xl sm:px-4"
                            >
                                <StarDisplay
                                    :rating="profile.avg_rating || 0"
                                    empty-class="text-white/30"
                                />
                                <div class="text-left">
                                    <p class="text-sm font-extrabold tabular-nums text-white">
                                        {{ ratingLabel }}
                                    </p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-white/45">
                                        {{ profile.review_count || 0 }}
                                        review{{ profile.review_count === 1 ? '' : 's' }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="inline-flex items-center gap-2.5 rounded-xl bg-white/10 px-3.5 py-2.5 ring-1 ring-white/15 sm:rounded-2xl sm:px-4"
                            >
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-coral/20 text-coral"
                                >
                                    <i class="ti ti-rosette-discount-check text-lg" aria-hidden="true" />
                                </span>
                                <div class="text-left">
                                    <p class="text-sm font-extrabold tabular-nums text-white">
                                        {{ profile.verified_works || 0 }}
                                    </p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-white/45">
                                        Verified work{{ profile.verified_works === 1 ? '' : 's' }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="inline-flex items-center gap-2.5 rounded-xl bg-white/10 px-3.5 py-2.5 ring-1 ring-white/15 sm:rounded-2xl sm:px-4"
                            >
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-base/25 text-[#9db7ff]"
                                >
                                    <i class="ti ti-briefcase text-lg" aria-hidden="true" />
                                </span>
                                <div class="text-left">
                                    <p class="text-sm font-extrabold tabular-nums text-white">
                                        {{ profile.jobs_count || 0 }}
                                    </p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-white/45">
                                        Jobs logged
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="profile.years_active !== null && profile.years_active !== undefined"
                                class="inline-flex items-center gap-2.5 rounded-xl bg-white/10 px-3.5 py-2.5 ring-1 ring-white/15 sm:rounded-2xl sm:px-4"
                            >
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white/70"
                                >
                                    <i class="ti ti-hourglass text-lg" aria-hidden="true" />
                                </span>
                                <div class="text-left">
                                    <p class="text-sm font-extrabold tabular-nums text-white">
                                        {{ profile.years_active }}
                                    </p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-white/45">
                                        Year{{ profile.years_active === 1 ? '' : 's' }} active
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <main
            class="relative z-[1] -mt-7 rounded-t-xl bg-pale pt-6 sm:-mt-14 sm:rounded-t-[1.5rem] sm:pt-14"
            :class="showWhatsAppBar ? (isLoggedIn ? 'pb-36 md:pb-28' : 'pb-28') : 'pb-16'"
        >
            <div class="mx-auto max-w-7xl px-2.5 sm:px-6 lg:px-8">
                <section
                    v-if="showTrustStrip"
                    class="mb-4 grid gap-2.5 sm:mb-10 sm:gap-4 lg:grid-cols-[1.6fr_1fr]"
                    aria-labelledby="trust-heading"
                >
                    <h2 id="trust-heading" class="sr-only">Credentials and transparency</h2>

                    <!-- Self-declared credentials -->
                    <div
                        v-if="profile.credentials?.length"
                        class="rounded-xl bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.05] sm:rounded-2xl sm:p-6"
                    >
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="font-editorial text-xl font-semibold tracking-tight text-ink">
                                Credentials
                            </h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.1em] text-amber-800 ring-1 ring-amber-200/70"
                            >
                                <i class="ti ti-alert-triangle text-[11px]" aria-hidden="true" />
                                Self-declared
                            </span>
                        </div>
                        <p class="mt-1.5 text-xs font-medium leading-relaxed text-ink/45">
                            Provided by the artisan and not verified by Isabi. Ask to see the
                            original before you hire.
                        </p>

                        <ul class="mt-4 grid gap-2.5 sm:grid-cols-2">
                            <li
                                v-for="(credential, i) in profile.credentials"
                                :key="`${credential.title}-${i}`"
                                class="rounded-xl bg-pale px-3.5 py-3 ring-1 ring-ink/[0.05]"
                            >
                                <p class="flex items-start gap-2 text-[13px] font-bold leading-snug text-ink">
                                    <i
                                        class="ti ti-certificate mt-0.5 shrink-0 text-sm text-deep"
                                        aria-hidden="true"
                                    />
                                    {{ credential.title }}
                                </p>
                                <p
                                    v-if="credential.issuer"
                                    class="mt-1 ps-6 text-[11px] font-medium text-ink/45"
                                >
                                    {{ credential.issuer }}
                                </p>
                                <p
                                    v-if="credential.reference || credential.year"
                                    class="mt-0.5 ps-6 text-[11px] font-semibold tabular-nums text-ink/35"
                                >
                                    {{ [credential.reference, credential.year].filter(Boolean).join(' · ') }}
                                </p>
                            </li>
                        </ul>
                    </div>

                    <!-- Transparency -->
                    <div
                        v-if="transparencyStats.length"
                        class="rounded-xl bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.05] sm:rounded-2xl sm:p-6"
                    >
                        <h3 class="font-editorial text-xl font-semibold tracking-tight text-ink">
                            Track record
                        </h3>
                        <p class="mt-1.5 text-xs font-medium leading-relaxed text-ink/45">
                            Measured from this artisan’s own logged jobs.
                        </p>

                        <dl class="mt-4 space-y-3">
                            <div
                                v-for="stat in transparencyStats"
                                :key="stat.label"
                                class="flex items-baseline justify-between gap-3 border-b border-ink/[0.05] pb-3 last:border-0 last:pb-0"
                            >
                                <dt class="text-xs font-semibold leading-snug text-ink/50">
                                    {{ stat.label }}
                                </dt>
                                <dd class="shrink-0 font-editorial text-lg font-semibold tabular-nums text-ink">
                                    {{ stat.value }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section aria-labelledby="work-heading">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-coral-deep">
                                Portfolio
                            </p>
                            <h2
                                id="work-heading"
                                class="mt-2 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-ink sm:text-[2.5rem]"
                            >
                                Recent work
                            </h2>
                            <p class="mt-2 max-w-xl text-[15px] font-normal leading-relaxed text-ink/50">
                                Real jobs, dated proof, and client reviews you can trust.
                            </p>
                        </div>
                    </div>

                    <div v-if="timeline.length > 1" class="mt-4 sm:mt-6">
                        <JobLogControls
                            v-model:search="search"
                            v-model:sort="sort"
                            v-model:category="category"
                            :categories="categoryFacets"
                            :sort-options="sortOptions"
                            :result-count="filteredJobs.length"
                            :total-count="timeline.length"
                            :has-active-filters="hasActiveFilters"
                            @clear="clearFilters"
                        />
                    </div>

                    <ol v-if="visibleJobs.length" class="mt-3 space-y-2.5 sm:mt-6 sm:space-y-4 lg:space-y-5">
                        <li
                            v-for="(job, index) in visibleJobs"
                            :id="`job-${job.uid}`"
                            :key="job.uid"
                            class="profile-job group/card scroll-mt-24 overflow-hidden rounded-xl bg-white shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-500 hover:shadow-premium-hover sm:rounded-2xl"
                            :style="{ animationDelay: `${Math.min(index, 5) * 45}ms` }"
                        >
                            <div
                                class="grid"
                                :class="[
                                    job.media?.length
                                        ? job.review
                                            ? 'lg:min-h-[23rem] lg:grid-cols-[1.28fr_1fr]'
                                            : 'lg:min-h-[18rem] lg:grid-cols-[1.6fr_1fr]'
                                        : 'lg:grid-cols-1',
                                ]"
                            >
                                <!-- Media mosaic — fills the exact height of the detail column -->
                                <div
                                    v-if="job.media?.length"
                                    class="relative aspect-[4/3] bg-ink/[0.04] sm:aspect-[16/10] lg:aspect-auto"
                                >
                                    <div class="absolute inset-0">
                                        <JobMediaMosaic
                                            :items="job.media"
                                            @open="openJobMedia(job, $event)"
                                        />
                                    </div>
                                </div>

                                <!-- Detail + review -->
                                <div
                                    class="flex min-w-0 flex-col p-4 sm:p-8"
                                    :class="job.media?.length ? '' : 'lg:max-w-3xl'"
                                >
                                    <div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                            <span
                                                v-if="job.category_label || job.job_category"
                                                class="rounded-full bg-tint px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-deep"
                                            >
                                                {{ job.category_label || job.job_category }}
                                            </span>
                                            <time
                                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink/40"
                                                :datetime="job.worked_on || undefined"
                                            >
                                                <i class="ti ti-calendar-event text-[13px]" aria-hidden="true" />
                                                {{ job.worked_on_label }}
                                            </time>

                                            <button
                                                type="button"
                                                class="relative z-[2] tap-target ms-auto inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-ink/40 transition-colors hover:bg-pale hover:text-deep"
                                                :aria-label="`Share ${job.description}`"
                                                @click.stop="openShare(job)"
                                            >
                                                <i class="ti ti-share-2 text-sm" aria-hidden="true" />
                                                Share
                                            </button>
                                        </div>

                                        <h3
                                            class="mt-4 font-editorial text-[1.6rem] font-semibold leading-[1.2] tracking-tight text-ink sm:text-[1.9rem]"
                                        >
                                            <Link
                                                v-if="jobHref(job)"
                                                :href="jobHref(job)"
                                                class="transition-colors hover:text-base-action hover:underline hover:decoration-base-action/40 hover:underline-offset-[6px]"
                                            >
                                                {{ job.description }}
                                            </Link>
                                            <template v-else>
                                                {{ job.description }}
                                            </template>
                                        </h3>

                                        <p
                                            v-if="job.service_label"
                                            class="mt-3 inline-flex items-center gap-1.5 text-[13px] font-medium text-ink/45"
                                        >
                                            <i class="ti ti-map-pin text-base-action" aria-hidden="true" />
                                            {{ job.service_label }}
                                        </p>
                                    </div>

                                    <!-- Client review — anchored to the bottom of the column -->
                                    <div class="pt-6 lg:mt-auto">
                                        <figure
                                            v-if="job.review"
                                            class="rounded-xl bg-pale p-4 ring-1 ring-ink/[0.05] sm:rounded-2xl sm:p-6"
                                        >
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                                <StarDisplay :rating="job.review.rating" size="md" />
                                                <span
                                                    v-if="job.review.would_recommend === true"
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.1em] text-emerald-700"
                                                >
                                                    <i class="ti ti-thumb-up text-[11px]" aria-hidden="true" />
                                                    Recommends
                                                </span>
                                            </div>

                                            <template v-if="job.review.comment">
                                                <blockquote
                                                    class="mt-3.5 font-editorial text-[1.05rem] font-normal leading-[1.6] text-ink/75"
                                                    :class="isReviewClamped(job) ? 'line-clamp-6' : ''"
                                                >
                                                    “{{ job.review.comment }}”
                                                </blockquote>
                                                <button
                                                    v-if="isLongReview(job)"
                                                    type="button"
                                                    class="mt-2 text-xs font-bold text-base-action transition-colors hover:text-base-hover"
                                                    @click="toggleReview(job)"
                                                >
                                                    {{ isReviewClamped(job) ? 'Read full review' : 'Show less' }}
                                                </button>
                                            </template>

                                            <figcaption
                                                class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs"
                                            >
                                                <span
                                                    v-if="job.review.client_display_name"
                                                    class="font-bold text-ink/70"
                                                >
                                                    {{ job.review.client_display_name }}
                                                </span>
                                                <span v-else class="font-bold text-ink/50">
                                                    Verified client
                                                </span>
                                                <span
                                                    v-if="job.review.submitted_at_label"
                                                    class="font-medium text-ink/35"
                                                >
                                                    · {{ job.review.submitted_at_label }}
                                                </span>
                                            </figcaption>

                                            <p
                                                v-if="job.review.referred_by"
                                                class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold text-deep"
                                            >
                                                <i class="ti ti-users text-[13px]" aria-hidden="true" />
                                                Heard about them via {{ job.review.referred_by }}
                                            </p>

                                            <button
                                                v-if="job.review.photo_url"
                                                type="button"
                                                class="group/photo mt-4 block w-full overflow-hidden rounded-xl ring-1 ring-ink/[0.06] transition duration-300 hover:ring-base/40"
                                                aria-label="View client photo"
                                                @click="openReviewPhoto(job.review)"
                                            >
                                                <img
                                                    :src="job.review.photo_thumb_url || job.review.photo_url"
                                                    alt="Client photo of finished work"
                                                    loading="lazy"
                                                    class="h-36 w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover/photo:scale-[1.03]"
                                                />
                                            </button>
                                        </figure>

                                        <p
                                            v-else
                                            class="inline-flex items-center gap-2 rounded-full bg-pale px-3.5 py-2 text-xs font-semibold text-ink/35 ring-1 ring-ink/[0.05]"
                                        >
                                            <i class="ti ti-hourglass-low text-sm" aria-hidden="true" />
                                            Awaiting client review
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ol>

                    <div
                        v-if="canLoadMore"
                        ref="loadMoreSentinel"
                        class="flex items-center justify-center gap-2 py-5 text-xs font-semibold text-ink/35"
                        aria-hidden="true"
                    >
                        <i class="ti ti-loader-2 animate-spin text-sm" />
                        Loading more jobs…
                    </div>

                    <div v-else-if="!visibleJobs.length" class="mt-3 sm:mt-6">
                        <AppEmptyState
                            :icon="hasActiveFilters ? 'ti ti-search-off' : 'ti ti-briefcase'"
                            :title="
                                hasActiveFilters
                                    ? 'No jobs match that search'
                                    : 'No jobs published yet'
                            "
                            :description="
                                hasActiveFilters
                                    ? 'Try a different keyword or clear the filters.'
                                    : viewerIsOwner
                                      ? 'Log a finished job and it will show here for clients to see.'
                                      : 'This artisan hasn’t published finished work yet — check back soon.'
                            "
                            :cta-label="
                                hasActiveFilters
                                    ? 'Clear filters'
                                    : viewerIsOwner
                                      ? 'Log a job'
                                      : ''
                            "
                            :cta-href="
                                !hasActiveFilters && viewerIsOwner
                                    ? route('work-log.create')
                                    : ''
                            "
                            @action="clearFilters"
                        />
                    </div>

                    <div
                        v-if="viewerIsOwner && timeline.length > 0 && reviewedCount === 0"
                        class="mt-4 rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:mt-6 sm:p-6"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white"
                                    style="
                                        background-image: linear-gradient(
                                            145deg,
                                            #1a4fb5,
                                            #123b72 55%,
                                            #071427
                                        );
                                    "
                                >
                                    <i class="ti ti-message-star text-lg" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-editorial text-lg font-semibold tracking-tight text-ink">
                                        No client reviews yet
                                    </p>
                                    <p class="mt-1 text-sm font-medium leading-relaxed text-ink/50">
                                        Send a review link from a logged job. When a client responds,
                                        their words appear next to that job — unedited.
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="route('work-log.index')"
                                class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-base-hover"
                            >
                                Open work log
                                <i class="ti ti-arrow-right" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>

                    <p
                        v-if="reviewedCount > 0"
                        class="mt-6 flex items-start justify-center gap-2 text-center text-xs font-medium leading-relaxed text-ink/35"
                    >
                        <i class="ti ti-quote mt-0.5 shrink-0 text-sm" aria-hidden="true" />
                        <span>
                            Reviews are published word-for-word as clients submitted them — no
                            translation, no edits.
                        </span>
                    </p>
                </section>

                <section
                    v-if="viewerIsOwner"
                    class="relative mt-8 overflow-hidden rounded-xl bg-ink px-4 py-7 text-white sm:mt-16 sm:rounded-2xl sm:px-10 sm:py-10"
                    aria-labelledby="qr-heading"
                >
                    <div
                        class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-base/30 blur-3xl"
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute -bottom-24 left-10 h-48 w-48 rounded-full bg-coral/20 blur-3xl"
                        aria-hidden="true"
                    />

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-xl">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-coral">
                                Share offline
                            </p>
                            <h2
                                id="qr-heading"
                                class="mt-2 font-editorial text-[1.75rem] font-semibold leading-tight tracking-tight sm:text-[2.15rem]"
                            >
                                Put your page on everything you touch
                            </h2>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-white/60">
                                Generate a QR code for vans, shop banners, invoices, and business cards.
                                One scan — straight to this profile.
                            </p>
                        </div>
                        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                            <button
                                type="button"
                                class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-ink shadow-premium-hover transition hover:bg-pale"
                                :disabled="!profile.public_url"
                                @click="qrOpen = true"
                            >
                                <i class="ti ti-qrcode text-lg text-deep" aria-hidden="true" />
                                Generate QR code
                            </button>
                            <button
                                type="button"
                                class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white/10 px-5 py-3.5 text-sm font-bold text-white ring-1 ring-white/15 transition hover:bg-white/15"
                                :disabled="!profile.public_url"
                                @click="copyPageLink"
                            >
                                <i
                                    :class="linkCopied ? 'ti ti-check text-emerald-300' : 'ti ti-link'"
                                    aria-hidden="true"
                                />
                                {{ linkCopied ? 'Copied' : 'Copy page link' }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <MediaLightbox
                v-model:show="lightboxOpen"
                :items="lightboxItems"
                :start-index="lightboxIndex"
            />
        </main>

        <div
            v-if="showWhatsAppBar"
            class="fixed inset-x-0 z-40 border-t border-ink/10 bg-white/95 px-2.5 py-2.5 backdrop-blur-md sm:px-3 sm:py-3"
            :class="isLoggedIn ? 'bottom-[4.25rem] md:bottom-0' : 'bottom-0'"
            :style="
                isLoggedIn
                    ? undefined
                    : { paddingBottom: 'max(0.65rem, env(safe-area-inset-bottom))' }
            "
        >
            <div class="mx-auto max-w-7xl">
                <button
                    type="button"
                    class="tap-target flex w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(37,211,102,0.55)] transition-opacity hover:opacity-95 sm:rounded-2xl"
                    @click="openWhatsAppChat"
                >
                    <i class="ti ti-brand-whatsapp text-lg" aria-hidden="true" />
                    {{ isLoggedIn ? 'Contact via WhatsApp' : 'Log in to chat on WhatsApp' }}
                </button>
            </div>
        </div>

        <ProfileQrModal
            :show="qrOpen"
            :url="profile.public_url || ''"
            :filename="`isabi-${profile.slug || 'page'}-qr`"
            :business-name="profile.business_name"
            :trade="profile.trade"
            @close="qrOpen = false"
        />

        <ReviewShareCard
            :show="shareOpen"
            :job="shareJob"
            :business-name="profile.business_name"
            :trade="profile.trade"
            :page-url="profile.public_url || ''"
            @close="shareOpen = false"
        />

        <div class="bg-pale">
            <SiteFooter />
        </div>
    </div>
    </component>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import JobMediaMosaic from '@/Components/Media/JobMediaMosaic.vue';
import MediaLightbox from '@/Components/Media/MediaLightbox.vue';
import JobLogControls from '@/Components/Public/JobLogControls.vue';
import ProfileQrModal from '@/Components/Public/ProfileQrModal.vue';
import ReviewShareCard from '@/Components/Public/ReviewShareCard.vue';
import StarDisplay from '@/Components/Reviews/StarDisplay.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { warmMediaItem } from '@/utils/mediaWarm';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    timeline: { type: Array, default: () => [] },
    viewerIsOwner: { type: Boolean, default: false },
});

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);

/** Guests and signed-in visitors can see chat; owners don’t message themselves. */
const showWhatsAppBar = computed(
    () => !!props.profile.whatsapp_url && !props.viewerIsOwner,
);

const openWhatsAppChat = () => {
    if (!props.profile.whatsapp_url) {
        return;
    }

    if (!isLoggedIn.value) {
        const redirect = encodeURIComponent(page.url || '/');
        router.visit(`${route('login')}?redirect=${redirect}`);
        return;
    }

    window.open(props.profile.whatsapp_url, '_blank', 'noopener,noreferrer');
};

const qrOpen = ref(false);
const linkCopied = ref(false);
const lightboxOpen = ref(false);
const lightboxItems = ref([]);
const lightboxIndex = ref(0);
const shareJob = ref(null);
const shareOpen = ref(false);
let linkTimer = null;

const headerRef = ref(null);
const headerHeight = ref(56);
const loadMoreSentinel = ref(null);

/** How many jobs to paint before the viewer scrolls for more. */
const PAGE_SIZE = 6;
const displayLimit = ref(PAGE_SIZE);

let resizeObserver = null;
let loadMoreObserver = null;

const search = ref('');
const sort = ref('recent');
const category = ref('');

const sortOptions = [
    { value: 'recent', label: 'Most recent' },
    { value: 'rated', label: 'Highest rated' },
    { value: 'oldest', label: 'Oldest first' },
];

const reviewedCount = computed(() => props.timeline.filter((job) => job.review).length);

/**
 * Category counts drive both the filter chips and the quick-jump for long logs.
 * Sorted by volume so a viewer sees the artisan's bread-and-butter work first.
 */
const categoryFacets = computed(() => {
    const counts = new Map();

    props.timeline.forEach((job) => {
        const value = job.job_category;
        if (!value) {
            return;
        }
        const existing = counts.get(value);
        if (existing) {
            existing.count += 1;
        } else {
            counts.set(value, {
                value,
                label: job.category_label || value,
                count: 1,
            });
        }
    });

    return [...counts.values()].sort((a, b) => b.count - a.count || a.label.localeCompare(b.label));
});

/** Full filtered/sorted list — search always runs against every job. */
const filteredJobs = computed(() => {
    const term = search.value.trim().toLowerCase();

    let jobs = props.timeline.filter((job) => {
        if (category.value && job.job_category !== category.value) {
            return false;
        }
        if (!term) {
            return true;
        }
        return [
            job.description,
            job.category_label,
            job.service_label,
            job.review?.comment,
            job.review?.client_display_name,
        ]
            .filter(Boolean)
            .some((field) => String(field).toLowerCase().includes(term));
    });

    if (sort.value === 'rated') {
        // Unreviewed jobs sink to the bottom rather than counting as zero-star.
        jobs = [...jobs].sort((a, b) => {
            const ra = a.review ? Number(a.review.rating) : -1;
            const rb = b.review ? Number(b.review.rating) : -1;
            return rb - ra || String(b.worked_on || '').localeCompare(String(a.worked_on || ''));
        });
    } else if (sort.value === 'oldest') {
        jobs = [...jobs].reverse();
    }

    return jobs;
});

/**
 * Search/category: show every match immediately (including jobs not yet
 * scrolled into the default window). Browsing: reveal in pages as they scroll.
 */
const revealAllMatches = computed(
    () => search.value.trim() !== '' || category.value !== '',
);

const visibleJobs = computed(() =>
    revealAllMatches.value
        ? filteredJobs.value
        : filteredJobs.value.slice(0, displayLimit.value),
);

const canLoadMore = computed(
    () => !revealAllMatches.value && visibleJobs.value.length < filteredJobs.value.length,
);

const revealMoreJobs = () => {
    if (!canLoadMore.value) {
        return;
    }
    displayLimit.value = Math.min(
        displayLimit.value + PAGE_SIZE,
        filteredJobs.value.length,
    );
};

if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
    loadMoreObserver = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                revealMoreJobs();
            }
        },
        { rootMargin: '280px 0px' },
    );
}

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || category.value !== '' || sort.value !== 'recent',
);

const clearFilters = () => {
    search.value = '';
    category.value = '';
    sort.value = 'recent';
    displayLimit.value = PAGE_SIZE;
};

watch([search, category, sort], () => {
    if (!revealAllMatches.value) {
        displayLimit.value = PAGE_SIZE;
    }
});

watch(
    loadMoreSentinel,
    (el, prev) => {
        if (prev) {
            loadMoreObserver?.unobserve(prev);
        }
        if (el) {
            loadMoreObserver?.observe(el);
        }
    },
    { flush: 'post' },
);

onMounted(() => {
    const measure = () => {
        headerHeight.value = headerRef.value?.offsetHeight || 56;
    };
    measure();

    if (headerRef.value && 'ResizeObserver' in window) {
        resizeObserver = new ResizeObserver(measure);
        resizeObserver.observe(headerRef.value);
    }

    // Deep links from a shared job land on the right card — reveal it first.
    const hash = window.location.hash;
    if (hash.startsWith('#job-')) {
        const uid = hash.slice(5);
        const index = props.timeline.findIndex((job) => job.uid === uid);
        if (index >= 0) {
            displayLimit.value = Math.max(displayLimit.value, index + 1);
        }
        nextTick(() => {
            document.querySelector(hash)?.scrollIntoView({ block: 'start' });
        });
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    loadMoreObserver?.disconnect();
});

const transparencyStats = computed(() => {
    const stats = [];
    const { review_rate: rate, avg_response_hours: hours, years_active: years } = props.profile;

    if (rate !== null && rate !== undefined && props.profile.jobs_count > 0) {
        stats.push({ label: 'Clients who left a review', value: `${rate}%` });
    }

    if (hours !== null && hours !== undefined) {
        stats.push({
            label: 'Average time from job logged to review',
            value:
                hours < 48
                    ? `${Math.max(1, hours)} hr${hours === 1 ? '' : 's'}`
                    : `${Math.round(hours / 24)} days`,
        });
    }

    if (years !== null && years !== undefined) {
        stats.push({ label: 'Years in this trade', value: `${years}` });
    }

    return stats;
});

const showTrustStrip = computed(
    () => props.profile.credentials?.length > 0 || transparencyStats.value.length > 0,
);

const openShare = (job) => {
    shareJob.value = job;
    shareOpen.value = true;
};

const jobHref = (job) => job?.detail_url || job?.public_url || '';

const initials = computed(() => {
    const parts = String(props.profile.business_name || 'A').trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return (parts[0]?.[0] || 'A').toUpperCase();
});

const ratingLabel = computed(() => {
    const avg = Number(props.profile.avg_rating);
    return Number.isFinite(avg) && avg > 0 ? avg.toFixed(1) : '—';
});

/** Long testimonials are collapsed so cards keep an even rhythm down the page. */
const LONG_REVIEW_CHARS = 280;
const expandedReviews = ref({});

const isLongReview = (job) => (job.review?.comment?.length || 0) > LONG_REVIEW_CHARS;

const isReviewClamped = (job) => isLongReview(job) && !expandedReviews.value[job.uid];

const toggleReview = (job) => {
    expandedReviews.value = {
        ...expandedReviews.value,
        [job.uid]: !expandedReviews.value[job.uid],
    };
};

const openJobMedia = (job, index) => {
    lightboxItems.value = job.media || [];
    lightboxIndex.value = index;
    warmMediaItem(job.media?.[index]);
    lightboxOpen.value = true;
};

const openReviewPhoto = (review) => {
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

const toast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('isabi:toast', {
            detail: { type, message, duration: 3500 },
        }),
    );
};

const copyPageLink = async () => {
    if (!props.profile.public_url) {
        return;
    }
    try {
        await navigator.clipboard.writeText(props.profile.public_url);
        linkCopied.value = true;
        window.clearTimeout(linkTimer);
        linkTimer = window.setTimeout(() => {
            linkCopied.value = false;
        }, 2200);
        toast('Page link copied.');
    } catch {
        toast('Couldn’t copy the link automatically.', 'error');
    }
};
</script>

<style scoped>
.profile-hero {
    animation: profile-fade-up 520ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.profile-job {
    animation: profile-fade-up 460ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes profile-fade-up {
    from {
        opacity: 0;
        transform: translate3d(0, 10px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .profile-hero,
    .profile-job {
        animation: none;
    }
}
</style>
