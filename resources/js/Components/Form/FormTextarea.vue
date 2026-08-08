<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template #default="{ id: fieldId, describedBy }">
            <div class="form-control-shell form-control-shell--textarea" :class="{ 'has-error': !!error, 'is-disabled': disabled }">
                <i
                    v-if="icon"
                    class="form-control-icon !top-4 translate-y-0"
                    :class="icon"
                    aria-hidden="true"
                />
                <textarea
                    :id="fieldId"
                    ref="inputRef"
                    v-model="model"
                    class="form-control min-h-[6.5rem] resize-none py-3.5"
                    :class="[icon ? 'ps-11' : 'ps-4', 'pe-4']"
                    :name="name"
                    :rows="rows"
                    :placeholder="placeholder"
                    :required="required"
                    :disabled="disabled"
                    :readonly="readonly"
                    :aria-invalid="!!error || undefined"
                    :aria-describedby="describedBy"
                    @blur="$emit('blur', $event)"
                    @focus="$emit('focus', $event)"
                />
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import { ref } from 'vue';

const model = defineModel({ type: String, default: '' });

defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    name: { type: String, default: undefined },
    icon: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    rows: { type: [Number, String], default: 3 },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
});

defineEmits(['blur', 'focus']);

const inputRef = ref(null);

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>
