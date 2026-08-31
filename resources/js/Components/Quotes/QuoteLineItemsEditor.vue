<template>
    <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
        <div class="border-b border-ink/[0.05] bg-gradient-to-r from-tint/40 to-white px-4 py-4 sm:px-6">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base">Charges</p>
            <p class="mt-1 text-sm font-medium text-ink/45">Build your quote line by line.</p>
        </div>

        <div class="px-4 py-4 sm:px-6 sm:py-5">
            <!-- Labour + extra charges -->
            <div class="overflow-hidden rounded-2xl ring-1 ring-ink/[0.06]">
                <div class="hidden border-b border-ink/[0.05] bg-pale/50 px-3 py-2.5 text-[10px] font-bold uppercase tracking-[0.12em] text-ink/35 sm:grid sm:grid-cols-[minmax(7.5rem,9.5rem)_minmax(0,1fr)_2.875rem] sm:gap-3 sm:px-4">
                    <span>Charge</span>
                    <span>Amount (₦)</span>
                    <span class="sr-only">Remove</span>
                </div>

                <!-- Labour -->
                <div class="flex items-center gap-2 border-b border-ink/[0.05] px-3 py-3 sm:grid sm:grid-cols-[minmax(7.5rem,9.5rem)_minmax(0,1fr)_2.875rem] sm:items-center sm:gap-3 sm:px-4 sm:py-3.5">
                    <p class="w-[6.75rem] shrink-0 text-sm font-bold leading-tight text-ink sm:w-auto">Labour</p>
                    <FormAmountInput
                        id="labour-fee"
                        v-model="labourRow.unit_price"
                        label=""
                        placeholder="Amount (₦)"
                        icon="ti ti-currency-naira"
                        class="quote-inline-field min-w-0 flex-1"
                        :error="lineItemError(labourIndex, 'unit_price')"
                    />
                    <span class="hidden sm:block" aria-hidden="true" />
                </div>

                <!-- Committed extra charges -->
                <div
                    v-for="{ row, index } in extraRows"
                    :key="`extra-${index}`"
                    class="flex items-center gap-2 border-b border-ink/[0.05] px-3 py-3 sm:grid sm:grid-cols-[minmax(7.5rem,9.5rem)_minmax(0,1fr)_2.875rem] sm:items-center sm:gap-3 sm:px-4 sm:py-3.5"
                >
                    <p class="w-[6.75rem] shrink-0 truncate text-sm font-semibold leading-tight text-ink/75 sm:w-auto">
                        {{ extraLabel(row) }}
                    </p>
                    <FormAmountInput
                        :id="`extra-price-${index}`"
                        v-model="row.unit_price"
                        label=""
                        placeholder="Amount (₦)"
                        icon="ti ti-currency-naira"
                        class="quote-inline-field min-w-0 flex-1"
                        :error="lineItemError(index, 'unit_price')"
                    />
                    <button
                        type="button"
                        class="quote-icon-btn quote-icon-btn--danger"
                        aria-label="Remove charge"
                        @click="removeRow(index)"
                    >
                        <i class="ti ti-trash text-base" aria-hidden="true" />
                    </button>
                </div>

                <!-- Draft extra charge row -->
                <div class="border-b border-ink/[0.05] bg-pale/20 px-3 py-3 sm:px-4 sm:py-3.5">
                    <!-- Pick charge type -->
                    <div
                        v-if="!pendingExtra.kind"
                        class="quote-charge-row"
                    >
                        <span class="quote-charge-label" aria-hidden="true" />
                        <FormSelect
                            id="extra-charge-type"
                            v-model="pendingExtra.kind"
                            placeholder="Charge type"
                            icon="ti ti-list"
                            :options="extraChargeOptions"
                            class="quote-inline-field quote-charge-field min-w-0"
                        />
                        <span class="quote-charge-action" aria-hidden="true" />
                    </div>

                    <!-- Other charge draft -->
                    <div
                        v-else-if="pendingExtra.kind === 'other'"
                        class="flex flex-col gap-2.5 sm:grid sm:grid-cols-[minmax(7.5rem,9.5rem)_minmax(0,1fr)_minmax(0,1fr)_2.875rem] sm:items-center sm:gap-3"
                    >
                        <p class="text-sm font-semibold leading-tight text-ink/75 sm:w-auto">Other</p>
                        <FormTextInput
                            id="extra-other-label"
                            v-model="pendingExtra.label"
                            label=""
                            placeholder="Description"
                            class="quote-inline-field quote-charge-field min-w-0 w-full"
                        />
                        <FormAmountInput
                            id="extra-charge-cost"
                            v-model="pendingExtra.unit_price"
                            label=""
                            placeholder="Amount (₦)"
                            icon="ti ti-currency-naira"
                            class="quote-inline-field quote-charge-field min-w-0 w-full"
                        />
                        <span class="quote-charge-action" aria-hidden="true" />
                    </div>

                    <!-- Standard extra charge draft -->
                    <div
                        v-else
                        class="quote-charge-row"
                    >
                        <p class="quote-charge-label truncate text-sm font-semibold leading-tight text-ink/75">
                            {{ selectedExtraLabel }}
                        </p>
                        <FormAmountInput
                            id="extra-charge-cost"
                            v-model="pendingExtra.unit_price"
                            label=""
                            placeholder="Amount (₦)"
                            icon="ti ti-currency-naira"
                            class="quote-inline-field quote-charge-field min-w-0"
                        />
                        <span class="quote-charge-action" aria-hidden="true" />
                    </div>
                </div>

                <div class="px-3 py-3 sm:px-4">
                    <button
                        type="button"
                        class="quote-add-btn"
                        :disabled="!canConfirmExtra"
                        @click="confirmExtraCharge"
                    >
                        <i class="ti ti-plus text-sm" aria-hidden="true" />
                        Add charge
                    </button>
                </div>
            </div>

            <!-- Materials -->
            <div class="mt-5 overflow-hidden rounded-2xl ring-1 ring-ink/[0.06]">
                <div class="flex items-center justify-between gap-3 border-b border-ink/[0.05] bg-pale/50 px-3 py-3 sm:px-4">
                    <div>
                        <p class="text-sm font-bold text-ink">Materials / tools</p>
                        <p class="mt-0.5 text-xs font-medium text-ink/40">Parts and supplies</p>
                    </div>
                    <p v-if="materialsSubtotal > 0" class="shrink-0 text-sm font-bold text-base-action">
                        {{ formatNaira(materialsSubtotal) }}
                    </p>
                </div>

                <div class="hidden border-b border-ink/[0.05] bg-white px-3 py-2.5 text-[10px] font-bold uppercase tracking-[0.12em] text-ink/35 sm:grid sm:grid-cols-[minmax(0,1.4fr)_minmax(4.5rem,5.5rem)_minmax(5.5rem,6.5rem)_minmax(5.5rem,6.5rem)_2.875rem] sm:gap-2 sm:px-4">
                    <span>Material / part</span>
                    <span>Units</span>
                    <span>Unit price</span>
                    <span>Total</span>
                    <span class="sr-only">Remove</span>
                </div>

                <!-- Committed material rows -->
                <div
                    v-for="{ row, index } in committedMaterialRows"
                    :key="`material-${index}`"
                    class="quote-material-row border-b border-ink/[0.05] px-3 py-3 sm:px-4 sm:py-3.5"
                >
                    <div class="quote-material-grid">
                        <FormTextInput
                            :id="`material-label-${index}`"
                            v-model="row.label"
                            label=""
                            placeholder="Material / part"
                            class="quote-inline-field min-w-0"
                            :error="lineItemError(index, 'label')"
                        />
                        <FormTextInput
                            :id="`material-qty-${index}`"
                            v-model="row.quantity"
                            type="number"
                            min="0"
                            step="0.01"
                            label=""
                            placeholder="Units"
                            class="quote-inline-field"
                            :error="lineItemError(index, 'quantity')"
                        />
                        <FormAmountInput
                            :id="`material-price-${index}`"
                            v-model="row.unit_price"
                            label=""
                            placeholder="Unit price"
                            icon="ti ti-currency-naira"
                            class="quote-inline-field min-w-0"
                            :error="lineItemError(index, 'unit_price')"
                        />
                        <FormAmountInput
                            :id="`material-total-${index}`"
                            :model-value="lineTotal(row)"
                            label=""
                            placeholder="Total"
                            readonly
                            disabled
                            input-class="!bg-pale/80 !text-ink/70"
                            class="quote-inline-field min-w-0"
                        />
                        <button
                            type="button"
                            class="quote-icon-btn quote-icon-btn--danger quote-material-delete"
                            aria-label="Remove material"
                            @click="removeRow(index)"
                        >
                            <i class="ti ti-trash text-base" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <!-- Draft material row -->
                <div
                    v-if="draftMaterialEntry"
                    class="quote-material-row border-b border-ink/[0.05] bg-pale/20 px-3 py-3 sm:px-4 sm:py-3.5"
                >
                    <div class="quote-material-grid">
                        <FormTextInput
                            id="material-label-draft"
                            v-model="draftMaterialEntry.row.label"
                            label=""
                            placeholder="Material / part"
                            class="quote-inline-field min-w-0"
                            :error="lineItemError(draftMaterialEntry.index, 'label')"
                        />
                        <FormTextInput
                            id="material-qty-draft"
                            v-model="draftMaterialEntry.row.quantity"
                            type="number"
                            min="0"
                            step="0.01"
                            label=""
                            placeholder="Units"
                            class="quote-inline-field"
                            :error="lineItemError(draftMaterialEntry.index, 'quantity')"
                        />
                        <FormAmountInput
                            id="material-price-draft"
                            v-model="draftMaterialEntry.row.unit_price"
                            label=""
                            placeholder="Unit price"
                            icon="ti ti-currency-naira"
                            class="quote-inline-field min-w-0"
                            :error="lineItemError(draftMaterialEntry.index, 'unit_price')"
                        />
                        <FormAmountInput
                            id="material-total-draft"
                            :model-value="lineTotal(draftMaterialEntry.row)"
                            label=""
                            placeholder="Total"
                            readonly
                            disabled
                            input-class="!bg-pale/80 !text-ink/70"
                            class="quote-inline-field min-w-0"
                        />
                        <span class="quote-material-delete hidden sm:block" aria-hidden="true" />
                    </div>
                </div>

                <div class="px-3 py-3 sm:px-4">
                    <button
                        type="button"
                        class="quote-add-btn"
                        :disabled="!canConfirmMaterial"
                        @click="confirmMaterialRow"
                    >
                        <i class="ti ti-plus text-sm" aria-hidden="true" />
                        Add material
                    </button>
                </div>
            </div>
        </div>

        <!-- Totals -->
        <div class="border-t border-ink/[0.05] bg-gradient-to-b from-pale/70 to-pale/40 px-4 py-4 sm:px-6 sm:py-5">
            <div class="grid gap-3 sm:grid-cols-2">
                <FormTextInput
                    id="vat-rate"
                    v-model="vatRate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    label="VAT %"
                    placeholder="7.5"
                    icon="ti ti-percentage"
                    :error="errors.vat_rate"
                />
                <FormAmountInput
                    id="discount"
                    v-model="discountNaira"
                    label="Discount (₦)"
                    placeholder="0"
                    icon="ti ti-discount-2"
                    :error="errors.discount_naira"
                />
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/[0.06]">
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="font-medium text-ink/45">Subtotal</dt>
                        <dd class="font-bold tabular-nums text-ink">{{ formatNaira(totals.subtotal) }}</dd>
                    </div>
                    <div v-if="totals.discount > 0" class="flex items-center justify-between gap-4">
                        <dt class="font-medium text-ink/45">Discount</dt>
                        <dd class="font-bold tabular-nums text-emerald-700">− {{ formatNaira(totals.discount) }}</dd>
                    </div>
                    <div v-if="totals.vat > 0" class="flex items-center justify-between gap-4">
                        <dt class="font-medium text-ink/45">VAT ({{ vatRate || 0 }}%)</dt>
                        <dd class="font-bold tabular-nums text-ink">{{ formatNaira(totals.vat) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-ink/10 pt-3">
                        <dt class="text-base font-bold text-ink">Total</dt>
                        <dd class="text-xl font-bold tabular-nums tracking-tight text-base-action">
                            {{ formatNaira(totals.total) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup>
import FormAmountInput from '@/Components/Form/FormAmountInput.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, required: true },
    extraCharges: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    vatRate: { type: [Number, String], default: 7.5 },
    discountNaira: { type: [Number, String], default: 0 },
});

const emit = defineEmits(['update:modelValue', 'update:vatRate', 'update:discountNaira']);

const pendingExtra = ref({
    kind: '',
    label: '',
    unit_price: '',
});

const vatRate = computed({
    get: () => props.vatRate,
    set: (value) => emit('update:vatRate', value),
});

const discountNaira = computed({
    get: () => props.discountNaira,
    set: (value) => emit('update:discountNaira', value),
});

const usedExtraKinds = computed(() =>
    extraRows.value
        .map(({ row }) => row.kind)
        .filter((kind) => kind !== 'other'),
);

const extraChargeOptions = computed(() =>
    props.extraCharges
        .filter((option) => option.kind === 'other' || !usedExtraKinds.value.includes(option.kind))
        .map((option) => ({ value: option.kind, label: option.label })),
);

const labourIndex = computed(() => props.modelValue.findIndex((row) => row.kind === 'labour'));

const labourRow = computed(() => {
    if (labourIndex.value >= 0) {
        return props.modelValue[labourIndex.value];
    }

    return { kind: 'labour', label: 'Labour', quantity: 1, unit: 'fee', unit_price: 0 };
});

const materialRowEntries = computed(() =>
    props.modelValue
        .map((row, index) => ({ row, index }))
        .filter(({ row }) => row.kind === 'materials'),
);

const committedMaterialRows = computed(() => materialRowEntries.value.slice(0, -1));

const draftMaterialEntry = computed(() => {
    const entries = materialRowEntries.value;

    return entries.length > 0 ? entries[entries.length - 1] : null;
});

const extraRows = computed(() =>
    props.modelValue
        .map((row, index) => ({ row, index }))
        .filter(({ row }) => !['labour', 'materials'].includes(row.kind)),
);

const materialsSubtotal = computed(() =>
    materialRowEntries.value.reduce((sum, { row }) => sum + lineTotal(row), 0),
);

const canConfirmExtra = computed(() => {
    if (!pendingExtra.value.kind) {
        return false;
    }

    const cost = Number(pendingExtra.value.unit_price);
    if (!cost || cost <= 0) {
        return false;
    }

    if (pendingExtra.value.kind === 'other' && !pendingExtra.value.label.trim()) {
        return false;
    }

    return true;
});

const canConfirmMaterial = computed(() => {
    const row = draftMaterialEntry.value?.row;

    if (!row) {
        return false;
    }

    const label = (row.label || '').trim();
    const quantity = Number(row.quantity);
    const unitPrice = Number(row.unit_price);

    return label !== '' && quantity > 0 && unitPrice > 0;
});

const totals = computed(() => {
    const subtotal = props.modelValue.reduce((sum, row) => sum + lineTotal(row), 0);
    const discount = Number(discountNaira.value) || 0;
    const taxable = Math.max(0, subtotal - discount);
    const rate = Number(vatRate.value) || 0;
    const vat = taxable * (rate / 100);

    return {
        subtotal,
        discount,
        vat,
        total: taxable + vat,
    };
});

const extraLabel = (row) => {
    if (row.kind === 'other') {
        return row.label || 'Other';
    }

    return props.extraCharges.find((option) => option.kind === row.kind)?.label || row.label || 'Charge';
};

const lineTotal = (row) => {
    if (row.kind === 'materials') {
        const quantity = Number(row.quantity) || 0;

        return quantity * (Number(row.unit_price) || 0);
    }

    return Number(row.unit_price) || 0;
};

const formatNaira = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount || 0);

const lineItemError = (index, field) => props.errors[`line_items.${index}.${field}`] || '';

const resetPendingExtra = () => {
    pendingExtra.value = { kind: '', label: '', unit_price: '' };
};

const confirmExtraCharge = () => {
    if (!canConfirmExtra.value) {
        return;
    }

    const option = props.extraCharges.find((item) => item.kind === pendingExtra.value.kind);

    emit('update:modelValue', [
        ...props.modelValue,
        {
            kind: pendingExtra.value.kind,
            label: pendingExtra.value.kind === 'other'
                ? pendingExtra.value.label.trim()
                : (option?.label || 'Charge'),
            quantity: 1,
            unit: 'fee',
            unit_price: Number(pendingExtra.value.unit_price),
        },
    ]);

    resetPendingExtra();
};

const confirmMaterialRow = () => {
    if (!canConfirmMaterial.value) {
        return;
    }

    emit('update:modelValue', [
        ...props.modelValue,
        {
            kind: 'materials',
            label: '',
            quantity: 0,
            unit: 'unit',
            unit_price: 0,
        },
    ]);
};

const removeRow = (index) => {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
};

defineExpose({ totals });
</script>

<style scoped>
.quote-inline-field :deep(.form-field) {
    margin-bottom: 0;
}

.quote-inline-field :deep(.form-control),
.quote-inline-field :deep(.form-select-trigger) {
    min-height: 2.875rem;
    padding-top: 0.625rem;
    padding-bottom: 0.625rem;
}

.quote-material-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.625rem;
    align-items: center;
}

