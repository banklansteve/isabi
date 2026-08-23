<template>
    <Head title="Tokens" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl">
            <section
                class="token-hero relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
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
                            Wallet
                        </p>
                        <h1
                            class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]"
                        >
                            Tokens
                        </h1>
                        <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                            Free review links reset every month. Tokens cover the extras — simple and fair.
                        </p>
                    </div>
                    <Link
                        :href="route('tokens.buy')"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] transition-colors hover:bg-coral-deep"
                    >
                        <i class="ti ti-shopping-bag" aria-hidden="true" />
                        Buy tokens
                    </Link>
                </div>

                <div class="relative mt-7 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3">
                    <div class="rounded-2xl bg-white/[0.07] px-3 py-3 ring-1 ring-white/10 sm:px-4 sm:py-3.5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            Balance
                        </p>
                        <p class="mt-1 font-display text-xl font-extrabold tabular-nums text-white sm:text-2xl">
                            {{ status.token_balance }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.07] px-3 py-3 ring-1 ring-white/10 sm:px-4 sm:py-3.5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            Free left
                        </p>
                        <p class="mt-1 font-display text-xl font-extrabold tabular-nums text-white sm:text-2xl">
                            <template v-if="status.has_annual">∞</template>
                            <template v-else>{{ status.free_remaining }}/{{ status.free_limit }}</template>
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.07] px-3 py-3 ring-1 ring-white/10 sm:px-4 sm:py-3.5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            Plan
                        </p>
                        <p class="mt-1 font-display text-xl font-extrabold text-white sm:text-2xl">
                            {{ status.plan }}
                        </p>
                        <p class="mt-0.5 text-[11px] font-medium text-white/50">
                            {{ status.period_label }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="mb-5 rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
            >
                <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                    How review links work
                </h2>
                <ul class="mt-4 space-y-3">
                    <li class="flex gap-3 text-sm font-medium text-ink/65">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-tint text-xs font-bold text-deep"
                        >1</span>
                        <span>
                            You get
                            <strong class="font-bold text-ink">{{ status.free_limit }} free</strong>
                            review requests each month.
                        </span>
                    </li>
                    <li class="flex gap-3 text-sm font-medium text-ink/65">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-tint text-xs font-bold text-deep"
                        >2</span>
                        <span>
                            After that, each new request costs
                            <strong class="font-bold text-ink">{{ actions.review_link }} token</strong>.
                        </span>
                    </li>
                    <li class="flex gap-3 text-sm font-medium text-ink/65">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-tint text-xs font-bold text-deep"
                        >3</span>
                        <span>Resending the same job’s link doesn’t spend another token.</span>
                    </li>
                </ul>

                <div
                    v-if="!status.has_annual && status.free_remaining === 0"
                    class="mt-5 flex flex-col gap-3 rounded-2xl bg-amber-50 px-4 py-3.5 ring-1 ring-amber-200/70 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm font-semibold text-amber-950">
                        Free links used up for {{ status.period_label }}.
                    </p>
                    <Link
                        :href="route('tokens.buy')"
                        class="tap-target shrink-0 rounded-xl bg-amber-950 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-ink"
                    >
                        Get tokens
                    </Link>
                </div>
            </section>

            <!-- History -->
            <section
                class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
            >
                <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                    <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                        Activity
                    </h2>
                    <p class="mt-0.5 text-xs font-medium text-ink/45">
                        Purchases and tokens spent on review links.
                    </p>
                </div>

                <ul v-if="transactions.length" class="divide-y divide-ink/[0.05]">
                    <li
                        v-for="tx in transactions"
                        :key="tx.id"
                        class="flex items-start justify-between gap-4 px-5 py-4 sm:px-6"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">
                                {{ tx.description }}
                            </p>
                            <p class="mt-0.5 text-xs font-medium text-ink/40">
                                {{ tx.created_label }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p
                                class="text-sm font-bold tabular-nums"
                                :class="tx.type === 'credit' ? 'text-emerald-700' : 'text-ink'"
                            >
                                {{ tx.type === 'credit' ? '+' : '−' }}{{ tx.amount }}
                            </p>
                            <p class="mt-0.5 text-[11px] font-medium tabular-nums text-ink/35">
                                bal {{ tx.balance_after }}
                            </p>
                        </div>
                    </li>
                </ul>
                <div v-else class="p-4 sm:p-5">
                    <AppEmptyState
                        icon="ti ti-receipt"
                        title="No token activity yet"
                        description="Your free monthly review links don’t show here — only token purchases and spends do."
                        cta-label="Buy tokens"
                        :cta-href="route('tokens.buy')"
                    />
                </div>
            </section>

            <section
                v-if="purchases.length"
                class="mt-5 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
            >
                <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                    <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                        Purchases
                    </h2>
                </div>
                <ul class="divide-y divide-ink/[0.05]">
                    <li
                        v-for="p in purchases"
                        :key="p.id"
                        class="flex items-center justify-between gap-4 px-5 py-3.5 sm:px-6"
                    >
                        <div>
                            <p class="text-sm font-semibold text-ink">
                                {{ p.pack_name }} · {{ p.tokens }} tokens
                            </p>
                            <p class="mt-0.5 text-xs font-medium text-ink/40">
                                {{ p.created_label }} · {{ p.reference }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold tabular-nums text-ink">
                                {{ currencySymbol }}{{ formatPrice(p.price) }}
                            </p>
                            <p
                                class="mt-0.5 text-[11px] font-semibold capitalize"
                                :class="p.status === 'completed' ? 'text-emerald-700' : 'text-amber-700'"
                            >
                                {{ p.status }}
                            </p>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppEmptyState from '@/Components/App/AppEmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: { type: Object, required: true },
    transactions: { type: Array, default: () => [] },
    purchases: { type: Array, default: () => [] },
    currencySymbol: { type: String, default: '₦' },
    actions: {
        type: Object,
        default: () => ({ review_link: 1, qr_download: 1, vanity_slug: 5 }),
    },
});

const formatPrice = (value) =>
    Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
</script>

<style scoped>
.token-hero {
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
