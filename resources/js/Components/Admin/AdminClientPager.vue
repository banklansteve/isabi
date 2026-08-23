<template>
    <nav v-if="pages > 1" class="flex flex-wrap items-center justify-between gap-3 pt-4">
        <p class="text-[12px] font-semibold text-ink/40">
            {{ from }}–{{ to }} of {{ total }}
        </p>
        <div class="flex items-center gap-1">
            <button
                type="button"
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg text-[13px] font-semibold text-ink/50 transition-colors hover:bg-tint hover:text-deep disabled:opacity-30"
                :disabled="page <= 1"
                @click="$emit('update:page', page - 1)"
            >
                <i class="ti ti-chevron-left" aria-hidden="true" />
            </button>
            <button
                v-for="item in windowed"
                :key="item"
                type="button"
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-[13px] font-semibold transition-colors"
                :class="item === page ? 'bg-base-action text-white' : 'text-ink/50 hover:bg-tint hover:text-deep'"
                @click="$emit('update:page', item)"
            >
                {{ item }}
            </button>
            <button
                type="button"
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg text-[13px] font-semibold text-ink/50 transition-colors hover:bg-tint hover:text-deep disabled:opacity-30"
                :disabled="page >= pages"
                @click="$emit('update:page', page + 1)"
            >
                <i class="ti ti-chevron-right" aria-hidden="true" />
            </button>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    page: { type: Number, required: true },
    pages: { type: Number, required: true },
    total: { type: Number, required: true },
    perPage: { type: Number, default: 20 },
});

defineEmits(['update:page']);

const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1));
const to = computed(() => Math.min(props.page * props.perPage, props.total));

const windowed = computed(() => {
    const start = Math.max(1, props.page - 2);
    const end = Math.min(props.pages, start + 4);
    const first = Math.max(1, end - 4);
    const items = [];
    for (let i = first; i <= end; i += 1) {
        items.push(i);
    }
    return items;
});
</script>
