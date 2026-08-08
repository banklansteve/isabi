<template>
    <FormField :label="label" :hint="hint" :error="error">
        <div
            class="grid max-h-[min(22rem,48vh)] grid-cols-1 gap-2 overflow-y-auto pe-1 sm:grid-cols-2"
            role="listbox"
            :aria-label="label || 'Options'"
        >
            <button
                v-for="option in options"
                :key="optionValue(option)"
                type="button"
                role="option"
                class="tap-target flex items-center gap-3 rounded-2xl border px-3.5 py-3 text-left text-sm font-semibold transition-all duration-200"
                :class="
                    isSelected(option)
                        ? 'border-base bg-tint text-ink shadow-[0_0_0_3px_rgba(47,111,237,0.12)]'
                        : 'border-ink/8 bg-white text-ink/75 hover:border-ink/20 hover:bg-pale'
                "
                :aria-selected="isSelected(option)"
                @click="select(option)"
            >
                <span
                    v-if="showIcons"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-base"
                    :class="isSelected(option) ? 'bg-base text-white' : 'bg-pale text-ink/40'"
                >
                    <i :class="optionIcon(option)" aria-hidden="true" />
                </span>
                <span class="leading-snug">{{ optionLabel(option) }}</span>
                <i
                    v-if="isSelected(option)"
                    class="ti ti-check ms-auto text-base text-base"
                    aria-hidden="true"
                />
            </button>
        </div>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';

const model = defineModel({ type: [String, Number], default: '' });

const props = defineProps({
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'value' },
    labelKey: { type: String, default: 'label' },
    iconKey: { type: String, default: 'icon' },
    showIcons: { type: Boolean, default: true },
    iconResolver: { type: Function, default: null },
    fallbackIcon: { type: String, default: 'ti ti-briefcase' },
});

const emit = defineEmits(['change']);

const optionValue = (option) =>
    typeof option === 'object' && option !== null ? option[props.valueKey] : option;

const optionLabel = (option) =>
    typeof option === 'object' && option !== null ? option[props.labelKey] : String(option);

const optionIcon = (option) => {
    if (props.iconResolver) {
        return props.iconResolver(optionLabel(option), option);
    }
    if (typeof option === 'object' && option !== null && option[props.iconKey]) {
        return option[props.iconKey];
    }
    return props.fallbackIcon;
};

const isSelected = (option) => optionValue(option) === model.value;

const select = (option) => {
    model.value = optionValue(option);
    emit('change', model.value);
};
</script>
