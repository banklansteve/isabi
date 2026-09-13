<template>
    <Head :title="`Quote from ${business.business_name}`" />

    <div class="quote-public min-h-dvh font-app text-ink antialiased">
        <div
            class="pointer-events-none fixed inset-0 bg-[radial-gradient(70%_55%_at_12%_-10%,rgba(26,79,181,0.14),transparent_55%),radial-gradient(50%_40%_at_100%_0%,rgba(255,106,61,0.08),transparent_45%)]"
            aria-hidden="true"
        />

        <main class="relative mx-auto w-full max-w-3xl px-4 py-8 sm:px-6 sm:py-12 lg:py-14">
            <header class="mb-6 flex items-center justify-between gap-3 sm:mb-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action/80">
                    Official quote
                </p>
                <p class="text-[11px] font-semibold text-ink/35">
                    Powered by {{ appName }}
                </p>
            </header>

            <article class="overflow-hidden rounded-[1.75rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <!-- Brand masthead -->
                <div class="relative border-b border-ink/[0.05] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-6 py-8 sm:px-8 sm:py-10">
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%)]"
                        aria-hidden="true"
                    />

                    <div class="relative flex flex-col items-start gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-[0_12px_28px_-12px_rgba(0,0,0,0.45)] ring-1 ring-white/30 sm:h-[4.5rem] sm:w-[4.5rem]"
                            >
                                <img
                                    v-if="business.logo_url"
                                    :src="business.logo_url"
                                    :alt="`${business.business_name} logo`"
                                    class="h-full w-full object-contain p-1.5"
                                />
                                <span
                                    v-else
                                    class="font-editorial text-xl font-semibold text-base-action"
                                    aria-hidden="true"
                                >
                                    {{ businessInitials }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate font-editorial text-xl font-semibold tracking-tight text-white sm:text-2xl">
                                    {{ business.business_name }}
                                </p>
                                <p class="mt-1 truncate text-sm font-medium text-white/65">
                                    <span v-if="business.trade">{{ business.trade }}</span>
                                    <span v-if="business.trade && business.area" class="text-white/35"> · </span>
                                    <span v-if="business.area">{{ business.area }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 font-mono text-xs font-bold text-white ring-1 ring-white/15"
                            >
                                <i class="ti ti-hash text-sm text-white/50" aria-hidden="true" />
                                {{ quote.quote_number }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 px-5 py-6 sm:px-8 sm:py-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                                Prepared for {{ request.client_name }}
                            </p>
                            <h1 class="mt-2 font-editorial text-[1.65rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                                {{ request.subject }}
                            </h1>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-if="quote.valid_until"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/55 ring-1 ring-ink/[0.06]"
                                >
                                    <i class="ti ti-calendar-event text-[12px]" aria-hidden="true" />
                                    Valid until {{ quote.valid_until }}
                                </span>
                                <span
                                    v-if="quote.estimated_start"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/55 ring-1 ring-ink/[0.06]"
                                >
                                    <i class="ti ti-flag text-[12px]" aria-hidden="true" />
                                    Est. start {{ quote.estimated_start }}
                                </span>
                            </div>
                        </div>

                        <div class="shrink-0 rounded-2xl bg-tint/70 px-5 py-4 ring-1 ring-base/10 sm:text-right">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action/70">
                                Total
                            </p>
                            <p class="mt-1 text-3xl font-bold tracking-tight text-base-action sm:text-[2.15rem]">
                                {{ formatNaira(quote.total_naira) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <a
                            v-if="pdf_url"
                            :href="pdf_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition hover:bg-base-hover"
                        >
                            <i class="ti ti-file-type-pdf text-base" aria-hidden="true" />
                            Download PDF
                        </a>
                        <a
                            v-if="whatsappHref"
                            :href="whatsappHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3.5 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition hover:bg-tint"
                        >
                            <i class="ti ti-brand-whatsapp text-base" aria-hidden="true" />
                            Message {{ business.business_name }}
                        </a>
                    </div>

                    <p
                        v-if="quote.scope_of_work"
                        class="rounded-2xl bg-pale/80 px-4 py-3.5 text-sm font-medium leading-relaxed text-ink/65 sm:px-5"
                    >
                        {{ quote.scope_of_work }}
                    </p>

                    <QuoteBreakdown
                        :line-items="quote.line_items"
                        :subtotal="quote.subtotal_naira"
                        :discount="quote.discount_naira"
                        :vat="quote.vat_naira"
                        :vat-rate="quote.vat_rate"
                        :total="quote.total_naira"
                    />

                    <div
                        v-if="quote.notes || quote.payment_terms || quote.terms"
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <div
                            v-if="quote.notes"
                            class="rounded-2xl bg-pale px-4 py-3.5 text-sm text-ink/65 sm:px-5"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Notes</p>
                            <p class="mt-1.5 leading-relaxed">{{ quote.notes }}</p>
                        </div>
                        <div
                            v-if="quote.payment_terms"
                            class="rounded-2xl bg-pale px-4 py-3.5 text-sm text-ink/65 sm:px-5"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Payment terms</p>
                            <p class="mt-1.5 leading-relaxed">{{ quote.payment_terms }}</p>
                        </div>
                        <div
                            v-if="quote.terms"
                            class="rounded-2xl bg-pale px-4 py-3.5 text-sm text-ink/65 sm:col-span-2 sm:px-5"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Terms</p>
                            <p class="mt-1.5 leading-relaxed">{{ quote.terms }}</p>
                        </div>
                    </div>

                    <form
                        v-if="can_respond"
                        class="space-y-4 rounded-[1.35rem] border border-ink/[0.06] bg-pale/40 p-5 sm:p-6"
                        @submit.prevent="submit"
                    >
                        <div>
                            <p class="text-sm font-bold text-ink">Your decision</p>
                            <p class="mt-1 text-xs font-medium text-ink/45">
                                Accept or decline this quote — no account needed.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <button
                                type="button"
                                class="tap-target rounded-2xl px-4 py-3.5 text-sm font-bold ring-1 transition"
                                :class="
                                    decision === 'accepted'
                                        ? 'bg-emerald-600 text-white ring-emerald-600 shadow-[0_10px_24px_-10px_rgba(5,150,105,0.55)]'
                                        : 'bg-white text-ink ring-ink/10 hover:bg-white'
                                "
                                @click="decision = 'accepted'"
                            >
                                Accept
                            </button>
                            <button
                                type="button"
                                class="tap-target rounded-2xl px-4 py-3.5 text-sm font-bold ring-1 transition"
                                :class="
                                    decision === 'declined'
                                        ? 'bg-ink text-white ring-ink'
                                        : 'bg-white text-ink ring-ink/10 hover:bg-white'
                                "
                                @click="decision = 'declined'"
                            >
                                Decline
                            </button>
                        </div>

                        <textarea
                            v-model="message"
                            rows="3"
                            placeholder="Optional note for the artisan…"
                            class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />

                        <button
                            type="submit"
                            class="tap-target w-full rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!decision || busy"
                        >
                            {{ busy ? 'Submitting…' : 'Submit response' }}
                        </button>
                    </form>

                    <div
                        v-else
                        class="rounded-[1.35rem] bg-pale px-5 py-4 text-sm font-semibold text-ink/55 ring-1 ring-ink/[0.05]"
                    >
                        This quote has already been answered.
                    </div>
                </div>
            </article>

            <p class="mt-6 text-center text-[11px] font-medium text-ink/35">
                Sent securely via {{ appName }} · Reply by email or WhatsApp with any questions
            </p>
        </main>
    </div>
</template>

<script setup>
import QuoteBreakdown from '@/Components/Quotes/QuoteBreakdown.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    business: { type: Object, required: true },
    request: { type: Object, required: true },
    quote: { type: Object, required: true },
    can_respond: { type: Boolean, default: false },
    respond_url: { type: String, required: true },
    pdf_url: { type: String, default: null },
    app_name: { type: String, default: 'Kraftrack' },
});

const decision = ref('');
const message = ref('');
const busy = ref(false);

const appName = computed(() => props.app_name || 'Kraftrack');

const businessInitials = computed(() => {
    const parts = String(props.business.business_name || 'Q')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'Q';
});

const whatsappHref = computed(() => {
    const raw = String(props.business.phone || '').replace(/\D+/g, '');
    if (!raw) {
        return null;
    }
    const phone = raw.startsWith('0') ? `234${raw.slice(1)}` : raw;
    const text = encodeURIComponent(
        `Hi ${props.business.business_name}, I’m writing about quote ${props.quote.quote_number}.`,
    );
    return `https://wa.me/${phone}?text=${text}`;
});

const formatNaira = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount || 0);

const submit = () => {
    busy.value = true;
    router.post(
        props.respond_url,
        { decision: decision.value, message: message.value },
        {
            onFinish: () => {
                busy.value = false;
            },
        },
    );
};
</script>

<style scoped>
.quote-public {
    background-color: #eef2f8;
}
</style>
