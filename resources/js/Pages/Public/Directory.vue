<template>
    <Head title="Find artisans" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="mx-auto max-w-6xl px-5 pb-16 pt-10 sm:px-8 sm:pb-20 sm:pt-14">
            <section
                class="relative mb-8 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-8 shadow-premium-ink sm:mb-10 sm:px-8 sm:py-10"
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
                        class="mt-3 font-editorial text-[2.1rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.6rem]"
                    >
                        Artisans with real proof of work
                    </h1>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-white/65 sm:text-base">
                        Public pages built from finished jobs and reviews written by clients — not
                        self-written testimonials.
                    </p>
                </div>
            </section>

            <AppEmptyState
                v-if="!artisans.data?.length"
                icon="ti ti-search"
                title="No public pages yet"
                description="When artisans log finished jobs, they appear here for clients to find."
                cta-label="Create your free page"
                :cta-href="route('register')"
            />

            <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="artisan in artisans.data" :key="artisan.slug">
                    <Link
                        :href="artisan.url"
                        class="group flex h-full items-start gap-4 rounded-[1.5rem] bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-hover hover:ring-base-action/20 sm:p-5"
                    >
                        <span
                            class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-tint text-sm font-extrabold text-deep"
                        >
                            <img
                                v-if="artisan.avatar_url"
                                :src="artisan.avatar_url"
                                :alt="artisan.business_name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ initials(artisan.business_name) }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold tracking-tight text-ink">
                                {{ artisan.business_name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs font-medium text-ink/45">
                                {{ [artisan.trade, artisan.area_label].filter(Boolean).join(' · ') }}
                            </p>
                            <p class="mt-2 text-[11px] font-semibold text-ink/40">
                                {{ artisan.jobs_count }} job{{ artisan.jobs_count === 1 ? '' : 's' }}
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

            <div
                v-if="artisans.last_page > 1"
                class="mt-8 flex items-center justify-between rounded-2xl bg-white px-4 py-3 shadow-premium ring-1 ring-ink/[0.05]"
            >
                <Link
                    v-if="artisans.prev_page_url"
                    :href="artisans.prev_page_url"
                    class="tap-target text-sm font-semibold text-ink/55 hover:text-ink"
                >
                    Previous
                </Link>
                <span v-else class="text-sm font-semibold text-ink/25">Previous</span>
                <p class="text-xs font-semibold tabular-nums text-ink/40">
                    {{ artisans.current_page }} / {{ artisans.last_page }}
                </p>
                <Link
                    v-if="artisans.next_page_url"
                    :href="artisans.next_page_url"
                    class="tap-target text-sm font-semibold text-ink/55 hover:text-ink"
                >
                    Next
                </Link>
                <span v-else class="text-sm font-semibold text-ink/25">Next</span>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    artisans: { type: Object, required: true },
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const initials = (name) => {
    const parts = String(name || '')
        .trim()
        .split(/\s+/);
    if (parts.length >= 2) {
        return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
    }
    return (parts[0]?.[0] || 'I').toUpperCase();
};
</script>
