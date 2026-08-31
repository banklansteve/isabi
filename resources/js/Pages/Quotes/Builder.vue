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
                                <FormTextInput
                                    id="valid-until"
                                    v-model="form.valid_until"
                                    type="date"
                                    label="Valid until"
                                    icon="ti ti-calendar"
                                    :min="formMeta.validUntilMin"
                                    :error="form.errors.valid_until"
                                />
                                <FormTextInput
                                    v-if="form.valid_until"
                                    id="estimated-start"
                                    v-model="form.estimated_start"
                                    type="date"
                                    label="Estimated start"
                                    icon="ti ti-calendar-event"
                                    :min="estimatedStartMin"
                                    :error="form.errors.estimated_start"
                                />
                            </div>
                            <FormTextInput
                                v-if="form.valid_until"
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
                        <div class="border-b border-ink/[0.05] px-5 py-4 sm:px-6">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">Your quote</p>
                        </div>
                        <div class="space-y-4 px-5 py-5 sm:px-6">
                            <p class="text-3xl font-bold tracking-tight text-base-action">{{ formatNaira(quote.total_naira) }}</p>
                            <p v-if="quote.scope_of_work" class="text-sm font-medium leading-relaxed text-ink/65">{{ quote.scope_of_work }}</p>
                            <ul class="space-y-2 text-sm">
                                <li v-for="(row, i) in quote.line_items" :key="i" class="flex justify-between gap-4">
                                    <span class="text-ink/70">{{ row.display_label || row.label || row.description }}</span>
                                    <span class="font-semibold text-ink">{{ formatNaira(row.line_total ?? ((row.quantity || 0) * (row.unit_price || 0))) }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <div v-if="request.is_editable" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <FormButton
                            type="submit"
                            variant="secondary"
                            label="Save draft"
                            loading-label="Saving…"
                            icon-left="ti ti-device-floppy"
                            :loading="form.processing"
                        />
                        <FormButton
                            type="button"
                            variant="primary"
                            label="Send quote"
                            icon-right="ti ti-send"
                            :disabled="form.processing || totals.total <= 0"
                            @click="sendQuote"
                        />
                    </div>

                    <div v-else-if="request.status === 'awaiting_client'" class="flex flex-col gap-3 sm:flex-row">
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
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
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
            validUntilMin: '',
            defaultVatRate: 7.5,
            extraCharges: [],
        }),
    },
});

const shareOpen = ref(false);

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

const estimatedStartMin = computed(() => {
    if (!form.valid_until) {
        return props.formMeta.validUntilMin;
    }

    const validUntil = new Date(`${form.valid_until}T00:00:00`);
    validUntil.setDate(validUntil.getDate() + 1);

    return validUntil.toISOString().slice(0, 10);
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

onMounted(() => {
    if (props.openQuoteShare && props.whatsappShare) {
        shareOpen.value = true;
    }
});

watch(
    () => form.valid_until,
    (value) => {
        if (!value) {
            form.estimated_start = '';
            form.estimated_duration_days = '';
            return;
        }

        if (form.estimated_start && form.estimated_start <= value) {
            form.estimated_start = estimatedStartMin.value;
        }
    },
);

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

const saveDraft = () => {
    form.put(route('quotes.update', props.request.uid), {
        preserveScroll: true,
    });
};

const sendQuote = () => {
    if (totals.value.total <= 0) {
        return;
    }

    form.put(route('quotes.update', props.request.uid), {
        preserveScroll: true,
        onSuccess: () => {
            router.post(route('quotes.send', props.request.uid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const reviseQuote = () => {
    router.post(route('quotes.revise', props.request.uid), {}, { preserveScroll: true });
};
</script>
