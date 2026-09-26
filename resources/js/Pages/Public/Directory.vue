<template>
    <Head :title="landing.title || 'Find artisans'" />

    <div class="dir-page min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <!-- Hero — centred like public profile banners -->
        <section class="relative overflow-hidden bg-[#071427]">
            <div
                class="pointer-events-none absolute inset-0"
                style="
                    background: linear-gradient(
                        180deg,
                        #071427 0%,
                        #071427 120px,
                        #0a1c36 48%,
                        #0c2140 100%
                    );
                "
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 top-24 bg-[radial-gradient(ellipse_80%_60%_at_50%_20%,rgba(47,111,237,0.28),transparent_58%),radial-gradient(ellipse_70%_50%_at_90%_80%,rgba(255,106,61,0.16),transparent_52%)]"
                aria-hidden="true"
            />

            <div
                class="relative mx-auto flex max-w-7xl flex-col items-center px-2.5 pb-12 pt-10 text-center sm:px-6 sm:pb-16 sm:pt-14 lg:px-8"
            >
                <nav
                    v-if="locked.trade || locked.state"
                    class="mb-5 flex flex-wrap items-center justify-center gap-1.5 text-xs font-semibold text-white/45"
                    aria-label="Breadcrumb"
                >
                    <Link
                        :href="route('public.directory')"
                        class="transition-colors hover:text-white"
                    >
                        All artisans
                    </Link>
                    <template v-if="locked.trade">
                        <i class="ti ti-chevron-right text-[10px] text-white/30" aria-hidden="true" />
                        <Link
                            v-if="locked.state"
                            :href="route('public.directory.trade', tradeSlug(locked.trade))"
                            class="transition-colors hover:text-white"
                        >
                            {{ locked.trade }}
                        </Link>
                        <span v-else class="text-white/85">{{ locked.trade }}</span>
                    </template>
                    <template v-if="locked.state">
                        <i class="ti ti-chevron-right text-[10px] text-white/30" aria-hidden="true" />
                        <span class="text-white/85">{{ locked.state }}</span>
                    </template>
                </nav>

                <p
                    class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-white/50"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                    Verified by finished work
                </p>
                <h1
                    class="mt-4 max-w-3xl font-editorial text-[clamp(2.1rem,5vw,3.5rem)] font-semibold leading-[1.05] tracking-tight text-white"
                >
                    {{ landing.heading }}
                </h1>
                <p
                    class="mt-4 max-w-xl text-base font-medium leading-relaxed text-white/65 sm:text-lg"
                >
                    {{ landing.subheading }}
                </p>

                <div class="mt-8 w-full max-w-xl sm:mt-10">
                    <label class="relative block text-left">
                        <span class="sr-only">Search artisans</span>
                        <i
                            class="ti ti-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ink/35 sm:left-5"
                            aria-hidden="true"
                        />
                        <input
                            v-model.trim="query"
                            type="search"
                            placeholder="Search by name, trade, or area…"
                            autocomplete="off"
                            class="w-full rounded-2xl border-0 bg-white py-4 pl-12 pr-4 text-base font-semibold text-ink shadow-[0_20px_50px_-24px_rgba(0,0,0,0.55)] placeholder:font-medium placeholder:text-ink/35 focus:outline-none focus:ring-2 focus:ring-coral/40 sm:pl-14 sm:pr-5"
                        />
                    </label>
                    <p
                        v-if="meta.total_eligible"
                        class="mt-3 text-xs font-semibold tabular-nums text-white/40"
                    >
                        {{ meta.total_eligible.toLocaleString() }} public
                        {{ meta.total_eligible === 1 ? 'page' : 'pages' }} with real client proof
                    </p>
                </div>
            </div>
        </section>

        <main class="mx-auto max-w-7xl px-2.5 pb-20 pt-6 sm:px-6 sm:pb-24 sm:pt-8 lg:px-8">
            <!-- Toolbar: Filters + Sort -->
            <div
                class="sticky top-[calc(env(safe-area-inset-top)+3.25rem)] z-20 -mx-2.5 mb-6 border-b border-ink/[0.06] bg-pale/95 px-2.5 py-3 backdrop-blur-xl sm:static sm:mx-0 sm:mb-8 sm:rounded-[1.35rem] sm:border sm:border-ink/[0.06] sm:bg-white sm:px-4 sm:py-3.5 sm:shadow-premium sm:backdrop-blur-none"
            >
                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        class="tap-target inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-ink px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-deep sm:flex-none sm:min-w-[8.5rem]"
                        @click="openFilterModal"
                    >
                        <i class="ti ti-adjustments-horizontal text-lg" aria-hidden="true" />
                        Filters
                        <span
                            v-if="activeFilterCount"
                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-coral px-1.5 text-[11px] font-extrabold tabular-nums text-white"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </button>

                    <!-- Custom sort -->
                    <div ref="sortRoot" class="relative flex-1 sm:flex-none sm:min-w-[12.5rem]">
                        <button
                            type="button"
                            class="tap-target flex w-full items-center justify-between gap-2 rounded-2xl bg-white px-4 py-3 text-left text-sm font-bold text-ink ring-1 ring-ink/[0.08] transition-colors hover:bg-pale sm:bg-pale/70"
                            :aria-expanded="sortOpen"
                            aria-haspopup="listbox"
                            @click="sortOpen = !sortOpen"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <i class="ti ti-arrows-sort shrink-0 text-base text-ink/35" aria-hidden="true" />
                                <span class="truncate">{{ currentSortLabel }}</span>
                            </span>
                            <i
                                class="ti ti-chevron-down shrink-0 text-base text-ink/35 transition-transform"
                                :class="{ 'rotate-180': sortOpen }"
                                aria-hidden="true"
                            />
                        </button>

                        <Transition name="sort-menu">
                            <ul
                                v-if="sortOpen"
                                class="absolute inset-x-0 top-[calc(100%+0.4rem)] z-30 overflow-hidden rounded-2xl bg-white py-1.5 shadow-premium-hover ring-1 ring-ink/[0.08]"
                                role="listbox"
                            >
                                <li v-for="option in sortOptions" :key="option.value">
                                    <button
                                        type="button"
                                        class="tap-target flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm font-semibold transition-colors"
                                        :class="
                                            sort === option.value
                                                ? 'bg-tint text-deep'
                                                : 'text-ink/70 hover:bg-pale hover:text-ink'
                                        "
                                        role="option"
                                        :aria-selected="sort === option.value"
                                        @click="selectSort(option.value)"
                                    >
                                        {{ option.label }}
                                        <i
                                            v-if="sort === option.value"
                                            class="ti ti-check text-base text-base-action"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </li>
                            </ul>
                        </Transition>
                    </div>
                </div>

                <!-- Active filter chips -->
                <div v-if="activeFilterChips.length" class="mt-3 flex flex-wrap gap-1.5">
                    <button
                        v-for="chip in activeFilterChips"
                        :key="chip.key"
                        type="button"
                        class="tap-target inline-flex items-center gap-1.5 rounded-full bg-tint px-2.5 py-1.5 text-[11px] font-bold text-deep transition-colors hover:bg-base-action hover:text-white"
                        @click="removeChip(chip)"
                    >
                        {{ chip.label }}
                        <i class="ti ti-x text-xs" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        class="tap-target inline-flex items-center rounded-full px-2.5 py-1.5 text-[11px] font-bold text-base-action hover:bg-tint"
                        @click="clearFilters"
                    >
                        Clear all
                    </button>
                </div>

                <p class="mt-3 text-[12px] font-bold tabular-nums tracking-tight text-ink/40">
                    <template v-if="filtered.length">
                        <span class="text-ink/70">{{ Math.min(visibleCount, filtered.length) }}</span>
                        of
                        <span class="text-ink/70">{{ filtered.length }}</span>
                        shown
                        <span
                            v-if="meta.capped && !hasActiveFilters"
                            class="font-semibold text-ink/30"
                        >
                            · from {{ meta.total_eligible.toLocaleString() }}
                        </span>
                    </template>
                    <template v-else>No matches</template>
                </p>
            </div>

            <AppEmptyState
                v-if="!artisans.length"
                icon="ti ti-search"
                title="No public pages yet"
                :description="
                    locked.trade
                        ? `Be the first ${locked.trade.toLowerCase()}${locked.state ? ` in ${locked.state}` : ''} with a Kraftrack page.`
                        : 'When artisans log finished jobs, they appear here for clients to find.'
                "
                cta-label="Create your free page"
                :cta-href="route('register')"
            />

            <div
                v-else-if="!filtered.length"
                class="rounded-[1.75rem] bg-white px-6 py-16 text-center shadow-premium ring-1 ring-ink/[0.05]"
            >
                <span
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-2xl text-ink/30"
                >
                    <i class="ti ti-filter-off" aria-hidden="true" />
                </span>
                <p class="mt-5 font-editorial text-xl font-semibold tracking-tight text-ink">
                    No artisans match
                </p>
                <p class="mx-auto mt-2 max-w-sm text-sm font-medium leading-relaxed text-ink/45">
                    Try different filters, or clear them to see everyone.
                </p>
                <button
                    type="button"
                    class="tap-target mt-6 inline-flex items-center gap-1.5 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.55)] transition-colors hover:bg-base-hover"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>

            <template v-else>
                <ul class="grid gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-3">
                    <li
                        v-for="(artisan, index) in visible"
                        :key="artisan.slug"
                        class="dir-card"
                        :style="{ animationDelay: `${Math.min(index, 11) * 40}ms` }"
                    >
                        <Link
                            :href="artisan.url"
                            class="group relative flex h-full flex-col overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.05] transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-premium-hover hover:ring-base-action/20 sm:rounded-[1.65rem]"
                        >
                            <div
                                class="h-1.5 w-full bg-gradient-to-r from-base-action via-[#2F6FED] to-coral/80 opacity-90 transition-opacity group-hover:opacity-100"
                                aria-hidden="true"
                            />

                            <div class="flex flex-1 flex-col p-5 sm:p-6">
                                <div class="flex items-start gap-4">
                                    <span
                                        class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-tint to-pale text-base font-extrabold text-deep ring-1 ring-ink/[0.06] sm:h-16 sm:w-16 sm:text-lg"
                                    >
                                        <img
                                            v-if="artisan.avatar_url"
                                            :src="artisan.avatar_url"
                                            :alt="artisan.business_name"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        />
                                        <span v-else>{{ initials(artisan.business_name) }}</span>
                                    </span>

                                    <div class="min-w-0 flex-1 pt-0.5">
                                        <h2
                                            class="truncate font-editorial text-[1.15rem] font-semibold leading-snug tracking-tight text-ink sm:text-[1.25rem]"
                                        >
                                            {{ artisan.business_name }}
                                        </h2>
                                        <p
                                            class="mt-1 flex items-center gap-1.5 truncate text-sm font-bold text-base-action"
                                        >
                                            <i
                                                :class="[tradeIconFor(artisan.trade), 'shrink-0 text-base']"
                                                aria-hidden="true"
                                            />
                                            <span class="truncate">{{ artisan.trade }}</span>
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="artisan.area_label"
                                    class="mt-4 flex items-center gap-1.5 text-sm font-medium text-ink/45"
                                >
                                    <i class="ti ti-map-pin text-base text-ink/30" aria-hidden="true" />
                                    <span class="truncate">{{ artisan.area_label }}</span>
                                </p>

                                <div
                                    class="mt-auto flex items-end justify-between gap-3 border-t border-ink/[0.05] pt-4"
                                >
                                    <div class="min-w-0">
                                        <div v-if="artisan.avg_rating" class="flex items-center gap-2">
                                            <StarDisplay
                                                :rating="artisan.avg_rating"
                                                size="sm"
                                                empty-class="text-ink/12"
                                            />
                                            <span
                                                class="text-sm font-extrabold tabular-nums tracking-tight text-ink"
                                            >
                                                {{ artisan.avg_rating.toFixed(1) }}
                                            </span>
                                        </div>
                                        <p v-else class="text-xs font-semibold text-ink/35">
                                            Awaiting first review
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] font-bold uppercase tracking-[0.06em] text-ink/35"
                                        >
                                            <span class="tabular-nums">{{ artisan.jobs_count }}</span>
                                            {{ artisan.jobs_count === 1 ? 'job' : 'jobs' }}
                                            <template v-if="artisan.review_count">
                                                ·
                                                <span class="tabular-nums">{{
                                                    artisan.review_count
                                                }}</span>
                                                {{
                                                    artisan.review_count === 1
                                                        ? 'review'
                                                        : 'reviews'
                                                }}
                                            </template>
                                        </p>
                                    </div>

                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-ink/25 transition-all duration-300 group-hover:bg-base-action group-hover:text-white"
                                        aria-hidden="true"
                                    >
                                        <i class="ti ti-arrow-right text-lg" />
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>

                <div v-if="canLoadMore" class="mt-10 flex justify-center">
                    <button
                        type="button"
                        class="tap-target inline-flex items-center gap-2.5 rounded-2xl bg-base-action px-6 py-3.5 text-sm font-bold text-white shadow-[0_14px_32px_-12px_rgba(26,79,181,0.55)] transition-all hover:bg-base-hover"
                        @click="loadMore"
                    >
                        Show more
                        <span class="tabular-nums font-semibold text-white/65">
                            {{ filtered.length - visibleCount }} left
                        </span>
                    </button>
                </div>
            </template>

            <section
                v-if="relatedLandings.length"
                class="mt-16 sm:mt-20"
                aria-labelledby="related-landings-heading"
            >
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink/35">Explore</p>
                <h2
                    id="related-landings-heading"
                    class="mt-1 font-editorial text-2xl font-semibold tracking-tight text-ink sm:text-[1.75rem]"
                >
                    {{ locked.trade ? 'Nearby areas' : 'Browse by trade & location' }}
                </h2>

                <ul class="mt-6 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="link in relatedLandings" :key="link.url">
                        <Link
                            :href="link.url"
                            class="group flex items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3.5 shadow-premium ring-1 ring-ink/[0.05] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-premium-hover hover:ring-base-action/15"
                        >
                            <span class="min-w-0 truncate text-sm font-bold tracking-tight text-ink">
                                {{ link.label }}
                            </span>
                            <i
                                class="ti ti-arrow-up-right shrink-0 text-base text-ink/20 transition-colors group-hover:text-base-action"
                                aria-hidden="true"
                            />
                        </Link>
                    </li>
                </ul>
            </section>

            <section
                class="relative mt-16 overflow-hidden rounded-[1.75rem] bg-[#071427] px-6 py-10 text-center sm:mt-20 sm:rounded-[2rem] sm:px-10 sm:py-12 sm:text-left"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_80%_at_50%_0%,rgba(47,111,237,0.35),transparent_55%),radial-gradient(45%_60%_at_100%_100%,rgba(255,106,61,0.2),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="relative flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:justify-between sm:text-left"
                >
                    <div class="max-w-lg">
                        <h2
                            class="font-editorial text-2xl font-semibold tracking-tight text-white sm:text-[1.85rem]"
                        >
                            Are you an artisan?
                        </h2>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-white/60 sm:text-base">
                            Log finished jobs, collect real client reviews, and share one page that
                            new customers can trust.
                        </p>
                    </div>
                    <Link
                        :href="route('register')"
                        class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-coral px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                    >
                        Create your free page
                        <i class="ti ti-arrow-right text-base" aria-hidden="true" />
                    </Link>
                </div>
            </section>
        </main>

        <SiteFooter />

        <!-- Filter modal (bottom sheet on mobile) -->
        <AppModal
            :show="filterOpen"
            title="Filters"
            description="Tick what you want to see. Leave a section empty to include everything."
            icon="ti ti-adjustments-horizontal"
            size="lg"
            sheet
            @close="closeFilterModal"
        >
            <div class="space-y-6">
                <section v-for="group in filterGroups" :key="group.key">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-extrabold tracking-tight text-ink">
                            {{ group.label }}
                        </h3>
                        <button
                            v-if="draft[group.key]?.length && !group.locked"
                            type="button"
                            class="text-xs font-bold text-base-action hover:underline"
                            @click="draft[group.key] = []"
                        >
                            Clear
                        </button>
                    </div>
                    <ul class="mt-3 max-h-44 space-y-0.5 overflow-y-auto overscroll-contain sm:max-h-52">
                        <li v-for="option in group.options" :key="option">
                            <label
                                class="tap-target flex cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2.5 transition-colors hover:bg-pale"
                                :class="{ 'opacity-60': group.locked }"
                            >
                                <span class="relative flex h-5 w-5 shrink-0 items-center justify-center">
                                    <input
                                        type="checkbox"
                                        class="absolute inset-0 z-10 cursor-pointer opacity-0 disabled:cursor-not-allowed"
                                        :checked="draft[group.key].includes(option)"
                                        :disabled="group.locked"
                                        @change="toggleDraft(group.key, option)"
                                    />
                                    <span
                                        class="flex h-5 w-5 items-center justify-center rounded-md border transition-all"
                                        :class="
                                            draft[group.key].includes(option)
                                                ? 'border-base bg-base shadow-[0_0_0_3px_rgba(47,111,237,0.12)]'
                                                : 'border-ink/15 bg-white'
                                        "
                                    >
                                        <i
                                            class="ti ti-check text-xs text-white"
                                            :class="
                                                draft[group.key].includes(option)
                                                    ? 'opacity-100'
                                                    : 'opacity-0'
                                            "
                                            aria-hidden="true"
                                        />
                                    </span>
                                </span>
                                <span class="min-w-0 flex-1 text-sm font-semibold text-ink/75">
                                    {{ option }}
                                </span>
                            </label>
                        </li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-sm font-extrabold tracking-tight text-ink">Minimum rating</h3>
                    <ul class="mt-3 space-y-0.5">
                        <li v-for="option in ratingOptions" :key="option.value || 'any'">
                            <label
                                class="tap-target flex cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2.5 transition-colors hover:bg-pale"
                            >
                                <span class="relative flex h-5 w-5 shrink-0 items-center justify-center">
                                    <input
                                        v-model="draft.minRating"
                                        type="radio"
                                        class="absolute inset-0 z-10 cursor-pointer opacity-0"
                                        name="dir-min-rating"
                                        :value="option.value"
                                    />
                                    <span
                                        class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                                        :class="
                                            draft.minRating === option.value
                                                ? 'border-base bg-base shadow-[0_0_0_3px_rgba(47,111,237,0.12)]'
                                                : 'border-ink/15 bg-white'
                                        "
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-white transition-opacity"
                                            :class="
                                                draft.minRating === option.value
                                                    ? 'opacity-100'
                                                    : 'opacity-0'
                                            "
                                        />
                                    </span>
                                </span>
                                <span class="text-sm font-semibold text-ink/75">{{ option.label }}</span>
                            </label>
                        </li>
                    </ul>
                </section>
            </div>

            <template #footer>
                <FormButton variant="secondary" class="w-full sm:w-auto" @click="resetDraft">
                    Reset
                </FormButton>
                <FormButton variant="primary" class="w-full sm:w-auto" @click="applyFilters">
                    Show results
                    <template v-if="draftMatchCount !== null">
                        ({{ draftMatchCount }})
                    </template>
                </FormButton>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AppModal from '@/Components/App/AppModal.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import StarDisplay from '@/Components/Reviews/StarDisplay.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { tradeIcon } from '@/utils/tradeIcons';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const PAGE_SIZE = 24;

const props = defineProps({
    artisans: { type: Array, default: () => [] },
    filterOptions: {
        type: Object,
        default: () => ({ trades: [], categories: [], states: [] }),
    },
    locked: {
        type: Object,
        default: () => ({ trade: null, state: null }),
    },
    landing: {
        type: Object,
        default: () => ({
            title: 'Find artisans',
            heading: 'Artisans with real proof of work',
            subheading: '',
        }),
    },
    relatedLandings: { type: Array, default: () => [] },
    meta: {
        type: Object,
        default: () => ({ loaded: 0, total_eligible: 0, capped: false, cap: 300 }),
    },
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const query = ref('');
const selectedTrades = ref(props.locked.trade ? [props.locked.trade] : []);
const selectedCategories = ref([]);
const selectedStates = ref(props.locked.state ? [props.locked.state] : []);
const minRating = ref('');
const sort = ref('reviews');
const visibleCount = ref(PAGE_SIZE);

const filterOpen = ref(false);
const sortOpen = ref(false);
const sortRoot = ref(null);

const draft = reactive({
    trades: [],
    categories: [],
    states: [],
    minRating: '',
});

const sortOptions = [
    { value: 'reviews', label: 'Most reviews' },
    { value: 'rating', label: 'Highest rated' },
    { value: 'jobs', label: 'Most jobs' },
    { value: 'name', label: 'Name A–Z' },
];

const ratingOptions = [
    { value: '', label: 'Any rating' },
    { value: '4.5', label: '4.5+ stars' },
    { value: '4', label: '4+ stars' },
    { value: '3', label: '3+ stars' },
];

const currentSortLabel = computed(
    () => sortOptions.find((o) => o.value === sort.value)?.label || 'Most reviews',
);

const filterGroups = computed(() => [
    {
        key: 'trades',
        label: 'Trades',
        options: props.filterOptions.trades || [],
        locked: Boolean(props.locked.trade),
    },
    {
        key: 'categories',
        label: 'Categories',
        options: props.filterOptions.categories || [],
        locked: false,
    },
    {
        key: 'states',
        label: 'States',
        options: props.filterOptions.states || [],
        locked: Boolean(props.locked.state),
    },
]);

const activeFilterCount = computed(() => {
    let n = 0;
    if (!props.locked.trade) n += selectedTrades.value.length;
    n += selectedCategories.value.length;
    if (!props.locked.state) n += selectedStates.value.length;
    if (minRating.value) n += 1;
    return n;
});

const hasActiveFilters = computed(
    () => activeFilterCount.value > 0 || Boolean(query.value),
);

const activeFilterChips = computed(() => {
    const chips = [];
    for (const t of selectedTrades.value) {
        if (props.locked.trade === t) continue;
        chips.push({ key: `trade:${t}`, type: 'trade', value: t, label: t });
    }
    for (const c of selectedCategories.value) {
        chips.push({ key: `cat:${c}`, type: 'category', value: c, label: c });
    }
    for (const s of selectedStates.value) {
        if (props.locked.state === s) continue;
        chips.push({ key: `state:${s}`, type: 'state', value: s, label: s });
    }
    if (minRating.value) {
        const label = ratingOptions.find((o) => o.value === minRating.value)?.label || minRating.value;
        chips.push({ key: `rating:${minRating.value}`, type: 'rating', value: minRating.value, label });
    }
    return chips;
});

const matchesFilters = (a, trades, categories, states, rating) => {
    if (trades.length && !trades.includes(a.trade)) return false;
    if (categories.length && !categories.includes(a.category)) return false;
    if (states.length && !states.includes(a.state)) return false;
    if (rating) {
        const min = Number(rating);
        if (a.avg_rating == null || Number(a.avg_rating) < min) return false;
    }
    return true;
};

const filtered = computed(() => {
    const q = query.value.toLowerCase();
    const trades = selectedTrades.value;
    const categories = selectedCategories.value;
    const states = selectedStates.value;
    const rating = minRating.value;

    let rows = props.artisans.filter((a) => {
        if (!matchesFilters(a, trades, categories, states, rating)) return false;
        if (!q) return true;
        const hay = [a.business_name, a.trade, a.category, a.area_label, a.lga, a.state, a.slug]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return hay.includes(q);
    });

    rows = [...rows];
    if (sort.value === 'jobs') {
        rows.sort(
            (a, b) =>
                b.jobs_count - a.jobs_count ||
                b.review_count - a.review_count ||
                a.business_name.localeCompare(b.business_name),
        );
    } else if (sort.value === 'rating') {
        rows.sort(
            (a, b) =>
                (b.avg_rating ?? 0) - (a.avg_rating ?? 0) ||
                b.review_count - a.review_count ||
                a.business_name.localeCompare(b.business_name),
        );
    } else if (sort.value === 'name') {
        rows.sort((a, b) => a.business_name.localeCompare(b.business_name));
    } else {
        rows.sort(
            (a, b) =>
                b.review_count - a.review_count ||
                b.jobs_count - a.jobs_count ||
                a.business_name.localeCompare(b.business_name),
        );
    }

    return rows;
});

const draftMatchCount = computed(() => {
    if (!filterOpen.value) return null;
    return props.artisans.filter((a) =>
        matchesFilters(a, draft.trades, draft.categories, draft.states, draft.minRating),
    ).length;
});

const visible = computed(() => filtered.value.slice(0, visibleCount.value));
const canLoadMore = computed(() => visibleCount.value < filtered.value.length);

watch([query, selectedTrades, selectedCategories, selectedStates, minRating, sort], () => {
    visibleCount.value = PAGE_SIZE;
});

const loadMore = () => {
    visibleCount.value = Math.min(visibleCount.value + PAGE_SIZE, filtered.value.length);
};

const selectSort = (value) => {
    sort.value = value;
    sortOpen.value = false;
};

const openFilterModal = () => {
    draft.trades = [...selectedTrades.value];
    draft.categories = [...selectedCategories.value];
    draft.states = [...selectedStates.value];
    draft.minRating = minRating.value;
    filterOpen.value = true;
    sortOpen.value = false;
};

const closeFilterModal = () => {
    filterOpen.value = false;
};

const toggleDraft = (key, option) => {
    const list = draft[key];
    const idx = list.indexOf(option);
    if (idx >= 0) {
        list.splice(idx, 1);
    } else {
        list.push(option);
    }
};

const applyFilters = () => {
    selectedTrades.value = props.locked.trade
        ? [props.locked.trade]
        : [...draft.trades];
    selectedCategories.value = [...draft.categories];
    selectedStates.value = props.locked.state
        ? [props.locked.state]
        : [...draft.states];
    minRating.value = draft.minRating;
    filterOpen.value = false;
};

const resetDraft = () => {
    draft.trades = props.locked.trade ? [props.locked.trade] : [];
    draft.categories = [];
    draft.states = props.locked.state ? [props.locked.state] : [];
    draft.minRating = '';
};

const clearFilters = () => {
    query.value = '';
    selectedTrades.value = props.locked.trade ? [props.locked.trade] : [];
    selectedCategories.value = [];
    selectedStates.value = props.locked.state ? [props.locked.state] : [];
    minRating.value = '';
    visibleCount.value = PAGE_SIZE;
};

const removeChip = (chip) => {
    if (chip.type === 'trade') {
        selectedTrades.value = selectedTrades.value.filter((t) => t !== chip.value);
    } else if (chip.type === 'category') {
        selectedCategories.value = selectedCategories.value.filter((c) => c !== chip.value);
    } else if (chip.type === 'state') {
        selectedStates.value = selectedStates.value.filter((s) => s !== chip.value);
    } else if (chip.type === 'rating') {
        minRating.value = '';
    }
};

const tradeSlug = (label) =>
    String(label || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');

const tradeIconFor = (trade) => tradeIcon(trade);

const initials = (name) => {
    const parts = String(name || '')
        .trim()
        .split(/\s+/);
    if (parts.length >= 2) {
        return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
    }
    return (parts[0]?.[0] || 'K').toUpperCase();
};

const onDocClick = (e) => {
    if (!sortOpen.value) return;
    if (sortRoot.value && !sortRoot.value.contains(e.target)) {
        sortOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', onDocClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocClick));
</script>

<style scoped>
.dir-card {
    animation: dir-rise 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes dir-rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sort-menu-enter-active,
.sort-menu-leave-active {
    transition:
        opacity 0.16s ease,
        transform 0.16s ease;
}

.sort-menu-enter-from,
.sort-menu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

@media (prefers-reduced-motion: reduce) {
    .dir-card {
        animation: none;
    }
}
</style>
