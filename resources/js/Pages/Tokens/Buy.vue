<template>
    <Head title="Buy tokens" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl">
            <section
                class="buy-hero relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.16),transparent_50%)]"
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
                        <Link
                            :href="route('tokens.index')"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55 transition-colors hover:text-white"
                        >
                            <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                            Wallet
                        </Link>
                        <h1
                            class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]"
                        >
                            Buy tokens
                        </h1>
                        <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                            Pick a pack. Tokens never expire — use them whenever you need another review link.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/[0.07] px-4 py-3.5 ring-1 ring-white/10 sm:min-w-[11rem]"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">
                            Balance
                        </p>
                        <p class="mt-1 font-display text-2xl font-extrabold tabular-nums text-white">
                            {{ status.token_balance }}
                        </p>
                        <p class="mt-0.5 text-[11px] font-medium text-white/50">
                            <template v-if="status.has_annual">Unlimited free links</template>
                            <template v-else>{{ status.free_remaining }} free left</template>
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-3">
                <button
                    v-for="pack in packs"
                    :key="pack.key"
                    type="button"
                    class="pack-card relative flex flex-col rounded-[1.5rem] bg-white p-5 text-left shadow-premium ring-1 transition-all duration-200 sm:p-6"
                    :class="
                        selected === pack.key
                            ? 'ring-2 ring-base-action shadow-premium-hover -translate-y-0.5'
                            : 'ring-ink/[0.06] hover:ring-base/25 hover:-translate-y-0.5'
                    "
                    @click="selected = pack.key"
                >
                    <span
                        v-if="pack.popular"
                        class="absolute -top-2.5 right-4 rounded-full bg-coral px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                    >
                        Popular
                    </span>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-ink/40">
                        {{ pack.name }}
                    </p>
                    <p class="mt-3 font-display text-3xl font-extrabold tabular-nums text-ink">
                        {{ pack.tokens }}
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-ink/45">tokens</p>
                    <p class="mt-4 font-display text-xl font-bold tabular-nums text-deep">
                        {{ currencySymbol }}{{ formatPrice(pack.price) }}
                    </p>
                    <p class="mt-1 text-[11px] font-medium text-ink/40">
                        ~{{ currencySymbol }}{{ pack.per_token }} per token
                    </p>
                    <span
                        class="mt-5 inline-flex h-5 w-5 items-center justify-center rounded-full ring-2"
                        :class="
                            selected === pack.key
                                ? 'bg-base-action ring-base-action text-white'
                                : 'bg-white ring-ink/15'
                        "
                    >
                        <i
                            v-if="selected === pack.key"
                            class="ti ti-check text-[11px]"
                            aria-hidden="true"
                        />
                    </span>
                </button>
            </div>

            <div
                class="mt-6 rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-ink">
                            {{ selectedPack ? `${selectedPack.name} pack` : 'Choose a pack' }}
                        </p>
                        <p v-if="selectedPack" class="mt-1 text-sm font-medium text-ink/50">
                            {{ selectedPack.tokens }} tokens for
                            {{ currencySymbol }}{{ formatPrice(selectedPack.price) }}
                        </p>
                        <p v-if="instantFulfill" class="mt-2 text-xs font-medium text-ink/40">
                            Tokens are added to your wallet right after you confirm.
                        </p>
                    </div>
                    <FormButton
                        type="button"
                        variant="primary"
                        class="!min-h-12 !rounded-2xl !px-8 sm:!min-w-[12rem]"
                        icon-right="ti ti-check"
                        label="Confirm purchase"
                        :disabled="!selected"
                        :loading="form.processing"
                        loading-label="Processing…"
                        @click="submit"
                    />
                </div>
            </div>

            <p class="mt-5 text-center text-xs font-medium leading-relaxed text-ink/40">
                Each review link after your free monthly allowance costs
                {{ status.token_cost }} token. QR downloads and custom links may use tokens later.
            </p>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    status: { type: Object, required: true },
    packs: { type: Array, default: () => [] },
    currencySymbol: { type: String, default: '₦' },
    instantFulfill: { type: Boolean, default: true },
});

const popular = props.packs.find((p) => p.popular);
const selected = ref(popular?.key || props.packs[0]?.key || '');

const form = useForm({
    pack: selected.value,
});

const selectedPack = computed(() => props.packs.find((p) => p.key === selected.value));

const formatPrice = (value) =>
    Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });

const submit = () => {
    if (!selected.value) return;
    form.pack = selected.value;
    form.post(route('tokens.purchase'), { preserveScroll: true });
};
</script>

<style scoped>
.buy-hero {
    animation: rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.pack-card {
    animation: rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.pack-card:nth-child(2) {
    animation-delay: 0.05s;
}
.pack-card:nth-child(3) {
    animation-delay: 0.1s;
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
