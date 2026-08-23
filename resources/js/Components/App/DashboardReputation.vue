<template>
    <section
        class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
        aria-labelledby="reputation-heading"
    >
        <div class="flex items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">
                    Reputation
                </p>
                <h2
                    id="reputation-heading"
                    class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink"
                >
                    What clients say
                </h2>
            </div>
            <Link
                :href="route('page.index')"
                class="tap-target shrink-0 rounded-xl px-2.5 py-1.5 text-xs font-bold text-base-action transition-colors hover:bg-tint"
            >
                My page
            </Link>
        </div>

        <!-- Has reviews -->
        <div v-if="hasReviews" class="px-5 pb-5 pt-4 sm:px-6 sm:pb-6">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
                <div class="flex items-center gap-3">
                    <p class="font-editorial text-[2.5rem] font-semibold leading-none tracking-tight text-ink">
                        {{ averageLabel }}
                    </p>
                    <div>
                        <StarDisplay :rating="reputation.average || 0" size="sm" />
                        <p class="mt-1 text-xs font-medium text-ink/45">
                            from {{ reputation.count }}
                            {{ reputation.count === 1 ? 'client review' : 'client reviews' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reply rate -->
            <div v-if="reputation.requests_sent > 0" class="mt-5">
                <div class="flex items-baseline justify-between gap-3">
                    <p class="text-xs font-bold tracking-tight text-ink/70">
                        {{ reputation.count }} of {{ reputation.requests_sent }} clients replied
                    </p>
                    <p class="text-xs font-bold tabular-nums text-base">
                        {{ reputation.response_rate }}%
                    </p>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-pale">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-base to-deep transition-[width] duration-700 ease-out"
                        :style="{ width: `${Math.max(reputation.response_rate, 3)}%` }"
                    />
                </div>
                <p v-if="reputation.awaiting > 0" class="mt-2 text-xs font-medium text-ink/45">
                    {{ reputation.awaiting }}
                    {{ reputation.awaiting === 1 ? 'client hasn’t' : 'clients haven’t' }}
                    replied yet — a nudge usually does it.
                </p>
            </div>

            <!-- Latest review -->
            <figure
                v-if="reputation.latest"
                class="mt-5 rounded-[1.25rem] bg-pale/80 px-4 py-4 ring-1 ring-ink/[0.05]"
            >
                <StarDisplay :rating="reputation.latest.rating" size="sm" empty-class="text-ink/15" />
                <blockquote
                    v-if="reputation.latest.comment"
                    class="mt-2.5 text-sm font-medium leading-relaxed text-ink/70"
                >
                    “{{ reputation.latest.comment }}”
                </blockquote>
                <figcaption class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-ink/45">
                    <span class="font-bold text-ink/60">{{ reputation.latest.client }}</span>
                    <span v-if="reputation.latest.time" aria-hidden="true">·</span>
                    <span v-if="reputation.latest.time">{{ reputation.latest.time }}</span>
                </figcaption>
                <Link
                    v-if="reputation.latest.job_uid"
                    :href="route('work-log.show', reputation.latest.job_uid)"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-base-action transition-colors hover:text-base-hover"
                >
                    <i class="ti ti-briefcase text-sm" aria-hidden="true" />
                    <span class="line-clamp-1">{{ reputation.latest.job || 'View job' }}</span>
                </Link>
            </figure>
        </div>

        <!-- No reviews yet -->
        <div v-else class="px-5 pb-5 pt-4 sm:px-6 sm:pb-6">
            <div class="rounded-[1.25rem] bg-pale/80 px-4 py-5 text-center ring-1 ring-ink/[0.05]">
                <span
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-ink/30 shadow-sm ring-1 ring-ink/[0.04]"
                >
                    <i class="ti ti-message-star" aria-hidden="true" />
                </span>
                <p class="mt-3.5 text-sm font-bold tracking-tight text-ink">
                    {{
                        reputation.requests_sent > 0
                            ? 'Waiting on your first reply'
                            : 'No client reviews yet'
                    }}
                </p>
                <p class="mx-auto mt-1 max-w-xs text-sm font-medium leading-relaxed text-ink/45">
                    {{
                        reputation.requests_sent > 0
                            ? 'You’ve sent review links — the first review will land here the moment a client submits it.'
                            : 'Log a finished job, then send the client a review link. Their words show up here and on your page.'
                    }}
                </p>
                <Link
                    :href="route('work-log.index')"
                    class="tap-target mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    {{ reputation.requests_sent > 0 ? 'Send a nudge' : 'Request a review' }}
                    <i class="ti ti-arrow-right text-sm" aria-hidden="true" />
                </Link>
            </div>
        </div>

        <!-- Review link allowance -->
        <div class="border-t border-ink/[0.05] bg-pale/50 px-5 py-4 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <div class="min-w-0">
                    <p class="text-xs font-bold tracking-tight text-ink/70">
                        {{ allowanceLabel }}
                    </p>
                    <p class="mt-0.5 text-xs font-medium text-ink/40">
                        {{ allowanceDetail }}
                    </p>
                </div>
                <Link
                    :href="route('tokens.index')"
                    class="tap-target shrink-0 rounded-xl bg-white px-3 py-2 text-xs font-bold text-deep ring-1 ring-ink/[0.06] transition-colors hover:bg-tint"
                >
                    {{ links.has_annual ? 'View plan' : 'Get more' }}
                </Link>
            </div>
            <div v-if="!links.has_annual" class="mt-3 h-1.5 overflow-hidden rounded-full bg-ink/[0.07]">
                <div
                    class="h-full rounded-full transition-[width] duration-700 ease-out"
                    :class="freeRemaining > 0 ? 'bg-base' : 'bg-coral'"
                    :style="{ width: `${freeUsedPercent}%` }"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import StarDisplay from '@/Components/Reviews/StarDisplay.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
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
});

const links = computed(() => props.reputation.links || {});
const hasReviews = computed(() => Number(props.reputation.count || 0) > 0);

const averageLabel = computed(() => {
    const value = Number(props.reputation.average || 0);
    return value > 0 ? value.toFixed(1) : '—';
});

const freeLimit = computed(() => Number(links.value.free_limit || 0));
const freeRemaining = computed(() => Number(links.value.free_remaining || 0));
const tokens = computed(() => Number(links.value.token_balance || 0));

const freeUsedPercent = computed(() => {
    if (freeLimit.value < 1) {
        return 0;
    }
    const used = Math.min(Number(links.value.free_used || 0), freeLimit.value);
    return Math.round((used / freeLimit.value) * 100);
});

const allowanceLabel = computed(() => {
    if (links.value.has_annual) {
        return 'Unlimited review links';
    }
    if (freeRemaining.value < 1) {
        return tokens.value > 0
            ? `Free links used — ${tokens.value} ${tokens.value === 1 ? 'token' : 'tokens'} left`
            : 'Free review links used up';
    }
    return `${freeRemaining.value} of ${freeLimit.value} free review links left`;
});

const allowanceDetail = computed(() => {
    if (links.value.has_annual) {
        return 'Annual plan — send as many as you need.';
    }
    if (freeRemaining.value < 1 && tokens.value < 1) {
        return `${links.value.reset_label || 'Resets next month'} · or buy tokens to keep sending`;
    }
    const parts = [links.value.reset_label || 'Resets next month'];
    if (tokens.value > 0) {
        parts.push(`${tokens.value} ${tokens.value === 1 ? 'token' : 'tokens'} in reserve`);
    }
    return parts.join(' · ');
});
</script>
