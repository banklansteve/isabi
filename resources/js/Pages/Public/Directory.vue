<template>
    <Head title="Find artisans" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-8 sm:pb-20 sm:pt-12">
            <section
                class="relative mb-6 overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:rounded-[1.75rem] sm:px-8 sm:py-10"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div class="relative max-w-2xl">
                    <p
                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                        Directory
                    </p>
                    <h1
                        class="mt-3 font-editorial text-[1.9rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.6rem]"
                    >
                        Artisans with real proof of work
                    </h1>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-white/65 sm:text-base">
                        Public pages built from finished jobs and reviews written by clients — not
                        self-written testimonials.
                    </p>
                </div>
            </section>

            <!-- Controls -->
            <div
                class="sticky top-[calc(env(safe-area-inset-top)+3.25rem)] z-20 -mx-4 mb-5 border-b border-ink/[0.06] bg-pale/95 px-4 py-3 backdrop-blur-md sm:static sm:mx-0 sm:mb-6 sm:rounded-2xl sm:border sm:border-ink/[0.06] sm:bg-white sm:px-4 sm:py-4 sm:shadow-premium sm:backdrop-blur-none"
            >
                <div class="flex flex-col gap-3">
                    <label class="relative block">
                        <span class="sr-only">Search artisans</span>
                        <i
                            class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-ink/30"
                            aria-hidden="true"
                        />
                        <input
                            v-model.trim="query"
                            type="search"
                            placeholder="Search name, trade, or area…"
                            autocomplete="off"
                            class="w-full rounded-xl border-0 bg-white py-3 pl-11 pr-4 text-sm font-medium text-ink shadow-sm ring-1 ring-ink/[0.08] placeholder:text-ink/35 focus:outline-none focus:ring-2 focus:ring-base-action/35 sm:bg-pale/60"
                        />
                    </label>

                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                        <select
                            v-model="trade"
                            class="w-full rounded-xl border-0 bg-white py-2.5 pl-3 pr-8 text-sm font-semibold text-ink ring-1 ring-ink/[0.08] focus:outline-none focus:ring-2 focus:ring-base-action/35 sm:w-auto sm:min-w-[10.5rem]"
                        >
                            <option value="">All trades</option>
                            <option v-for="t in filterOptions.trades" :key="t" :value="t">
                                {{ t }}
                            </option>
                        </select>

                        <select
                            v-model="state"
                            class="w-full rounded-xl border-0 bg-white py-2.5 pl-3 pr-8 text-sm font-semibold text-ink ring-1 ring-ink/[0.08] focus:outline-none focus:ring-2 focus:ring-base-action/35 sm:w-auto sm:min-w-[9rem]"
                        >
                            <option value="">All states</option>
                            <option v-for="s in filterOptions.states" :key="s" :value="s">
                                {{ s }}
                            </option>
                        </select>

                        <select
                            v-model="sort"
                            class="w-full rounded-xl border-0 bg-white py-2.5 pl-3 pr-8 text-sm font-semibold text-ink ring-1 ring-ink/[0.08] focus:outline-none focus:ring-2 focus:ring-base-action/35 sm:w-auto sm:min-w-[10rem]"
                        >
                            <option value="reviews">Most reviews</option>
                            <option value="jobs">Most jobs</option>
                            <option value="name">Name A–Z</option>
                        </select>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="tap-target inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-bold text-base-action hover:bg-tint sm:ml-auto"
                            @click="clearFilters"
                        >
                            <i class="ti ti-x" aria-hidden="true" />
                            Clear
                        </button>
                    </div>
                </div>

                <p class="mt-3 text-[12px] font-semibold tabular-nums text-ink/40">
                    <template v-if="filtered.length">
                        Showing {{ visibleCount }} of {{ filtered.length }}
                        <span v-if="meta.capped && !hasActiveFilters">
                            · curated from {{ meta.total_eligible.toLocaleString() }}
                        </span>
                    </template>
                    <template v-else> No matches </template>
                </p>
            </div>

            <AppEmptyState
                v-if="!artisans.length"
                icon="ti ti-search"
                title="No public pages yet"
                description="When artisans log finished jobs, they appear here for clients to find."
                cta-label="Create your free page"
                :cta-href="route('register')"
            />

            <div
                v-else-if="!filtered.length"
                class="rounded-[1.5rem] bg-white px-6 py-12 text-center shadow-premium ring-1 ring-ink/[0.06]"
            >
                <i class="ti ti-filter-off text-3xl text-ink/25" aria-hidden="true" />
                <p class="mt-3 text-sm font-bold text-ink">No artisans match</p>
                <p class="mt-1 text-sm font-medium text-ink/45">
                    Try a different trade, state, or search term.
                </p>
                <button
                    type="button"
                    class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-base-action px-4 py-2.5 text-sm font-bold text-white hover:bg-base-hover"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>

            <template v-else>
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="artisan in visible" :key="artisan.slug">
                        <Link
                            :href="artisan.url"
                            class="group flex h-full items-start gap-3.5 rounded-[1.35rem] bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.06] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-hover hover:ring-base-action/20 sm:gap-4 sm:rounded-[1.5rem] sm:p-5"
                        >
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-tint text-sm font-extrabold text-deep sm:h-14 sm:w-14"
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
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold tracking-tight text-ink">
                                    {{ artisan.business_name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs font-medium text-ink/45">
                                    {{
                                        [artisan.trade, artisan.area_label]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </p>
                                <p class="mt-2 text-[11px] font-semibold text-ink/40">
                                    {{ artisan.jobs_count }} job{{
                                        artisan.jobs_count === 1 ? '' : 's'
                                    }}
                                    <span v-if="artisan.review_count">
                                        · {{ artisan.review_count }} review{{
                                            artisan.review_count === 1 ? '' : 's'
                                        }}
                                    </span>
                                </p>
                            </div>
                            <i
                                class="ti ti-chevron-right mt-1 shrink-0 text-ink/15 transition-colors group-hover:text-base-action"
                                aria-hidden="true"
                            />
                        </Link>
                    </li>
                </ul>

                <div v-if="canLoadMore" class="mt-8 flex justify-center">
                    <button
                        type="button"
                        class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-12px_rgba(26,79,181,0.55)] transition-colors hover:bg-base-hover"
                        @click="loadMore"
                    >
                        Show more
                        <span class="tabular-nums text-white/70"
                            >({{ filtered.length - visibleCount }} left)</span
                        >
                    </button>
                </div>
            </template>
        </main>

        <SiteFooter />
    </div>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { computed, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const PAGE_SIZE = 24;

const props = defineProps({
    artisans: { type: Array, default: () => [] },
    filterOptions: {
        type: Object,
        default: () => ({ trades: [], states: [] }),
    },
    meta: {
        type: Object,
        default: () => ({ loaded: 0, total_eligible: 0, capped: false, cap: 300 }),
    },
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const query = ref('');
const trade = ref('');
const state = ref('');
const sort = ref('reviews');
const visibleCount = ref(PAGE_SIZE);

const hasActiveFilters = computed(
    () => Boolean(query.value || trade.value || state.value || sort.value !== 'reviews'),
);

const filtered = computed(() => {
    const q = query.value.toLowerCase();

    let rows = props.artisans.filter((a) => {
        if (trade.value && a.trade !== trade.value) return false;
        if (state.value && a.state !== state.value) return false;
        if (!q) return true;
        const hay = [a.business_name, a.trade, a.area_label, a.lga, a.state, a.slug]
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

const visible = computed(() => filtered.value.slice(0, visibleCount.value));
const canLoadMore = computed(() => visibleCount.value < filtered.value.length);

watch([query, trade, state, sort], () => {
    visibleCount.value = PAGE_SIZE;
});

const loadMore = () => {
    visibleCount.value = Math.min(visibleCount.value + PAGE_SIZE, filtered.value.length);
};

const clearFilters = () => {
    query.value = '';
    trade.value = '';
    state.value = '';
    sort.value = 'reviews';
    visibleCount.value = PAGE_SIZE;
};

const initials = (name) => {
    const parts = String(name || '')
        .trim()
        .split(/\s+/);
    if (parts.length >= 2) {
        return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
    }
    return (parts[0]?.[0] || 'K').toUpperCase();
};
</script>
