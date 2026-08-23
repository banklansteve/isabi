<template>
    <Head title="Referrals" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl">
            <section
                class="referral-hero relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.18]"
                    style="
                        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                        background-size: 18px 18px;
                    "
                    aria-hidden="true"
                />

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Grow together
                        </p>
                        <h1
                            class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]"
                        >
                            Referrals
                        </h1>
                        <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                            Invite artisans you trust. When they log their first job, you earn tokens.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] transition-colors hover:bg-coral-deep disabled:opacity-50"
                        :disabled="!referralLink"
                        @click="copyLink"
                    >
                        <i :class="copied ? 'ti ti-check' : 'ti ti-copy'" aria-hidden="true" />
                        {{ copied ? 'Link copied' : 'Copy invite link' }}
                    </button>
                </div>

                <div class="relative mt-7 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3">
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="rounded-2xl bg-white/[0.07] px-3 py-3 ring-1 ring-white/10 sm:px-4 sm:py-3.5"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            {{ stat.label }}
                        </p>
                        <p class="mt-1 font-display text-xl font-extrabold tabular-nums text-white sm:text-2xl">
                            {{ stat.value }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">
                <section
                    class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                >
                    <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                        Your invite link
                    </h2>
                    <p class="mt-1 text-sm font-medium text-ink/50">
                        Share on WhatsApp, Instagram, or in person — anyone who signs up through this
                        link counts as your referral.
                    </p>

                    <div
                        class="mt-5 flex flex-col gap-2 rounded-2xl bg-pale p-3 ring-1 ring-ink/[0.05] sm:flex-row sm:items-center"
                    >
                        <p class="min-w-0 flex-1 truncate px-2 text-sm font-semibold text-ink">
                            {{ referralLink || 'Your invite link will appear here' }}
                        </p>
                        <button
                            type="button"
                            class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-base-hover disabled:opacity-40"
                            :disabled="!referralLink"
                            @click="copyLink"
                        >
                            <i :class="copied ? 'ti ti-check' : 'ti ti-link'" aria-hidden="true" />
                            {{ copied ? 'Copied' : 'Copy' }}
                        </button>
                    </div>

                    <div
                        v-if="code"
                        class="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-tint/60 px-4 py-3.5 ring-1 ring-base/10"
                    >
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/40">
                                Referral code
                            </p>
                            <p class="mt-1 font-display text-lg font-extrabold tracking-wide text-deep">
                                {{ code }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="tap-target inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-bold text-ink ring-1 ring-ink/[0.08] transition hover:bg-pale"
                            @click="copyCode"
                        >
                            <i :class="codeCopied ? 'ti ti-check' : 'ti ti-copy'" aria-hidden="true" />
                            {{ codeCopied ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                </section>

                <section
                    class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                >
                    <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                        How it works
                    </h2>
                    <ol class="mt-4 space-y-4">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.title"
                            class="flex gap-3"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-tint text-xs font-bold text-deep"
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="min-w-0 pt-0.5">
                                <p class="text-sm font-bold text-ink">{{ step.title }}</p>
                                <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/55">
                                    {{ step.body }}
                                </p>
                            </div>
                        </li>
                    </ol>
                </section>
            </div>

            <section
                class="mt-5 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
            >
                <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                    <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                        People you’ve invited
                    </h2>
                    <p class="mt-0.5 text-xs font-medium text-ink/45">
                        Signups and first jobs from your link show here.
                    </p>
                </div>

                <ul v-if="invitees.length" class="divide-y divide-ink/[0.05]">
                    <li
                        v-for="person in invitees"
                        :key="person.id"
                        class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ person.name }}</p>
                            <p class="mt-0.5 text-xs font-medium text-ink/40">
                                Joined {{ person.joined_label }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p
                                class="text-xs font-bold"
                                :class="
                                    person.status === 'rewarded'
                                        ? 'text-emerald-700'
                                        : 'text-ink/50'
                                "
                            >
                                {{ person.status_label }}
                            </p>
                            <p
                                v-if="person.reward_tokens"
                                class="mt-0.5 text-[11px] font-semibold tabular-nums text-ink/40"
                            >
                                +{{ person.reward_tokens }} tokens
                            </p>
                        </div>
                    </li>
                </ul>
                <div v-else class="p-4 sm:p-5">
                    <AppEmptyState
                        icon="ti ti-gift"
                        title="No referrals yet"
                        description="Share your invite link with fellow artisans. When they join and log their first job, they’ll show up here — and you’ll earn tokens."
                    />
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { copyToClipboard } from '@/utils/clipboard';
import { toast } from '@/utils/toast';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    referralLink: { type: String, default: '' },
    code: { type: String, default: '' },
    rewardTokens: { type: Number, default: 5 },
    invitedCount: { type: Number, default: 0 },
    earnedTokens: { type: Number, default: 0 },
    invitees: { type: Array, default: () => [] },
});

const copied = ref(false);
const codeCopied = ref(false);
const linkTimer = { id: null };
const codeTimer = { id: null };

const stats = computed(() => [
    { label: 'Invited', value: props.invitedCount },
    { label: 'Earned', value: props.earnedTokens },
    { label: 'Reward', value: props.rewardTokens },
]);

const steps = computed(() => [
    {
        title: 'Share your link',
        body: 'Send it to artisans who should be on Isabi.',
    },
    {
        title: 'They join & log a job',
        body: 'The reward unlocks when their first job is logged.',
    },
    {
        title: `You earn ${props.rewardTokens} tokens`,
        body: 'Added to your wallet — they don’t expire.',
    },
]);

const copyText = async (value, flag, timer, successMessage) => {
    if (!value) return;
    const ok = await copyToClipboard(value);
    if (!ok) {
        toast('Couldn’t copy automatically. Select the text and copy it.', 'error');
        return;
    }
    flag.value = true;
    toast({ type: 'success', title: 'Copied', message: successMessage, duration: 3200 });
    clearTimeout(timer.id);
    timer.id = setTimeout(() => {
        flag.value = false;
    }, 2000);
};

const copyLink = () =>
    copyText(props.referralLink, copied, linkTimer, 'Invite link ready to paste.');
const copyCode = () =>
    copyText(props.code, codeCopied, codeTimer, 'Referral code ready to paste.');

onBeforeUnmount(() => {
    clearTimeout(linkTimer.id);
    clearTimeout(codeTimer.id);
});
</script>

<style scoped>
.referral-hero {
    animation: rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes rise {
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
