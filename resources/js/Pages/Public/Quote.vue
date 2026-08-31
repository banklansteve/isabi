<template>
    <Head :title="`Quote from ${business.business_name}`" />

    <div class="min-h-dvh bg-pale font-app text-ink antialiased">
        <main class="mx-auto max-w-lg px-4 py-8 sm:py-12">
            <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <div class="border-b border-ink/[0.05] bg-gradient-to-r from-tint/80 to-white px-5 py-6 sm:px-6">
                    <div class="flex items-start gap-3">
                        <img
                            v-if="business.logo_url"
                            :src="business.logo_url"
                            :alt="business.business_name"
                            class="h-14 w-14 rounded-2xl object-cover ring-1 ring-ink/10"
                        />
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">Quote</p>
                            <h1 class="mt-1 font-editorial text-xl font-semibold tracking-tight text-ink">
                                {{ request.subject }}
                            </h1>
                            <p class="mt-1 text-sm font-medium text-ink/45">From {{ business.business_name }}</p>
                            <p class="mt-1 text-xs font-semibold text-ink/35">{{ quote.quote_number }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 px-5 py-6 sm:px-6">
                    <div>
                        <p class="text-3xl font-bold tracking-tight text-base-action">{{ formatNaira(quote.total_naira) }}</p>
                        <p v-if="quote.valid_until" class="mt-1 text-sm font-medium text-ink/45">Valid until {{ quote.valid_until }}</p>
                        <p v-if="quote.estimated_start" class="mt-0.5 text-sm font-medium text-ink/45">Est. start {{ quote.estimated_start }}</p>
                    </div>

                    <a
                        v-if="pdf_url"
                        :href="pdf_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tap-target inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition hover:bg-tint"
                    >
                        <i class="ti ti-file-type-pdf text-base" aria-hidden="true" />
                        Download PDF
                    </a>

                    <p v-if="quote.scope_of_work" class="text-sm font-medium leading-relaxed text-ink/65">{{ quote.scope_of_work }}</p>

                    <ul class="divide-y divide-ink/[0.06] rounded-2xl bg-pale/60 ring-1 ring-ink/[0.05]">
                        <li
                            v-for="(row, i) in quote.line_items"
                            :key="i"
                            class="flex items-start justify-between gap-4 px-4 py-3 text-sm"
                        >
                            <span class="font-medium text-ink/70">{{ row.display_label || row.label }}</span>
                            <span class="shrink-0 font-bold text-ink">{{ formatNaira(row.line_total) }}</span>
                        </li>
                    </ul>

                    <dl class="space-y-2 rounded-2xl bg-pale/40 px-4 py-3 text-sm ring-1 ring-ink/[0.05]">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink/45">Subtotal</dt>
                            <dd class="font-semibold text-ink">{{ formatNaira(quote.subtotal_naira) }}</dd>
                        </div>
                        <div v-if="quote.discount_naira > 0" class="flex justify-between gap-4">
                            <dt class="text-ink/45">Discount</dt>
                            <dd class="font-semibold text-emerald-700">− {{ formatNaira(quote.discount_naira) }}</dd>
                        </div>
                        <div v-if="quote.vat_naira > 0" class="flex justify-between gap-4">
                            <dt class="text-ink/45">VAT ({{ quote.vat_rate }}%)</dt>
                            <dd class="font-semibold text-ink">{{ formatNaira(quote.vat_naira) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-ink/10 pt-2">
                            <dt class="font-bold text-ink">Total</dt>
                            <dd class="font-bold text-base-action">{{ formatNaira(quote.total_naira) }}</dd>
                        </div>
                    </dl>

                    <div v-if="quote.notes" class="rounded-2xl bg-pale px-4 py-3 text-sm text-ink/65">{{ quote.notes }}</div>
                    <div v-if="quote.payment_terms" class="rounded-2xl bg-pale px-4 py-3 text-sm text-ink/65">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Payment terms</p>
                        <p class="mt-1">{{ quote.payment_terms }}</p>
                    </div>
                    <div v-if="quote.terms" class="rounded-2xl bg-pale px-4 py-3 text-sm text-ink/65">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Terms</p>
                        <p class="mt-1">{{ quote.terms }}</p>
                    </div>

                    <form v-if="can_respond" class="space-y-4 border-t border-ink/[0.06] pt-5" @submit.prevent="submit">
                        <p class="text-sm font-bold text-ink">Your decision</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="tap-target rounded-2xl px-4 py-3 text-sm font-bold ring-1 transition"
                                :class="decision === 'accepted' ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-white text-ink ring-ink/10'"
                                @click="decision = 'accepted'"
                            >
                                Accept
                            </button>
                            <button
                                type="button"
                                class="tap-target rounded-2xl px-4 py-3 text-sm font-bold ring-1 transition"
                                :class="decision === 'declined' ? 'bg-ink text-white ring-ink' : 'bg-white text-ink ring-ink/10'"
                                @click="decision = 'declined'"
                            >
                                Decline
                            </button>
                        </div>
                        <textarea
                            v-model="message"
                            rows="3"
                            placeholder="Optional note to the artisan…"
                            class="w-full rounded-xl border border-ink/10 bg-pale px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                        />
                        <button
                            type="submit"
                            class="tap-target w-full rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white hover:bg-base-hover disabled:opacity-50"
                            :disabled="!decision || busy"
                        >
                            Submit response
                        </button>
                    </form>

                    <p v-else class="rounded-2xl bg-pale px-4 py-3 text-sm font-semibold text-ink/55">
                        This quote has already been answered.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    business: { type: Object, required: true },
    request: { type: Object, required: true },
    quote: { type: Object, required: true },
    can_respond: { type: Boolean, default: false },
    respond_url: { type: String, required: true },
    pdf_url: { type: String, default: null },
});

const decision = ref('');
const message = ref('');
const busy = ref(false);

const formatNaira = (amount) =>
    new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(amount || 0);

const submit = () => {
    busy.value = true;
    router.post(props.respond_url, { decision: decision.value, message: message.value }, {
        onFinish: () => {
            busy.value = false;
        },
    });
};
</script>
