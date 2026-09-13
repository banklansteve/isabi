<template>
    <Head :title="`Quote for ${request.name}`" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl space-y-5 pb-10 sm:space-y-6">
            <section
                class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-6 shadow-premium-ink sm:px-8 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <Link
                            :href="route('quotes.index')"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.08] px-3 py-1.5 text-xs font-bold text-white/70 ring-1 ring-white/10 transition-colors hover:bg-white/15 hover:text-white"
                        >
                            <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                            All quotes
                        </Link>

                        <p class="mt-5 text-[11px] font-bold uppercase tracking-[0.16em] text-white/45">
                            {{ request.status_label }}
                        </p>
                        <h1 class="mt-2 font-editorial text-[1.75rem] font-semibold leading-tight tracking-tight text-white sm:text-[2.2rem]">
                            {{ request.subject }}
                        </h1>
                        <p class="mt-2 text-sm font-medium text-white/60">
                            {{ request.name }} · {{ request.submitted_at }}
                        </p>
                        <p
                            v-if="request.phone"
                            class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/[0.08] px-3 py-1.5 text-sm font-semibold text-white/85 ring-1 ring-white/10"
                        >
                            <i class="ti ti-phone text-base text-white/50" aria-hidden="true" />
                            {{ request.phone }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="quote.pdf_url"
                            :href="quote.pdf_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-base-action shadow-sm ring-1 ring-white/20 transition hover:bg-tint"
                        >
                            <i class="ti ti-file-type-pdf text-sm" aria-hidden="true" />
                            Download PDF
                        </a>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.08] px-3 py-1.5 text-xs font-bold text-white/80 ring-1 ring-white/10"
                        >
                            <i class="ti ti-hash text-sm text-white/50" aria-hidden="true" />
                            {{ quote.quote_number }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold ring-1"
                            :class="statusBadgeClass"
                        >
                            {{ request.status_label }}
                        </span>
                    </div>
                </div>
            </section>

            <section
                v-if="request.should_log_job"
                class="overflow-hidden rounded-[1.35rem] bg-gradient-to-r from-tint to-white p-5 shadow-premium ring-1 ring-base/15 sm:p-6"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-editorial text-lg font-semibold text-ink">Ready to log this job?</p>
                        <p class="mt-1 text-sm font-medium text-ink/50">
                            The client accepted and the start date has passed — turn this quote into a work log entry.
                        </p>
                    </div>
                    <Link
                        :href="request.log_job_url"
                        class="tap-target inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] hover:bg-base-hover"
                    >
                        <i class="ti ti-briefcase" aria-hidden="true" />
                        Log this as a job
                    </Link>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start lg:gap-6">
                <form class="space-y-5" @submit.prevent="saveDraft">
                    <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                        <div class="border-b border-ink/[0.05] bg-pale/50 px-5 py-4 sm:px-6">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Client request</p>
                        </div>

                        <div class="space-y-4 px-5 py-5 sm:px-6">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-pale px-4 py-3 ring-1 ring-ink/[0.05]">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Phone</p>
                                    <p class="mt-1 text-sm font-bold text-ink">{{ request.phone }}</p>
                                </div>
                                <div class="rounded-2xl bg-pale px-4 py-3 ring-1 ring-ink/[0.05]">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Email</p>
                                    <p class="mt-1 truncate text-sm font-bold text-ink">{{ request.email }}</p>
                                </div>
                            </div>

                            <p v-if="request.message" class="rounded-2xl bg-pale px-4 py-3 text-sm font-medium leading-relaxed text-ink/70">
                                “{{ request.message }}”
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    v-if="request.phone_href"
                                    :href="request.phone_href"
                                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-4 py-3 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                                >
                                    <i class="ti ti-phone text-base" aria-hidden="true" />
                                    Call {{ request.phone }}
                                </a>
                                <a
                                    v-if="request.email_href"
                                    :href="request.email_href"
                                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition-colors hover:bg-tint"
                                >
                                    <i class="ti ti-mail text-base" aria-hidden="true" />
                                    Email {{ request.name }}
                                </a>
                                <a
                                    v-if="request.job?.public_url"
                                    :href="request.job.public_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-ink/70 ring-1 ring-ink/10 transition-colors hover:bg-pale"
                                >
                                    <i class="ti ti-briefcase text-base" aria-hidden="true" />
                                    View referenced job
                                </a>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="request.client_response"
                        class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                    >
                        <div class="border-b border-ink/[0.05] bg-emerald-50/50 px-5 py-4 sm:px-6">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-800">Client response</p>
                            <p class="mt-1 text-xs font-medium text-ink/45">{{ request.client_responded_at }}</p>
                        </div>
                        <div class="px-5 py-5 sm:px-6">
                            <p class="text-sm font-medium leading-relaxed text-ink/70">“{{ request.client_response }}”</p>
                        </div>
                    </section>

                    <template v-if="request.is_editable">
                    <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                        <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">Your quote</p>
                        </div>

                        <div class="space-y-5 px-5 py-5 sm:px-6">
                            <FormTextarea
                                id="scope"
                                v-model="form.scope_of_work"
                                label="Scope of work"
                                icon="ti ti-list-check"
                                hint="What you’ll deliver — materials, labour, exclusions."
                                :error="form.errors.scope_of_work"
                                :rows="4"
                            />

                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormDatePicker
                                    id="valid-until"
                                    v-model="form.valid_until"
                                    label="Valid until"
                                    hint="Quote offer expires at the end of this day."
                                    icon="ti ti-calendar"
                                    :min-date="formMeta.validUntilMin"
                                    :max-date="formMeta.validUntilMax"
                                    :error="form.errors.valid_until"
                                />
                                <FormDatePicker
                                    id="estimated-start"
                                    v-model="form.estimated_start"
                                    label="Estimated start"
                                    hint="When you expect to begin the job."
                                    icon="ti ti-calendar-event"
                                    :min-date="estimatedStartMin"
                                    :max-date="estimatedStartMax"
                                    :error="form.errors.estimated_start"
                                />
                            </div>
                            <FormTextInput
                                id="duration"
                                v-model="form.estimated_duration_days"
                                type="number"
                                min="1"
                                label="Duration (days)"
                                icon="ti ti-clock"
                                :error="form.errors.estimated_duration_days"
                            />
                        </div>
                    </section>

                    <QuoteLineItemsEditor
                        v-model="form.line_items"
                        v-model:vat-rate="form.vat_rate"
                        v-model:discount-naira="form.discount_naira"
                        :extra-charges="formMeta.extraCharges"
                        :errors="form.errors"
                    />

                    <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                        <div class="space-y-5 px-5 py-5 sm:px-6">
                            <FormTextarea
                                id="notes"
                                v-model="form.notes"
                                label="Notes for client"
                                icon="ti ti-note"
                                hint="Optional clarifications, assumptions, or next steps."
                                :error="form.errors.notes"
                            />
                            <FormTextarea
                                id="payment-terms"
                                v-model="form.payment_terms"
                                label="Payment terms"
                                icon="ti ti-credit-card"
                                :error="form.errors.payment_terms"
                            />
                            <FormTextarea
                                id="terms"
                                v-model="form.terms"
                                label="Terms & conditions"
                                icon="ti ti-file-text"
                                :error="form.errors.terms"
                            />
                        </div>
                    </section>
                    </template>

                    <section
                        v-else
                        class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                    >
                        <div class="border-b border-ink/[0.05] bg-gradient-to-r from-tint/50 to-white px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">Your quote</p>
                                    <p class="mt-1 text-xs font-semibold text-ink/40">{{ quote.quote_number }}</p>
                                </div>
                                <p class="text-2xl font-bold tracking-tight text-base-action sm:text-3xl">
                                    {{ formatNaira(quote.total_naira) }}
                                </p>
                            </div>
                            <div
                                v-if="quote.valid_until || quote.estimated_start || quote.estimated_duration_days"
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                <span
                                    v-if="quote.valid_until"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-ink/60 ring-1 ring-ink/10"
                                >
                                    <i class="ti ti-calendar text-sm text-ink/35" aria-hidden="true" />
                                    Valid until {{ formatDisplayDate(quote.valid_until) }}
                                </span>
                                <span
                                    v-if="quote.estimated_start"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-ink/60 ring-1 ring-ink/10"
                                >
                                    <i class="ti ti-calendar-event text-sm text-ink/35" aria-hidden="true" />
                                    Start {{ formatDisplayDate(quote.estimated_start) }}
                                </span>
                                <span
                                    v-if="quote.estimated_duration_days"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-ink/60 ring-1 ring-ink/10"
                                >
                                    <i class="ti ti-clock text-sm text-ink/35" aria-hidden="true" />
                                    {{ quote.estimated_duration_days }} day{{ quote.estimated_duration_days === 1 ? '' : 's' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4 px-4 py-4 sm:px-5 sm:py-5">
                            <p
                                v-if="quote.scope_of_work"
                                class="rounded-2xl bg-pale/70 px-4 py-3 text-sm font-medium leading-relaxed text-ink/65"
                            >
                                {{ quote.scope_of_work }}
                            </p>

                            <QuoteBreakdown
                                :line-items="pricedLineItems"
                                :subtotal="quote.subtotal_naira"
                                :discount="quote.discount_naira"
                                :vat="quote.vat_naira"
                                :vat-rate="quote.vat_rate"
                                :total="quote.total_naira"
                            />

                            <div v-if="quote.notes" class="rounded-2xl bg-pale px-4 py-3 text-sm text-ink/65">
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Notes</p>
                                <p class="mt-1">{{ quote.notes }}</p>
                            </div>
                            <div v-if="quote.payment_terms" class="rounded-2xl bg-pale px-4 py-3 text-sm text-ink/65">
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Payment terms</p>
                                <p class="mt-1">{{ quote.payment_terms }}</p>
                            </div>
                            <div v-if="quote.terms" class="rounded-2xl bg-pale px-4 py-3 text-sm text-ink/65">
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Terms</p>
                                <p class="mt-1">{{ quote.terms }}</p>
                            </div>
                        </div>
                    </section>

                    <div v-if="request.is_editable" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <FormButton
                                type="submit"
                                variant="secondary"
                                label="Save draft"
                                loading-label="Saving…"
                                icon-left="ti ti-device-floppy"
                                :loading="form.processing"
                            />
                            <a
                                v-if="quote.pdf_url"
                                :href="quote.pdf_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition hover:bg-tint"
                            >
                                <i class="ti ti-file-type-pdf text-base" aria-hidden="true" />
                                Download PDF
                            </a>
                        </div>
                        <FormButton
                            type="button"
                            variant="primary"
                            label="Send quote"
                            icon-right="ti ti-send"
                            :disabled="form.processing || totals.total <= 0"
                            @click="sendQuote"
                        />
                    </div>

                    <div v-else-if="request.status === 'awaiting_client'" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a
                            v-if="quote.pdf_url"
                            :href="quote.pdf_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-base-action ring-1 ring-base-action/25 transition hover:bg-tint"
                        >
                            <i class="ti ti-file-type-pdf text-base" aria-hidden="true" />
                            Download PDF
                        </a>
                        <FormButton
                            type="button"
                            variant="secondary"
                            label="Revise quote"
                            icon-left="ti ti-pencil"
                            @click="reviseQuote"
                        />
                        <FormButton
                            type="button"
                            variant="primary"
                            label="Share on WhatsApp"
                            icon-left="ti ti-brand-whatsapp"
                            @click="shareOpen = true"
                        />
                    </div>

                    <div
                        v-else-if="quote.pdf_url"
                        class="flex flex-col gap-3 sm:flex-row"
                    >
                        <a
                            :href="quote.pdf_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition hover:bg-base-hover"
                        >
                            <i class="ti ti-file-type-pdf text-base" aria-hidden="true" />
                            Download PDF
                        </a>
                    </div>
                </form>

                <aside class="space-y-5 lg:sticky lg:top-24">
                    <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                        <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">Your business</p>
                            <p class="mt-1 text-sm font-medium text-ink/45">Printed on the quote automatically.</p>
                        </div>

                        <div class="px-5 py-5 sm:px-6">
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="business.logo_url"
                                    :src="business.logo_url"
                                    :alt="`${business.business_name} logo`"
                                    class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 ring-ink/10"
                                />
                                <div
                                    v-else
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-tint text-base-action ring-1 ring-base-action/15"
                                >
                                    <i class="ti ti-building-store text-2xl" aria-hidden="true" />
                                </div>

                                <div class="min-w-0">
                                    <p class="font-editorial text-lg font-semibold tracking-tight text-ink">
                                        {{ business.business_name }}
                                    </p>
                                    <p v-if="business.trade" class="text-sm font-medium text-ink/45">
                                        {{ business.trade }}
                                        <span v-if="business.area_label"> · {{ business.area_label }}</span>
                                    </p>
                                </div>
                            </div>

                            <dl class="mt-5 space-y-3 text-sm">
                                <div v-if="business.address">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Address</dt>
                                    <dd class="mt-1 font-medium leading-relaxed text-ink/70">{{ business.address }}</dd>
                                </div>
                                <div v-if="business.phone">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Phone</dt>
                                    <dd class="mt-1 font-semibold text-ink">{{ business.phone }}</dd>
                                </div>
                                <div v-if="business.email">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">Email</dt>
                                    <dd class="mt-1 font-semibold text-ink">{{ business.email }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    <section
                        v-if="request.status === 'awaiting_client'"
                        class="rounded-[1.5rem] bg-amber-50 px-5 py-4 ring-1 ring-amber-200/80"
                    >
                        <p class="text-sm font-bold text-amber-950">Awaiting client</p>
                        <p class="mt-1 text-sm font-medium text-amber-900/75">
                            Quote sent {{ quote.sent_at_label }}. Share the link again or revise if needed.
                        </p>
                    </section>
                </aside>
            </div>
        </div>

        <QuoteShareSheet
            :show="shareOpen"
            :share="whatsappShare"
            @close="shareOpen = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormDatePicker from '@/Components/Form/FormDatePicker.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import QuoteBreakdown from '@/Components/Quotes/QuoteBreakdown.vue';
import QuoteLineItemsEditor from '@/Components/Quotes/QuoteLineItemsEditor.vue';
import QuoteShareSheet from '@/Components/Quotes/QuoteShareSheet.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    request: { type: Object, required: true },
    business: { type: Object, required: true },
    quote: { type: Object, required: true },
    whatsappShare: { type: Object, default: null },
    openQuoteShare: { type: Boolean, default: false },
    formMeta: {
        type: Object,
        default: () => ({
            today: '',
            validUntilMin: '',
            validUntilMax: '',
            estimatedStartMin: '',
            estimatedStartMax: '',
            defaultVatRate: 7.5,
            extraCharges: [],
        }),
    },
});

const shareOpen = ref(false);

const toLocalIso = (date = new Date()) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
};

const addYearsIso = (years) => {
    const date = new Date();
    date.setFullYear(date.getFullYear() + years);

    return toLocalIso(date);
};

const estimatedStartMin = computed(
    () => props.formMeta.estimatedStartMin || props.formMeta.today || toLocalIso(),
);

const estimatedStartMax = computed(
    () => props.formMeta.estimatedStartMax || props.formMeta.validUntilMax || addYearsIso(2),
);

const defaultLineItems = () => [
    { kind: 'labour', label: 'Labour', quantity: 1, unit: 'fee', unit_price: 0 },
    { kind: 'materials', label: '', quantity: 0, unit: 'unit', unit_price: 0 },
];

const form = useForm({
    valid_until: props.quote.valid_until || '',
    estimated_start: props.quote.estimated_start || '',
    estimated_duration_days: props.quote.estimated_duration_days || '',
    scope_of_work: props.quote.scope_of_work || '',
    line_items: (props.quote.line_items?.length ? props.quote.line_items : defaultLineItems()).map((row) => ({
        kind: row.kind || 'other',
        label: row.label || row.description || '',
        quantity: row.quantity ?? 1,
        unit: row.unit || (row.kind === 'materials' ? 'lot' : 'fee'),
        unit_price: row.unit_price ?? 0,
    })),
    notes: props.quote.notes || '',
    terms: props.quote.terms || '',
    payment_terms: props.quote.payment_terms || '',
    vat_rate: props.quote.vat_rate ?? props.formMeta.defaultVatRate ?? 7.5,
    discount_naira: props.quote.discount_naira ?? 0,
});

const totals = computed(() => {
    const subtotal = form.line_items.reduce((sum, row) => {
        if (row.kind === 'materials') {
            const quantity = Number(row.quantity) || 0;

            return sum + quantity * (Number(row.unit_price) || 0);
        }

        return sum + (Number(row.unit_price) || 0);
    }, 0);
    const discount = Number(form.discount_naira) || 0;
    const taxable = Math.max(0, subtotal - discount);
    const vatRate = Number(form.vat_rate) || 0;
    const vat = taxable * (vatRate / 100);

    return {
        subtotal,
        discount,
        vat,
        total: taxable + vat,
    };
});

const statusBadgeClass = computed(() => {
    return 'bg-white/[0.08] text-white/80 ring-white/10';
});

const pricedLineItems = computed(() =>
    (props.quote.line_items || []).filter((row) => (Number(row.line_total) || 0) > 0),
);

onMounted(() => {
    if (props.openQuoteShare && props.whatsappShare) {
        shareOpen.value = true;
    }
});

watch(
    () => props.openQuoteShare,
    (open) => {
        if (open && props.whatsappShare) {
            shareOpen.value = true;
        }
    },
);

const formatNaira = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount || 0);

const formatDisplayDate = (iso) => {
    if (!iso) {
        return '';
    }

    const [y, m, d] = String(iso).split('-');

    if (!y || !m || !d) {
        return iso;
    }

    return `${d}/${m}/${y}`;
};

const saveDraft = () => {
    form.put(route('quotes.update', props.request.uid), {
        preserveScroll: true,
    });
};

const sendQuote = () => {
    if (totals.value.total <= 0 || form.processing) {
        return;
    }

    form.post(route('quotes.send', props.request.uid), {
        preserveScroll: true,
    });
};

const reviseQuote = () => {
    router.post(route('quotes.revise', props.request.uid), {}, { preserveScroll: true });
};
</script>
