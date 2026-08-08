<template>
    <div>
        <p v-if="label" class="mb-2 text-sm font-semibold text-ink">
            {{ label }}
            <span v-if="required" class="text-coral">*</span>
        </p>
        <div class="flex items-center gap-1.5" role="radiogroup" :aria-label="label || 'Rating'">
            <button
                v-for="n in 5"
                :key="n"
                type="button"
                class="tap-target flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-150"
                :class="n <= (hover || modelValue)
                    ? 'bg-amber-50 text-amber-500 ring-1 ring-amber-200/80 scale-[1.02]'
                    : 'bg-pale text-ink/25 ring-1 ring-ink/[0.06] hover:bg-tint/60 hover:text-amber-400'"
                :aria-checked="modelValue === n"
                role="radio"
                :aria-label="`${n} star${n === 1 ? '' : 's'}`"
                @click="emit('update:modelValue', n)"
                @mouseenter="hover = n"
                @mouseleave="hover = 0"
            >
                <i class="ti ti-star-filled text-xl" aria-hidden="true" />
            </button>
        </div>
        <p v-if="hint && !error" class="mt-2 text-xs font-medium text-ink/45">{{ hint }}</p>
        <p v-if="error" class="mt-2 text-xs font-semibold text-coral">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: { type: Number, default: 0 },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const hover = ref(0);
</script>
