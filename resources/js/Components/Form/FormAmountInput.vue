<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template v-if="$slots.action" #action>
            <slot name="action" />
        </template>

        <template #default="{ id: fieldId, describedBy }">
            <div class="form-control-shell" :class="{ 'has-error': !!error, 'is-disabled': disabled || readonly }">
                <i
                    v-if="icon"
                    class="form-control-icon"
                    :class="icon"
                    aria-hidden="true"
                />
                <input
                    :id="fieldId"
                    ref="inputRef"
                    :value="display"
                    class="form-control"
                    :class="[
                        icon ? 'ps-11' : 'ps-4',
                        'pe-4',
                        inputClass,
                    ]"
                    type="text"
                    inputmode="decimal"
                    :name="name"
                    :placeholder="placeholder"
                    :required="required"
                    :disabled="disabled"
                    :readonly="readonly"
                    :autocomplete="autocomplete"
                    :autofocus="autofocus"
                    :aria-invalid="!!error || undefined"
                    :aria-describedby="describedBy"
                    @focus="onFocus"
                    @blur="onBlur"
                    @input="onInput"
                />
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import {
    AMOUNT_INPUT_PATTERN,
    formatAmountInput,
    formatAmountWhileTyping,
    parseAmountInput,
} from '@/utils/amountInput';
import { onMounted, ref, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number], default: '' });

const props = defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    name: { type: String, default: undefined },
    icon: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: undefined },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    autofocus: { type: Boolean, default: false },
    inputClass: { type: String, default: '' },
});

const emit = defineEmits(['blur', 'focus']);

const inputRef = ref(null);
const display = ref('');
const isFocused = ref(false);

const syncDisplayFromModel = () => {
    if (isFocused.value) {
        return;
    }

    display.value = model.value === '' || model.value === null || model.value === undefined
        ? ''
        : formatAmountInput(model.value);
};

watch(model, syncDisplayFromModel, { immediate: true });

const onFocus = (event) => {
    isFocused.value = true;

    if (model.value !== '' && model.value !== null && model.value !== undefined) {
        display.value = String(model.value).replace(/,/g, '');
    }

    emit('focus', event);
};

const onBlur = (event) => {
    isFocused.value = false;

    const parsed = parseAmountInput(display.value);
    model.value = parsed === '' ? '' : parsed;
    display.value = parsed === '' ? '' : formatAmountInput(parsed);

    emit('blur', event);
};

const onInput = (event) => {
    const raw = event.target.value.replace(/,/g, '');

    if (raw !== '' && !AMOUNT_INPUT_PATTERN.test(raw)) {
        event.target.value = display.value;

        return;
    }

    display.value = formatAmountWhileTyping(raw);

    if (raw === '' || raw === '.') {
        model.value = '';

        return;
    }

    if (raw.endsWith('.')) {
        model.value = Number(raw.slice(0, -1)) || 0;

        return;
    }

    model.value = Number(raw);
};

onMounted(() => {
    if (props.autofocus) {
        inputRef.value?.focus();
    }
});

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>
