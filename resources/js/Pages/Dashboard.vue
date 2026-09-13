<template>
    <Head title="Home" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl space-y-5 sm:space-y-6">
            <!-- Identity hero -->
            <section
                class="dash-hero relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:px-7 sm:py-8"
                aria-label="Welcome"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(50%_60%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.16]"
                    style="
                        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                        background-size: 18px 18px;
                    "
                    aria-hidden="true"
                />

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="relative shrink-0">
                            <UserAvatar
                                :src="user.avatar_url"
                                :initials="user.initials || 'I'"
                                :alt="user.name || 'Profile'"
                                size="hero"
                                class="bg-white shadow-sm"
                            />
                            <span
                                class="absolute bottom-0 right-0 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-[2.5px] ring-[#123B72]"
                                title="Verified Kraftrack artisan"
                            >
                                <i class="ti ti-check text-[11px] font-bold leading-none" aria-hidden="true" />
                                <span class="sr-only">Verified artisan</span>
                            </span>
                        </div>

                        <div class="min-w-0">
                            <p class="flex items-center gap-1.5 text-sm font-medium text-white/65">
                                <i :class="greetingIcon" class="text-[15px] text-white/70" aria-hidden="true" />
                                <span>{{ greeting }}</span>
                            </p>
                            <h1
                                class="mt-1 truncate font-editorial text-[1.85rem] font-semibold leading-[1.12] tracking-tight text-white sm:text-[2.25rem]"
                            >
                                {{ firstName || 'there' }}
                            </h1>
                            <div
                                v-if="user.trade || user.state"
                                class="mt-2.5 inline-flex max-w-full items-center gap-2 rounded-full bg-white/12 px-3 py-1.5 text-xs font-semibold text-white/90 ring-1 ring-white/15"
                            >
                                <span v-if="user.trade" class="inline-flex min-w-0 items-center gap-1.5">
                                    <i class="ti ti-bolt text-[13px] text-white/70" aria-hidden="true" />
                                    <span class="truncate">{{ user.trade }}</span>
                                </span>
                                <span v-if="user.trade && user.state" class="text-white/35" aria-hidden="true">·</span>
                                <span v-if="user.state" class="inline-flex min-w-0 items-center gap-1.5">
                                    <i class="ti ti-map-pin text-[13px] text-white/70" aria-hidden="true" />
                                    <span class="truncate">{{ user.state }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <Link
                            :href="route('tokens.index')"
                            class="inline-flex items-center gap-1.5 rounded-2xl bg-white/10 px-3.5 py-2.5 text-xs font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/16"
                            :title="glance.plan_detail || 'Your plan'"
                        >
                            <i class="ti ti-crown text-sm text-coral" aria-hidden="true" />
                            {{ glance.plan }}
                        </Link>
                    </div>
                </div>

                <div class="relative mt-7 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                    <Link
                        v-for="stat in heroStats"
                        :key="stat.label"
                        :href="stat.href"
                        :title="stat.title || undefined"
                        class="rounded-2xl bg-white/[0.07] px-3 py-3 ring-1 ring-white/10 transition-colors hover:bg-white/[0.11] sm:px-4 sm:py-3.5"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            {{ stat.label }}
                        </p>
                        <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-white sm:text-2xl">
                            {{ stat.value }}
                        </p>
                        <p v-if="stat.detail" class="mt-0.5 truncate text-[11px] font-medium text-white/45">
                            {{ stat.detail }}
                        </p>
                    </Link>
                </div>
            </section>

            <!-- Nudge -->
            <section v-if="nudge" aria-label="Attention needed" class="dash-rise">
                <div
                    class="flex flex-col gap-3 rounded-[1.35rem] border px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                    :class="nudgeShellClass"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-base"
                            :class="nudgeIconClass"
                        >
                            <i :class="nudge.icon" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold tracking-tight text-ink">{{ nudge.title }}</p>
                            <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/55">
                                {{ nudge.body }}
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="nudge.cta_href"
                        class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        {{ nudge.cta_label }}
                        <i class="ti ti-arrow-right text-sm" aria-hidden="true" />
                    </Link>
                </div>
            </section>

            <!-- Due reminders -->
            <section
                v-if="dueReminders.length"
                class="dash-rise overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                aria-label="Review reminders due"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-ink/[0.05] bg-gradient-to-r from-amber-50/80 to-white px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-coral-deep">
                            Reminders due
                        </p>
                        <h2 class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink">
                            Nudge clients who haven’t reviewed
                        </h2>
                    </div>
                    <Link
                        :href="route('work-log.index')"
                        class="text-xs font-bold text-base-action hover:text-base-hover"
                    >
                        Work log
                    </Link>
                </div>
                <ul class="divide-y divide-ink/[0.05] px-5 sm:px-6">
                    <li
                        v-for="item in dueReminders"
                        :key="item.uid"
                        class="flex items-center justify-between gap-3 py-3.5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">
                                {{ item.description }}
                            </p>
                            <p class="mt-0.5 text-xs font-medium text-ink/45">
                                {{ item.client_name || 'Client' }}
                                <span v-if="item.requested_label"> · asked {{ item.requested_label }}</span>
                            </p>
                        </div>
                        <Link
                            :href="route('work-log.show', item.uid)"
                            class="tap-target shrink-0 rounded-xl bg-pale px-3 py-2 text-xs font-bold text-deep ring-1 ring-ink/[0.06] transition-colors hover:bg-tint"
                        >
                            Send nudge
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Primary actions -->
            <section aria-labelledby="actions-heading" class="dash-rise">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <h2
                            id="actions-heading"
                            class="font-editorial text-lg font-semibold tracking-tight text-ink sm:text-xl"
                        >
                            What next?
                        </h2>
                        <p class="mt-0.5 text-sm font-medium text-ink/45">
                            Three moves that grow your reputation.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <Link
                        :href="route('work-log.create')"
                        class="group relative flex items-center gap-4 overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#FF8A5B] via-coral to-[#C94C24] px-4 py-4 text-white shadow-[0_14px_36px_-14px_rgba(255,106,61,0.55)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-14px_rgba(255,106,61,0.6)] sm:block sm:px-5 sm:py-6"
                    >
                        <div
                            class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"
                            aria-hidden="true"
                        />
                        <span
                            class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl backdrop-blur-sm"
                        >
                            <i class="ti ti-plus" aria-hidden="true" />
                        </span>
                        <span class="relative block min-w-0 flex-1">
                            <span
                                class="block font-editorial text-[1.0625rem] font-semibold tracking-tight sm:mt-5 sm:text-xl"
                            >
                                Log a job
                            </span>
                            <span
                                class="mt-0.5 line-clamp-2 block text-xs font-medium leading-relaxed text-pretty text-white/85 sm:mt-1.5 sm:line-clamp-none sm:text-sm"
                            >
                                Record finished work, then send a review link to your client.
                            </span>
                            <span
                                class="mt-5 hidden items-center gap-1.5 text-sm font-bold sm:inline-flex"
                            >
                                Start entry
                                <i
                                    class="ti ti-arrow-right text-sm transition-transform duration-200 group-hover:translate-x-0.5"
                                    aria-hidden="true"
                                />
                            </span>
                        </span>
                        <i
                            class="ti ti-chevron-right relative shrink-0 text-lg text-white/55 sm:hidden"
                            aria-hidden="true"
                        />
                    </Link>

                    <div
                        class="relative overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#2F6FED] via-[#123B72] to-[#0B1F3A] px-4 py-4 text-white shadow-premium-ink sm:px-5 sm:py-6"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 bottom-0 h-36 w-36 rounded-full bg-base/40 blur-2xl"
                            aria-hidden="true"
                        />
                        <div class="relative flex items-center gap-4 sm:block">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-xl backdrop-blur-sm"
                            >
                                <i class="ti ti-qrcode" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="font-editorial text-[1.0625rem] font-semibold tracking-tight sm:mt-5 sm:text-xl"
                                >
                                    Copy link &amp; QR
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs font-medium text-white/70 sm:mt-1.5 sm:text-sm"
                                    :title="page.url"
                                >
                                    {{ prettyPageUrl }}
                                </p>
                            </div>
                        </div>
                        <div class="relative mt-3.5 flex gap-2 sm:mt-5">
                            <button
                                type="button"
                                class="tap-target inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-white/12 px-3 py-2.5 text-xs font-bold ring-1 ring-white/15 transition-colors hover:bg-white/20 sm:text-sm"
                                @click="copyPageLink"
                            >
                                <i
                                    :class="copied ? 'ti ti-check' : 'ti ti-link'"
                                    class="text-sm"
                                    aria-hidden="true"
                                />
                                {{ copied ? 'Copied' : 'Copy link' }}
                            </button>
                            <button
                                type="button"
                                class="tap-target inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-white/12 px-3 py-2.5 text-xs font-bold ring-1 ring-white/15 transition-colors hover:bg-white/20 sm:text-sm"
                                @click="qrOpen = true"
                            >
                                <i class="ti ti-qrcode text-sm" aria-hidden="true" />
                                QR code
                            </button>
                        </div>
                    </div>

                    <Link
                        :href="
                            pendingReview
                                ? route('work-log.show', pendingReview.uid)
                                : route('work-log.index')
                        "
                        class="group relative flex items-center gap-4 overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#1A4FB5] via-[#2F6FED] to-[#123B72] px-4 py-4 text-white shadow-[0_14px_36px_-14px_rgba(26,79,181,0.45)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-14px_rgba(26,79,181,0.55)] sm:block sm:px-5 sm:py-6"
                    >
                        <div
                            class="pointer-events-none absolute -left-8 top-0 h-32 w-32 rounded-full bg-white/10 blur-2xl"
                            aria-hidden="true"
                        />
                        <span
                            class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl backdrop-blur-sm"
                        >
                            <i class="ti ti-star" aria-hidden="true" />
                        </span>
                        <span class="relative block min-w-0 flex-1">
                            <span
                                class="block font-editorial text-[1.0625rem] font-semibold tracking-tight sm:mt-5 sm:text-xl"
                            >
                                Request a review
                            </span>
                            <span
                                class="mt-0.5 line-clamp-2 block text-xs font-medium leading-relaxed text-pretty text-white/80 sm:mt-1.5 sm:line-clamp-none sm:text-sm"
                            >
                                {{
                                    pendingReview
                                        ? `Send a WhatsApp link for “${pendingReview.description}”.`
                                        : 'Open a logged job and send a private review link to your client.'
                                }}
                            </span>
                            <span
                                class="mt-5 hidden items-center gap-1.5 text-sm font-bold sm:inline-flex"
                            >
                                {{ pendingReview ? 'Open job & send' : 'Open work log' }}
                                <i
                                    class="ti ti-arrow-right text-sm transition-transform duration-200 group-hover:translate-x-0.5"
                                    aria-hidden="true"
                                />
                            </span>
                        </span>
                        <i
                            class="ti ti-chevron-right relative shrink-0 text-lg text-white/55 sm:hidden"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </section>

            <!-- Reputation -->
            <section class="dash-rise">
                <DashboardReputation :reputation="reputation" />
            </section>

            <!-- Chart -->
            <section aria-label="Jobs trend" class="dash-rise">
                <JobsTrendChart :points="jobsChart" />
            </section>

            <!-- Shortcuts + activity -->
            <div class="grid gap-5 lg:grid-cols-5 lg:gap-6">
                <!-- Desktop only: the mobile bottom bar already covers these destinations -->
                <section class="dash-rise hidden lg:col-span-2 lg:block" aria-labelledby="explore-heading">
                    <div class="mb-3">
                        <h2
                            id="explore-heading"
                            class="font-editorial text-lg font-semibold tracking-tight text-ink"
                        >
                            Shortcuts
                        </h2>
                        <p class="mt-0.5 text-sm font-medium text-ink/45">
                            Jump straight to what you need.
                        </p>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                        <Link
                            v-for="card in exploreCards"
                            :key="card.href"
                            :href="card.href"
                            class="group flex items-center gap-3.5 rounded-[1.25rem] bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.06] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-premium-hover"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-pale text-base text-ink/45 transition-colors duration-200 group-hover:bg-tint group-hover:text-base"
                            >
                                <i :class="card.icon" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold tracking-tight text-ink">{{ card.title }}</p>
                                <p class="mt-0.5 truncate text-xs font-medium text-ink/45">
                                    {{ card.body }}
                                </p>
                            </div>
                            <i
                                class="ti ti-chevron-right shrink-0 text-ink/20 transition-colors group-hover:text-base-action"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>
                </section>

                <section class="dash-rise pb-1 lg:col-span-3" aria-labelledby="activity-heading">
                    <div class="mb-3">
                        <h2
                            id="activity-heading"
                            class="font-editorial text-lg font-semibold tracking-tight text-ink"
                        >
                            Recent activity
                        </h2>
                        <p class="mt-0.5 text-sm font-medium text-ink/45">
                            Light momentum — not a full audit log.
                        </p>
                    </div>

                    <div
                        v-if="activity.length"
                        class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                    >
                        <ul class="divide-y divide-ink/[0.05]">
                            <li
                                v-for="item in activity"
                                :key="item.id"
                                class="flex gap-3 px-4 py-3.5 transition-colors duration-200 hover:bg-pale/60 sm:px-5"
                            >
                                <span
                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-pale text-sm text-ink/40"
                                >
                                    <i :class="item.icon" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                        <p class="text-sm font-semibold tracking-tight text-ink">
                                            {{ item.title }}
                                        </p>
                                        <p class="text-[11px] font-medium text-ink/35">{{ item.time }}</p>
                                    </div>
                                    <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/50">
                                        {{ item.body }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <AppEmptyState
                        v-else
                        icon="ti ti-sparkles"
                        title="Your story starts here"
                        description="Log a job, share your page, or send a review request — activity will show up here as you go."
                        cta-label="Log a job"
                        :cta-href="route('work-log.create')"
                    />
                </section>
            </div>
        </div>

        <ProfileQrModal
            :show="qrOpen"
            :url="page.url || ''"
            :filename="`Kraftrack-${page.slug || 'page'}-qr`"
            :business-name="page.business_name || ''"
            :trade="page.trade || ''"
            @close="qrOpen = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import DashboardReputation from '@/Components/App/DashboardReputation.vue';
import JobsTrendChart from '@/Components/App/JobsTrendChart.vue';
import UserAvatar from '@/Components/App/UserAvatar.vue';
import ProfileQrModal from '@/Components/Public/ProfileQrModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { copyToClipboard } from '@/utils/clipboard';
import { toast } from '@/utils/toast';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    greeting: { type: String, default: 'Welcome' },
    firstName: { type: String, default: '' },
    glance: {
        type: Object,
        default: () => ({
            jobs: 0,
            reviews: 0,
            views: 0,
            views_label: '0',
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
            business_name: '',
            trade: '',
        }),
    },
    reputation: {
        type: Object,
        default: () => ({
            average: null,
            count: 0,
            requests_sent: 0,
            awaiting: 0,
            response_rate: 0,
            latest: null,
            links: {},
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
    dueReminders: {
        type: Array,
        default: () => [],
    },
});

const pageProps = usePage();
const user = computed(() => pageProps.props.auth.user || {});

const qrOpen = ref(false);
const copied = ref(false);
let copyTimer = null;

const prettyPageUrl = computed(() =>
    String(props.page.url || '').replace(/^https?:\/\//, '').replace(/\/$/, ''),
);

const copyPageLink = async () => {
    const ok = await copyToClipboard(props.page.url);

    if (!ok) {
        toast('Could not copy the link. Try again.', 'error');
        return;
    }

    copied.value = true;
    toast('Page link copied — paste it anywhere.');
    window.clearTimeout(copyTimer);
    copyTimer = window.setTimeout(() => {
        copied.value = false;
    }, 2200);
};

onBeforeUnmount(() => window.clearTimeout(copyTimer));

const greetingIcon = computed(() => {
    const g = (props.greeting || '').toLowerCase();
    if (g.includes('morning')) return 'ti ti-sunrise';
    if (g.includes('evening') || g.includes('night')) return 'ti ti-moon-stars';
    return 'ti ti-sun';
});

const heroStats = computed(() => [
    {
        label: 'Jobs',
        value: props.glance.jobs,
        detail: 'Logged',
        href: route('work-log.index'),
    },
    {
        label: 'Reviews',
        value: props.glance.reviews,
        detail: 'From clients',
        href: route('work-log.index'),
    },
    {
        label: 'Views',
        value: props.glance.views_label || '0',
        detail: 'Public page',
        href: route('page.index'),
        title: `${Number(props.glance.views || 0).toLocaleString()} total page views`,
    },
    {
        label: 'Tokens',
        value: props.glance.credits,
        detail: 'Balance',
        href: route('tokens.index'),
    },
]);

const exploreCards = [
    {
        title: 'My page',
        body: 'Preview, share link, and QR',
        icon: 'ti ti-user-circle',
        href: route('page.index'),
    },
    {
        title: 'Work log',
        body: 'Past entries and new jobs',
        icon: 'ti ti-notebook',
        href: route('work-log.index'),
    },
    {
        title: 'Tokens',
        body: 'Balance, packs, free links',
        icon: 'ti ti-coin',
        href: route('tokens.index'),
    },
    {
        title: 'Account',
        body: 'Profile, messages, security',
        icon: 'ti ti-settings',
        href: route('profile.edit'),
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
        return 'bg-white text-coral-deep shadow-sm ring-1 ring-coral/15';
    }
    return 'bg-white text-base shadow-sm ring-1 ring-base/15';
});
</script>

<style scoped>
.dash-hero {
    animation: dash-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.dash-rise {
    animation: dash-rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: 0.06s;
}

@keyframes dash-rise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
