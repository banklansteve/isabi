<template>
    <div class="form-field" :class="{ 'has-error': !!error }">
        <div v-if="label || $slots.label || $slots.action" class="mb-1.5 flex items-center justify-between gap-3">
            <label
                v-if="label || $slots.label"
                :for="inputId"
                class="block text-sm font-semibold text-ink/70"
            >
                <slot name="label">{{ label }}</slot>
            </label>
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </div>

        <slot :id="inputId" :described-by="describedBy" />

        <p v-if="hint && !error" :id="hintId" class="mt-1.5 text-xs font-medium text-ink/40">
            {{ hint }}
        </p>
        <p v-if="error" :id="errorId" class="mt-1.5 text-xs font-semibold text-red-500" role="alert">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: '',
    },
});

const uid = useId();
const inputId = computed(() => props.id || `field-${uid}`);
const hintId = computed(() => `${inputId.value}-hint`);
const errorId = computed(() => `${inputId.value}-error`);
const describedBy = computed(() => {
    if (props.error) {
        return errorId.value;
    }
    if (props.hint) {
        return hintId.value;
    }
    return undefined;
});
</script>
