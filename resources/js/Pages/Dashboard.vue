<template>
    <Head title="Home" />

    <AuthenticatedLayout>
        <div class="space-y-5 sm:space-y-6">
            <!-- Identity header -->
            <section
                class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#0B1F3A] px-5 py-7 shadow-premium-ink sm:px-6 sm:py-8"
                aria-label="Welcome"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(50%_60%_at_100%_100%,rgba(255,106,61,0.12),transparent_50%)]"
                    aria-hidden="true"
                />

                <div class="relative flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="relative shrink-0">
                            <span
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-xl font-bold tracking-tight text-deep shadow-sm sm:h-[4.25rem] sm:w-[4.25rem] sm:text-[1.35rem]"
                            >
                                {{ user.initials || 'I' }}
                            </span>
                            <span
                                class="absolute bottom-0 right-0 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-[2.5px] ring-[#123B72]"
                                title="Verified Isabi artisan"
                            >
                                <i class="ti ti-check text-[11px] font-bold leading-none" aria-hidden="true" />
                                <span class="sr-only">Verified artisan</span>
                            </span>
                        </div>

                        <div class="min-w-0">
                            <p class="flex items-center gap-1.5 text-sm font-medium text-white/70">
                                <i
                                    :class="greetingIcon"
                                    class="text-[15px] text-white/75"
                                    aria-hidden="true"
                                />
                                <span>{{ greeting }}</span>
                            </p>
                            <h1 class="mt-1 truncate text-[1.75rem] font-bold leading-tight tracking-tight text-white sm:text-[2rem]">
                                {{ firstName || 'there' }}
                            </h1>
                            <div
                                v-if="user.trade || user.state"
                                class="mt-2.5 inline-flex max-w-full items-center gap-2 rounded-full bg-white/12 px-3 py-1.5 text-xs font-semibold text-white/90 ring-1 ring-white/15 backdrop-blur-sm"
                            >
                                <span
                                    v-if="user.trade"
                                    class="inline-flex min-w-0 items-center gap-1.5"
                                >
                                    <i class="ti ti-bolt text-[13px] text-white/70" aria-hidden="true" />
                                    <span class="truncate">{{ user.trade }}</span>
                                </span>
                                <span
                                    v-if="user.trade && user.state"
                                    class="text-white/35"
                                    aria-hidden="true"
                                >·</span>
                                <span
                                    v-if="user.state"
                                    class="inline-flex min-w-0 items-center gap-1.5"
                                >
                                    <i class="ti ti-map-pin text-[13px] text-white/70" aria-hidden="true" />
                                    <span class="truncate">{{ user.state }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2.5">
                        <Link
                            :href="route('credits.index')"
                            class="inline-flex items-center gap-1 rounded-md bg-white/12 px-2.5 py-1 text-[11px] font-semibold text-white/85 ring-1 ring-white/15 transition-colors hover:bg-white/18"
                            :title="glance.plan_detail || 'Your plan'"
                        >
                            {{ glance.plan }}
                            <i class="ti ti-dots text-xs text-white/55" aria-hidden="true" />
                        </Link>

                        <Link
                            :href="route('profile.edit')"
                            class="relative flex h-12 w-12 items-center justify-center"
                            :aria-label="`Profile ${page.completion}% complete`"
                            title="Complete your profile"
                        >
                            <svg class="h-12 w-12 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.5"
                                    fill="none"
                                    stroke="rgba(255,255,255,0.2)"
                                    stroke-width="2.75"
                                />
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.5"
                                    fill="none"
                                    stroke="#FFFFFF"
                                    stroke-width="2.75"
                                    stroke-linecap="round"
                                    :stroke-dasharray="profileRing.circumference"
                                    :stroke-dashoffset="profileRing.offset"
                                />
                            </svg>
                            <span
                                class="absolute inset-0 flex items-center justify-center text-[11px] font-bold tabular-nums text-white"
                            >
                                {{ page.completion }}%
                            </span>
                        </Link>
                    </div>
                </div>
            </section>

            <div class="h-px w-full bg-ink/[0.08]" aria-hidden="true" />

            <!-- Nudge -->
            <section v-if="nudge" aria-label="Attention needed">
                <div
                    class="flex flex-col gap-3 rounded-xl border px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                    :class="nudgeShellClass"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-base"
                            :class="nudgeIconClass"
                        >
                            <i :class="nudge.icon" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-tight text-ink">{{ nudge.title }}</p>
                            <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/55">
                                {{ nudge.body }}
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="nudge.cta_href"
                        class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        {{ nudge.cta_label }}
                        <i class="ti ti-arrow-right text-sm" aria-hidden="true" />
                    </Link>
                </div>
            </section>

            <!-- Glance -->
            <section aria-labelledby="glance-heading">
                <div class="mb-3">
                    <h2 id="glance-heading" class="text-base font-semibold tracking-tight text-ink sm:text-lg">
                        Here’s where you stand
                    </h2>
                    <p class="mt-0.5 text-sm font-medium text-ink/45">
                        A quick pulse before you dig in.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:gap-2.5 lg:grid-cols-4">
                    <article
                        v-for="stat in glanceCards"
                        :key="stat.label"
                        class="surface-card rounded-xl bg-white p-3.5 sm:p-4"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-pale text-sm text-ink/45">
                                <i :class="stat.icon" aria-hidden="true" />
                            </span>
                            <span
                                v-if="stat.badge"
                                class="rounded-full bg-tint px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-deep"
                            >
                                {{ stat.badge }}
                            </span>
                        </div>
                        <p class="mt-3 text-xl font-bold tracking-tight tabular-nums text-ink sm:text-2xl">
                            {{ stat.value }}
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-ink/45">
                            {{ stat.label }}
                        </p>
                        <p v-if="stat.detail" class="mt-0.5 text-xs font-medium text-ink/35">
                            {{ stat.detail }}
                        </p>
                    </article>
                </div>
            </section>

            <!-- Chart -->
            <section aria-label="Jobs trend">
                <JobsTrendChart :points="jobsChart" />
            </section>

            <!-- Primary actions -->
            <section aria-labelledby="actions-heading">
                <h2 id="actions-heading" class="sr-only">Primary actions</h2>
                <div class="mx-auto grid w-full max-w-[80%] gap-2.5 sm:grid-cols-2">
                    <Link
                        :href="route('work-log.create')"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-[#FF8A5B] via-coral to-[#C94C24] px-5 py-5 text-white shadow-[0_14px_36px_-14px_rgba(255,106,61,0.55)] transition-shadow duration-200 hover:shadow-[0_18px_40px_-14px_rgba(255,106,61,0.6)] sm:px-6 sm:py-6"
                    >
                        <div
                            class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"
                            aria-hidden="true"
                        />
                        <span class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-lg backdrop-blur-sm">
                            <i class="ti ti-plus" aria-hidden="true" />
                        </span>
                        <p class="relative mt-4 text-lg font-bold tracking-tight sm:text-xl">
                            Log a job
                        </p>
                        <p class="relative mt-1 text-sm font-medium leading-relaxed text-pretty text-white/85">
                            Record finished work, then send a review link to your client.
                        </p>
                        <span class="relative mt-4 inline-flex items-center gap-1.5 text-sm font-semibold">
                            Start entry
                            <i class="ti ti-arrow-right text-sm transition-transform duration-200 group-hover:translate-x-0.5" aria-hidden="true" />
                        </span>
                    </Link>

                    <Link
                        :href="route('page.index')"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-[#2F6FED] via-[#123B72] to-[#0B1F3A] px-5 py-5 text-white shadow-premium-ink transition-shadow duration-200 hover:shadow-premium-ink-hover sm:px-6 sm:py-6"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 bottom-0 h-36 w-36 rounded-full bg-base/40 blur-2xl"
                            aria-hidden="true"
                        />
                        <span class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-lg backdrop-blur-sm">
                            <i class="ti ti-share" aria-hidden="true" />
                        </span>
                        <p class="relative mt-4 text-lg font-bold tracking-tight sm:text-xl">
                            Share my page
                        </p>
                        <p class="relative mt-1 text-sm font-medium leading-relaxed text-pretty text-white/75">
                            Copy your link or QR — put it on WhatsApp, flyers, or your shop.
                        </p>
                        <span class="relative mt-4 inline-flex items-center gap-1.5 text-sm font-semibold">
                            Open my page
                            <i class="ti ti-arrow-right text-sm transition-transform duration-200 group-hover:translate-x-0.5" aria-hidden="true" />
                        </span>
                    </Link>

                    <Link
                        v-if="pendingReview"
                        :href="route('work-log.show', pendingReview.uid)"
                        class="group relative overflow-hidden rounded-xl bg-white px-5 py-5 text-ink shadow-premium ring-1 ring-ink/[0.06] transition-shadow duration-200 hover:shadow-premium-hover sm:col-span-2 sm:px-6 sm:py-6"
                    >
                        <span class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-tint text-lg text-deep">
                            <i class="ti ti-star" aria-hidden="true" />
                        </span>
                        <p class="relative mt-4 text-lg font-bold tracking-tight sm:text-xl">
                            Request a review
                        </p>
                        <p class="relative mt-1 text-sm font-medium leading-relaxed text-ink/55">
                            Send a WhatsApp link for “{{ pendingReview.description }}” — clients leave the review, not you.
                        </p>
                        <span class="relative mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-base-action">
                            Open job &amp; send link
                            <i class="ti ti-arrow-right text-sm transition-transform duration-200 group-hover:translate-x-0.5" aria-hidden="true" />
                        </span>
                    </Link>
                </div>
            </section>

            <!-- Destinations -->
            <section aria-labelledby="explore-heading">
                <div class="mb-3">
                    <h2 id="explore-heading" class="text-base font-semibold tracking-tight text-ink sm:text-lg">
                        Explore
                    </h2>
                    <p class="mt-0.5 text-sm font-medium text-ink/45">
                        The rest of your toolkit — one tap away.
                    </p>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 sm:gap-2.5 lg:grid-cols-4">
                    <Link
                        v-for="card in exploreCards"
                        :key="card.href"
                        :href="card.href"
                        class="surface-card group rounded-xl bg-white p-4"
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-pale text-base text-ink/45 transition-colors duration-200 group-hover:bg-tint group-hover:text-base">
                            <i :class="card.icon" aria-hidden="true" />
                        </span>
                        <p class="mt-3 text-sm font-semibold tracking-tight text-ink">{{ card.title }}</p>
                        <p class="mt-1 text-xs font-medium leading-relaxed text-ink/45">
                            {{ card.body }}
                        </p>
                    </Link>
                </div>
            </section>

            <!-- Recent activity -->
            <section class="pb-1" aria-labelledby="activity-heading">
                <div class="mb-3">
                    <h2 id="activity-heading" class="text-base font-semibold tracking-tight text-ink sm:text-lg">
                        Recent activity
                    </h2>
                    <p class="mt-0.5 text-sm font-medium text-ink/45">
                        Light momentum — not a full audit log.
                    </p>
                </div>

                <div class="overflow-hidden rounded-xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
                    <ul class="divide-y divide-ink/[0.06]">
                        <li
                            v-for="item in activity"
                            :key="item.id"
                            class="flex gap-3 px-4 py-3.5 transition-colors duration-200 hover:bg-pale/60 sm:px-5"
                        >
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-pale text-sm text-ink/40">
                                <i :class="item.icon" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                    <p class="text-sm font-semibold tracking-tight text-ink">{{ item.title }}</p>
                                    <p class="text-[11px] font-medium text-ink/35">{{ item.time }}</p>
                                </div>
                                <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/50">
                                    {{ item.body }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import JobsTrendChart from '@/Components/App/JobsTrendChart.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    greeting: { type: String, default: 'Welcome' },
    firstName: { type: String, default: '' },
    glance: {
        type: Object,
        default: () => ({
            jobs: 0,
            reviews: 0,
            credits: 0,
            plan: 'Free',
            plan_detail: '',
        }),
    },
    page: {
        type: Object,
        default: () => ({
            url: '',
            slug: '',
            completion: 0,
        }),
    },
    nudge: {
        type: Object,
        default: null,
    },
    jobsChart: {
        type: Array,
        default: () => [],
    },
    activity: {
        type: Array,
        default: () => [],
    },
    pendingReview: {
        type: Object,
        default: null,
    },
});

const pageProps = usePage();
const user = computed(() => pageProps.props.auth.user || {});

const greetingIcon = computed(() => {
    const g = (props.greeting || '').toLowerCase();
    if (g.includes('morning')) {
        return 'ti ti-sunrise';
    }
    if (g.includes('evening') || g.includes('night')) {
        return 'ti ti-moon-stars';
    }
    return 'ti ti-sun';
});

const profileRing = computed(() => {
    const radius = 15.5;
    const circumference = 2 * Math.PI * radius;
    const pct = Math.max(0, Math.min(100, Number(props.page.completion) || 0));
    return {
        circumference,
        offset: circumference - (pct / 100) * circumference,
    };
});

const glanceCards = computed(() => [
    {
        label: 'Jobs logged',
        value: props.glance.jobs,
        icon: 'ti ti-briefcase',
        detail: 'All time',
    },
    {
        label: 'Client reviews',
        value: props.glance.reviews,
        icon: 'ti ti-star',
        detail: 'From real clients',
    },
    {
        label: 'Credits',
        value: props.glance.credits,
        icon: 'ti ti-coins',
        badge: 'Balance',
        detail: 'For review links & more',
    },
    {
        label: 'Plan',
        value: props.glance.plan,
        icon: 'ti ti-crown',
        detail: props.glance.plan_detail,
    },
]);

const exploreCards = [
    {
        title: 'My page',
        body: 'Preview, share link, and QR for your public profile.',
        icon: 'ti ti-user-circle',
        href: route('page.index'),
    },
    {
        title: 'Work log',
        body: 'Past entries and new jobs in one place.',
        icon: 'ti ti-notebook',
        href: route('work-log.index'),
    },
    {
        title: 'Credits & plan',
        body: 'Balance, top-ups, and annual renewal status.',
        icon: 'ti ti-wallet',
        href: route('credits.index'),
    },
    {
        title: 'Referrals',
        body: 'Your code, invites, and credits earned.',
        icon: 'ti ti-gift',
        href: route('referrals.index'),
    },
];

const nudgeShellClass = computed(() => {
    if (props.nudge?.tone === 'coral') {
        return 'border-coral/25 bg-coral-tint/40';
    }
    return 'border-base/20 bg-tint/70';
});

const nudgeIconClass = computed(() => {
    if (props.nudge?.tone === 'coral') {
        return 'bg-white text-coral-deep shadow-sm';
    }
    return 'bg-white text-base shadow-sm';
});
</script>

<style scoped>
.surface-card {
    @apply shadow-premium ring-1 ring-ink/[0.06] transition-shadow duration-200 ease-out;
}

.surface-card:hover {
    @apply shadow-card-hover;
}
</style>
