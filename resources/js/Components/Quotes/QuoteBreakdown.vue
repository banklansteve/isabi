<template>
    <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-ink/[0.06]">
        <!-- Charges -->
        <div v-if="charges.length" class="border-b border-ink/[0.05]">
            <div class="border-b border-ink/[0.04] bg-pale/50 px-4 py-2.5 sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/40">Charges</p>
            </div>
            <ul class="divide-y divide-ink/[0.05]">
                <li
                    v-for="(row, i) in charges"
                    :key="`charge-${i}`"
                    class="flex items-start justify-between gap-3 px-4 py-3.5 sm:px-5"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-snug text-ink">
                            {{ row.display_label || row.label }}
                        </p>
                        <p v-if="row.kind === 'other' && row.label" class="mt-0.5 text-[11px] font-medium text-ink/40">
                            Other charge
                        </p>
                    </div>
                    <p class="shrink-0 text-sm font-bold tabular-nums text-ink">
                        {{ formatNaira(row.line_total) }}
                    </p>
                </li>
            </ul>
        </div>

        <!-- Materials -->
        <div v-if="materials.length" class="border-b border-ink/[0.05]">
            <div class="flex items-center justify-between gap-3 border-b border-ink/[0.04] bg-pale/50 px-4 py-2.5 sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/40">Materials / parts</p>
                <p class="text-xs font-bold tabular-nums text-ink/50">{{ formatNaira(materialsTotal) }}</p>
            </div>
            <ul class="divide-y divide-ink/[0.05]">
                <li
                    v-for="(row, i) in materials"
                    :key="`mat-${i}`"
                    class="flex items-start justify-between gap-3 px-4 py-3.5 sm:px-5"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-snug text-ink">
                            {{ row.label || 'Material' }}
                        </p>
                        <p class="mt-0.5 text-[11px] font-medium text-ink/40">
                            {{ formatQty(row.quantity) }} × {{ formatNaira(row.unit_price) }}
                        </p>
                    </div>
                    <p class="shrink-0 text-sm font-bold tabular-nums text-ink">
                        {{ formatNaira(row.line_total) }}
                    </p>
                </li>
            </ul>
        </div>

        <!-- Totals -->
        <div class="bg-gradient-to-b from-pale/40 to-white px-4 py-4 sm:px-5">
            <dl class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="font-medium text-ink/45">Subtotal</dt>
                    <dd class="font-semibold tabular-nums text-ink">{{ formatNaira(subtotal) }}</dd>
                </div>
                <div v-if="discount > 0" class="flex items-center justify-between gap-4">
                    <dt class="font-medium text-ink/45">Discount</dt>
                    <dd class="font-semibold tabular-nums text-emerald-700">− {{ formatNaira(discount) }}</dd>
                </div>
                <div v-if="vat > 0" class="flex items-center justify-between gap-4">
                    <dt class="font-medium text-ink/45">VAT ({{ vatRate }}%)</dt>
                    <dd class="font-semibold tabular-nums text-ink">{{ formatNaira(vat) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-ink/10 pt-3">
                    <dt class="text-base font-bold text-ink">Total</dt>
                    <dd class="text-xl font-bold tabular-nums tracking-tight text-base-action">
                        {{ formatNaira(total) }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    lineItems: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    discount: { type: Number, default: 0 },
    vat: { type: Number, default: 0 },
    vatRate: { type: [Number, String], default: 0 },
    total: { type: Number, default: 0 },
});

const charges = computed(() =>
    props.lineItems.filter((row) => row.kind !== 'materials'),
);

const materials = computed(() =>
    props.lineItems.filter((row) => row.kind === 'materials'),
);

const materialsTotal = computed(() =>
    materials.value.reduce((sum, row) => sum + (Number(row.line_total) || 0), 0),
);

const formatNaira = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount || 0);

const formatQty = (qty) => {
    const n = Number(qty) || 0;

    return n % 1 === 0 ? String(n) : n.toFixed(2);
};
</script>
