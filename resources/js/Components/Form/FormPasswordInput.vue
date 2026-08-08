<template>
    <FormTextInput
        v-model="model"
        :id="id"
        :label="label"
        :hint="hint"
        :error="error"
        :type="visible ? 'text' : 'password'"
        :name="name"
        :icon="icon"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :required="required"
        :disabled="disabled"
        :autofocus="autofocus"
        input-class="pe-12"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
        @input="$emit('input', $event)"
    >
        <template v-if="$slots.action" #action>
            <slot name="action" />
        </template>
        <template #suffix>
            <button
                type="button"
                class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/35 transition-colors hover:text-ink"
                :aria-label="visible ? 'Hide password' : 'Show password'"
                @click="visible = !visible"
            >
                <i :class="visible ? 'ti ti-eye-off' : 'ti ti-eye'" class="text-lg" aria-hidden="true" />
            </button>
        </template>
    </FormTextInput>
</template>

<script setup>
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { ref } from 'vue';

const model = defineModel({ type: String, default: '' });

defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    name: { type: String, default: undefined },
    icon: { type: String, default: 'ti ti-lock' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: 'current-password' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    autofocus: { type: Boolean, default: false },
});

defineEmits(['blur', 'focus', 'input']);

const visible = ref(false);
</script>
