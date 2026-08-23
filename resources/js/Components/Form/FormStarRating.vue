<template>
    <div :class="align === 'center' ? 'text-center' : ''">
        <p v-if="label" class="mb-3 text-sm font-semibold text-ink">
            {{ label }}
            <span v-if="required" class="text-coral">*</span>
        </p>

        <div
            class="inline-flex select-none items-center gap-0.5 rounded-2xl outline-none sm:gap-1.5"
            role="slider"
            :aria-label="label || 'Rating'"
            :aria-valuemin="0.5"
            :aria-valuemax="5"
            :aria-valuenow="modelValue || undefined"
            :aria-valuetext="valueLabel"
            tabindex="0"
            @keydown="onKeydown"
            @mouseleave="hover = 0"
        >
            <button
                v-for="n in 5"
                :key="n"
                type="button"
                class="relative flex h-[3.15rem] w-[3.15rem] shrink-0 items-center justify-center rounded-xl transition-transform duration-150 ease-out active:scale-90 sm:h-14 sm:w-14"
                :aria-label="`${n} stars (left half for ${n - 0.5})`"
                @click="onStarClick(n, $event)"
                @mousemove="onStarMove(n, $event)"
                @mouseenter="onStarMove(n, $event)"
                @focus="hover = n"
            >
                <svg
                    class="pointer-events-none h-10 w-10 transition-transform duration-200 ease-out sm:h-11 sm:w-11"
                    :class="displayValue >= n - 0.5 ? 'scale-105' : 'scale-100'"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <defs>
                        <clipPath :id="`sr-${uid}-${n}`">
                            <rect x="0" y="0" width="12" height="24" />
                        </clipPath>
                        <linearGradient :id="`sr-gold-${uid}`" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#FFC64D" />
                            <stop offset="100%" stop-color="#F0980C" />
                        </linearGradient>
                    </defs>

                    <path
                        :d="STAR_PATH"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.9"
                        stroke-linejoin="round"
                        class="transition-colors duration-200"
                        :class="displayValue >= n ? 'text-[#E5940F]' : 'text-ink/25'"
                    />
                    <path
                        v-if="displayValue >= n - 0.5"
                        :d="STAR_PATH"
                        :fill="`url(#sr-gold-${uid})`"
                        :clip-path="displayValue >= n ? undefined : `url(#sr-${uid}-${n})`"
                    />
                </svg>
            </button>
        </div>

        <div class="mt-2 flex min-h-[1.75rem] items-center gap-2" :class="rowAlignClass">
            <template v-if="displayValue > 0">
                <span
                    class="inline-flex items-center rounded-full bg-[#F5A524]/15 px-3 py-1 text-xs font-extrabold tracking-tight text-[#A66300]"
                >
                    {{ descriptor }}
                </span>
                <span class="text-xs font-bold tabular-nums text-ink/35">
                    {{ displayValue.toFixed(1) }}/5
                </span>
            </template>
            <p v-else-if="hint && !error" class="text-xs font-medium text-ink/40">
                {{ hint }}
            </p>
        </div>

        <p v-if="error" class="mt-0.5 text-xs font-semibold text-coral">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const STAR_PATH =
    'M12 2.6l2.75 5.93 6.47.74-4.8 4.4 1.33 6.37L12 16.96l-5.75 3.08 1.33-6.37-4.8-4.4 6.47-.74L12 2.6z';

const props = defineProps({
    modelValue: { type: Number, default: 0 },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    align: { type: String, default: 'start' },
    descriptors: {
        type: Array,
        default: () => ['Poor', 'Fair', 'Good', 'Great', 'Exceptional'],
    },
});

const emit = defineEmits(['update:modelValue']);

const hover = ref(0);
const uid = Math.random().toString(36).slice(2, 9);

const displayValue = computed(() => (hover.value > 0 ? hover.value : Number(props.modelValue) || 0));

const rowAlignClass = computed(() =>
    props.align === 'center' ? 'justify-center' : 'justify-start',
);

const descriptor = computed(() => {
    const index = Math.ceil(displayValue.value) - 1;
    return props.descriptors[Math.min(props.descriptors.length - 1, Math.max(0, index))];
});

const valueLabel = computed(() => {
    const v = Number(props.modelValue) || 0;
    return v > 0 ? `${v.toFixed(1)} / 5` : '';
});

const valueFromEvent = (n, event) => {
    const rect = event.currentTarget?.getBoundingClientRect();
    if (!rect) {
        return n;
    }
    return (event.clientX ?? 0) - rect.left < rect.width / 2 ? n - 0.5 : n;
};

const setValue = (value) => {
    const stepped = Math.round(Number(value) * 2) / 2;
    emit('update:modelValue', Math.min(5, Math.max(0.5, stepped)));
};

const onStarClick = (n, event) => {
    setValue(valueFromEvent(n, event));
};

const onStarMove = (n, event) => {
    hover.value = valueFromEvent(n, event);
};

const onKeydown = (e) => {
    const current = Number(props.modelValue) || 0;
    if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
        e.preventDefault();
        setValue(Math.min(5, current + 0.5 || 0.5));
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
        e.preventDefault();
        setValue(Math.max(0.5, current - 0.5));
    } else if (e.key === 'Home') {
        e.preventDefault();
        setValue(0.5);
    } else if (e.key === 'End') {
        e.preventDefault();
        setValue(5);
    }
};
</script>
