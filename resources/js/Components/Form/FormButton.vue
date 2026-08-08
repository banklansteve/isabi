<template>
    <button
        :type="type"
        class="tap-target group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-2xl px-5 py-3.5 text-sm font-bold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50"
        :class="variantClass"
        :disabled="disabled || loading"
        @click="emit('click', $event)"
    >
        <span
            v-if="variant === 'primary' || variant === 'accent'"
            class="pointer-events-none absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-500 group-hover:translate-x-full"
            aria-hidden="true"
        />
        <span v-if="loading" class="inline-flex items-center gap-2.5">
            <span class="btn-dots" aria-hidden="true">
                <span class="btn-dots__dot" />
                <span class="btn-dots__dot" />
                <span class="btn-dots__dot" />
            </span>
            {{ loadingLabel || label }}
        </span>
        <span v-else class="inline-flex items-center gap-2">
            <i v-if="iconLeft" :class="iconLeft" aria-hidden="true" />
            <slot>{{ label }}</slot>
            <i
                v-if="iconRight"
                :class="[iconRight, 'transition-transform duration-200 group-hover:translate-x-0.5']"
                aria-hidden="true"
            />
        </span>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    label: { type: String, default: '' },
    loadingLabel: { type: String, default: '' },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'accent', 'secondary', 'ghost'].includes(value),
    },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    iconLeft: { type: String, default: '' },
    iconRight: { type: String, default: '' },
    block: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const variantClass = computed(() => {
    const base = props.block ? 'w-full' : '';
    const variants = {
        primary:
            'bg-base-action text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover active:bg-deep',
        accent:
            'bg-coral text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] hover:bg-coral-deep',
        secondary:
            'border border-base-action/35 bg-white text-base-action shadow-sm hover:border-base-action/55 hover:bg-white hover:text-base-hover',
        ghost: 'bg-transparent text-ink/60 hover:bg-ink/5 hover:text-ink',
    };
    return [base, variants[props.variant]].join(' ');
});
</script>

<style scoped>
.btn-dots {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    height: 1rem;
}

.btn-dots__dot {
    display: block;
    height: 0.35rem;
    width: 0.35rem;
    border-radius: 9999px;
    background: currentColor;
    animation: btn-dot-pulse 1s ease-in-out infinite;
}

.btn-dots__dot:nth-child(2) {
    animation-delay: 0.15s;
}

.btn-dots__dot:nth-child(3) {
    animation-delay: 0.3s;
}

@keyframes btn-dot-pulse {
    0%,
    80%,
    100% {
        opacity: 0.35;
        transform: translateY(0) scale(0.85);
    }
    40% {
        opacity: 1;
        transform: translateY(-2px) scale(1);
    }
}
</style>
