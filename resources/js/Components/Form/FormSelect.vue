<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template #default="{ id: fieldId, describedBy }">
            <div ref="rootRef" class="relative">
                <button
                    :id="fieldId"
                    type="button"
                    class="form-control form-select-trigger"
                    :class="[
                        icon ? 'ps-11' : 'ps-4',
                        {
                            'has-error': !!error,
                            'is-disabled': disabled,
                            'is-open': open,
                            'is-placeholder': !selectedLabel,
                        },
                    ]"
                    :disabled="disabled"
                    :aria-expanded="open"
                    aria-haspopup="listbox"
                    :aria-controls="listboxId"
                    :aria-invalid="!!error || undefined"
                    :aria-describedby="describedBy"
                    @click="toggle"
                    @keydown="onTriggerKeydown"
                >
                    <i
                        v-if="icon"
                        class="form-control-icon pointer-events-none"
                        :class="[icon, { 'text-base': open || !!model }]"
                        aria-hidden="true"
                    />
                    <span class="truncate">{{ selectedLabel || placeholder }}</span>
                    <i
                        class="ti ti-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-ink/30 transition-transform duration-200"
                        :class="{ 'rotate-180 text-base': open }"
                        aria-hidden="true"
                    />
                </button>

                <Transition name="select-panel">
                    <div
                        v-if="open"
                        :id="listboxId"
                        class="form-select-panel"
                        role="listbox"
                        :aria-labelledby="fieldId"
                    >
                        <div v-if="searchable" class="sticky top-0 z-10 border-b border-ink/6 bg-white p-2">
                            <div class="relative">
                                <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                                <input
                                    ref="searchRef"
                                    v-model="query"
                                    type="search"
                                    class="w-full rounded-xl border border-ink/10 bg-pale py-2.5 ps-9 pe-3 text-sm font-medium text-ink outline-none placeholder:text-ink/30 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                                    :placeholder="searchPlaceholder"
                                    @keydown="onSearchKeydown"
                                />
                            </div>
                        </div>

                        <div class="max-h-60 overflow-y-auto p-1.5">
                            <p
                                v-if="!filteredOptions.length"
                                class="px-3 py-6 text-center text-sm font-medium text-ink/40"
                            >
                                No matches
                            </p>
                            <button
                                v-for="(option, index) in filteredOptions"
                                :key="optionValue(option)"
                                type="button"
                                role="option"
                                class="tap-target flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition-colors"
                                :class="
                                    isSelected(option)
                                        ? 'bg-tint text-ink'
                                        : index === activeIndex
                                          ? 'bg-pale text-ink'
                                          : 'text-ink/75 hover:bg-pale'
                                "
                                :aria-selected="isSelected(option)"
                                @mouseenter="activeIndex = index"
                                @click="select(option)"
                            >
                                <span class="min-w-0 flex-1 truncate">{{ optionLabel(option) }}</span>
                                <i
                                    v-if="isSelected(option)"
                                    class="ti ti-check shrink-0 text-base text-base"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';

const model = defineModel({ type: [String, Number, null], default: '' });

const props = defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    /** When options are objects: key for value */
    valueKey: { type: String, default: 'value' },
    /** When options are objects: key for label */
    labelKey: { type: String, default: 'label' },
    icon: { type: String, default: '' },
    placeholder: { type: String, default: 'Select an option' },
    searchable: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Search…' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['change', 'blur']);

const open = ref(false);
const query = ref('');
const activeIndex = ref(-1);
const rootRef = ref(null);
const searchRef = ref(null);
const listboxId = `listbox-${useId()}`;

const optionValue = (option) =>
    typeof option === 'object' && option !== null ? option[props.valueKey] : option;

const optionLabel = (option) =>
    typeof option === 'object' && option !== null ? option[props.labelKey] : String(option);

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return props.options;
    }
    return props.options.filter((option) =>
        optionLabel(option).toLowerCase().includes(q),
    );
});

const selectedLabel = computed(() => {
    const match = props.options.find((option) => optionValue(option) === model.value);
    return match ? optionLabel(match) : '';
});

const isSelected = (option) => optionValue(option) === model.value;

const close = () => {
    if (!open.value) {
        return;
    }
    open.value = false;
    query.value = '';
    activeIndex.value = -1;
    emit('blur');
};

const openPanel = async () => {
    if (props.disabled) {
        return;
    }
    open.value = true;
    const selectedIdx = filteredOptions.value.findIndex((option) => isSelected(option));
    activeIndex.value = selectedIdx >= 0 ? selectedIdx : 0;
    await nextTick();
    if (props.searchable) {
        searchRef.value?.focus();
    }
};

const toggle = () => {
    if (open.value) {
        close();
    } else {
        openPanel();
    }
};

const select = (option) => {
    model.value = optionValue(option);
    emit('change', model.value);
    close();
};

const onTriggerKeydown = (event) => {
    if (props.disabled) {
        return;
    }
    if (['ArrowDown', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        if (!open.value) {
            openPanel();
        }
    }
};

const onSearchKeydown = (event) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
    }
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = Math.min(activeIndex.value + 1, filteredOptions.value.length - 1);
        return;
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
        return;
    }
    if (event.key === 'Enter' && activeIndex.value >= 0) {
        event.preventDefault();
        const option = filteredOptions.value[activeIndex.value];
        if (option !== undefined) {
            select(option);
        }
    }
};

const onPointerDown = (event) => {
    if (!rootRef.value?.contains(event.target)) {
        close();
    }
};

watch(
    () => props.disabled,
    (disabled) => {
        if (disabled) {
            close();
        }
    },
);

watch(query, () => {
    activeIndex.value = filteredOptions.value.length ? 0 : -1;
});

onMounted(() => {
    document.addEventListener('pointerdown', onPointerDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onPointerDown);
});
</script>