@media (min-width: 640px) {
    .quote-material-grid {
        grid-template-columns: minmax(0, 1.4fr) minmax(4.5rem, 5.5rem) minmax(5.5rem, 6.5rem) minmax(5.5rem, 6.5rem) 2.875rem;
        gap: 0.5rem;
    }
}

.quote-material-delete {
    justify-self: end;
}

.quote-charge-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (min-width: 640px) {
    .quote-charge-row {
        display: grid;
        grid-template-columns: minmax(7.5rem, 9.5rem) minmax(0, 1fr) 2.875rem;
        align-items: center;
        gap: 0.75rem;
    }
}

.quote-charge-label {
    width: 6.75rem;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .quote-charge-label {
        width: auto;
    }
}

.quote-charge-field {
    min-width: 0;
    flex: 1 1 0%;
}

@media (min-width: 640px) {
    .quote-charge-field {
        flex: none;
    }
}

.quote-charge-action {
    display: none;
    width: 2.875rem;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .quote-charge-action {
        display: block;
    }
}

@media (min-width: 640px) {
    .quote-material-delete {
        justify-self: center;
    }
}

.quote-add-btn {
    @apply inline-flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-base-action/25 bg-tint/40 px-3.5 py-2.5 text-xs font-bold text-base-action transition-colors hover:border-base-action/40 hover:bg-tint disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto;
}

.quote-icon-btn {
    @apply inline-flex h-[2.875rem] w-[2.875rem] shrink-0 items-center justify-center rounded-2xl bg-white text-ink/45 ring-1 ring-ink/10 transition-colors hover:bg-pale hover:text-ink/70;
}

.quote-icon-btn--danger {
    @apply text-ink/30 ring-0 hover:bg-red-50 hover:text-red-600;
}
</style>
