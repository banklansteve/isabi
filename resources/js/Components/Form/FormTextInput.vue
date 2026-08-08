<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template v-if="$slots.action" #action>
            <slot name="action" />
        </template>

        <template #default="{ id: fieldId, describedBy }">
            <div class="form-control-shell" :class="{ 'has-error': !!error, 'is-disabled': disabled }">
                <i
                    v-if="icon"
                    class="form-control-icon"
                    :class="icon"
                    aria-hidden="true"
                />
                <input
                    :id="fieldId"
                    ref="inputRef"
                    v-model="model"
                    class="form-control"
                    :class="[
                        icon ? 'ps-11' : 'ps-4',
                        $slots.suffix || clearable ? 'pe-12' : 'pe-4',
                        inputClass,
                    ]"
                    :type="type"
                    :name="name"
                    :placeholder="placeholder"
                    :required="required"
                    :disabled="disabled"
                    :readonly="readonly"
                    :autocomplete="autocomplete"
                    :inputmode="inputmode"
                    :autofocus="autofocus"
                    :aria-invalid="!!error || undefined"
                    :aria-describedby="describedBy"
                    v-bind="$attrs"
                    @blur="$emit('blur', $event)"
                    @focus="$emit('focus', $event)"
                    @input="$emit('input', $event)"
                />
                <div
                    v-if="$slots.suffix || (clearable && model)"
                    class="absolute inset-y-0 right-0 flex items-center pe-1.5"
                >
                    <slot name="suffix">
                        <button
                            v-if="clearable && model"
                            type="button"
                            class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/35 transition-colors hover:text-ink"
                            aria-label="Clear"
                            @click="model = ''"
                        >
                            <i class="ti ti-x text-base" aria-hidden="true" />
                        </button>
                    </slot>
                </div>
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import { onMounted, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number], default: '' });

const props = defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    type: { type: String, default: 'text' },
    name: { type: String, default: undefined },
    icon: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: undefined },
    inputmode: { type: String, default: undefined },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    autofocus: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    inputClass: { type: String, default: '' },
});

defineEmits(['blur', 'focus']);

const inputRef = ref(null);

onMounted(() => {
    if (props.autofocus) {
        inputRef.value?.focus();
    }
});

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>
