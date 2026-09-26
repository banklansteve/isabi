<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template #default="{ id: fieldId, describedBy }">
            <div ref="rootRef" class="relative">
                <div
                    v-if="model.length"
                    class="mb-2.5 flex flex-wrap gap-2"
                    role="list"
                    :aria-label="label || 'Selected skills'"
                >
                    <button
                        v-for="item in model"
                        :key="item"
                        type="button"
                        role="listitem"
                        class="tap-target group inline-flex max-w-full items-center gap-1.5 rounded-full bg-base-action px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-base-hover"
                        :aria-label="`Remove ${item}`"
                        @click="remove(item)"
                    >
                        <span class="truncate">{{ item }}</span>
                        <i
                            class="ti ti-x shrink-0 text-[13px] opacity-80 transition group-hover:opacity-100"
                            aria-hidden="true"
                        />
                    </button>
                </div>

                <div class="relative">
                    <div
                        class="form-control flex min-h-[3.25rem] flex-wrap items-center gap-2 py-2.5"
                        :class="[
                            icon ? 'ps-11' : 'ps-4',
                            {
                                'has-error': !!error,
                                'is-disabled': disabled || atLimit,
                                'ring-4 ring-base/15 border-base': open,
                            },
                        ]"
                        @click="focusInput"
                    >
                        <i
                            v-if="icon"
                            class="form-control-icon pointer-events-none"
                            :class="[icon, { 'text-base': open || model.length }]"
                            aria-hidden="true"
                        />
                        <input
                            :id="fieldId"
                            ref="inputRef"
                            v-model="query"
                            type="search"
                            autocomplete="off"
                            class="min-w-[8rem] flex-1 border-0 bg-transparent p-0 text-sm font-medium text-ink outline-none placeholder:text-ink/30 disabled:cursor-not-allowed"
                            :placeholder="atLimit ? `Maximum ${max} selected` : placeholder"
                            :disabled="disabled || atLimit"
                            :aria-expanded="open"
                            aria-autocomplete="list"
                            aria-haspopup="listbox"
                            :aria-controls="listboxId"
                            :aria-invalid="!!error || undefined"
                            :aria-describedby="describedBy"
                            @focus="openPanel"
                            @keydown="onKeydown"
                        />
                        <span
                            v-if="max"
                            class="shrink-0 pe-1 text-[11px] font-bold tabular-nums text-ink/35"
                        >
                            {{ model.length }}/{{ max }}
                        </span>
                    </div>

                    <Transition name="select-panel">
                        <div
                            v-if="open && !atLimit && (filteredOptions.length || query.trim())"
                            :id="listboxId"
                            ref="panelRef"
                            class="form-select-panel absolute left-0 right-0 top-[calc(100%+0.4rem)] z-30 max-h-60 overflow-hidden"
                            role="listbox"
                        >
                            <div class="max-h-60 overflow-y-auto p-1.5">
                                <p
                                    v-if="!filteredOptions.length && !allowCustom"
                                    class="px-3 py-6 text-center text-sm font-medium text-ink/40"
                                >
                                    No matches
                                </p>

                                <button
                                    v-for="(option, index) in filteredOptions"
                                    :key="option"
                                    type="button"
                                    role="option"
                                    class="tap-target flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition-colors duration-150"
                                    :class="
                                        index === activeIndex
                                            ? 'bg-pale text-ink'
                                            : 'text-ink/75 hover:bg-pale'
                                    "
                                    :aria-selected="false"
                                    @mouseenter="activeIndex = index"
                                    @mousedown.prevent="add(option)"
                                >
                                    <i class="ti ti-plus shrink-0 text-base text-ink/35" aria-hidden="true" />
                                    <span class="min-w-0 flex-1 truncate">{{ option }}</span>
                                </button>

                                <button
                                    v-if="showCustomOption"
                                    type="button"
                                    role="option"
                                    class="tap-target mt-0.5 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-base-action transition-colors hover:bg-tint"
                                    :class="{ 'bg-tint': activeIndex === filteredOptions.length }"
                                    @mouseenter="activeIndex = filteredOptions.length"
                                    @mousedown.prevent="addCustom"
                                >
                                    <i class="ti ti-plus shrink-0" aria-hidden="true" />
                                    <span class="min-w-0 flex-1 truncate">
                                        Add “{{ query.trim() }}”
                                    </span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';

const model = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    icon: { type: String, default: 'ti ti-search' },
    placeholder: { type: String, default: 'Search and add…' },
    disabled: { type: Boolean, default: false },
    max: { type: Number, default: 8 },
    allowCustom: { type: Boolean, default: true },
    maxLength: { type: Number, default: 40 },
});

const emit = defineEmits(['change']);

const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const rootRef = ref(null);
const inputRef = ref(null);
const panelRef = ref(null);
const listboxId = `multiselect-${useId()}`;

const atLimit = computed(() => props.max > 0 && model.value.length >= props.max);

const selectedSet = computed(
    () => new Set(model.value.map((item) => String(item).toLowerCase())),
);

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    return props.options
        .filter((option) => !selectedSet.value.has(String(option).toLowerCase()))
        .filter((option) => !q || String(option).toLowerCase().includes(q))
        .slice(0, 40);
});

const showCustomOption = computed(() => {
    if (!props.allowCustom) {
        return false;
    }
    const q = query.value.trim();
    if (!q || q.length > props.maxLength) {
        return false;
    }
    if (selectedSet.value.has(q.toLowerCase())) {
        return false;
    }
    return !filteredOptions.value.some((option) => String(option).toLowerCase() === q.toLowerCase());
});

const openPanel = () => {
    if (props.disabled || atLimit.value) {
        return;
    }
    open.value = true;
    activeIndex.value = 0;
};

const closePanel = () => {
    open.value = false;
    activeIndex.value = 0;
};

const focusInput = () => {
    if (props.disabled || atLimit.value) {
        return;
    }
    inputRef.value?.focus();
    openPanel();
};

const add = (value) => {
    const trimmed = String(value || '').trim().slice(0, props.maxLength);
    if (!trimmed || atLimit.value) {
        return;
    }
    if (selectedSet.value.has(trimmed.toLowerCase())) {
        return;
    }
    model.value = [...model.value, trimmed];
    query.value = '';
    activeIndex.value = 0;
    emit('change', model.value);
    nextTick(() => {
        if (atLimit.value) {
            closePanel();
        } else {
            inputRef.value?.focus();
        }
    });
};

const addCustom = () => {
    add(query.value);
};

const remove = (item) => {
    model.value = model.value.filter((value) => value !== item);
    emit('change', model.value);
    nextTick(() => inputRef.value?.focus());
};

const onKeydown = (event) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        closePanel();
        return;
    }

    if (event.key === 'Backspace' && !query.value && model.value.length) {
        remove(model.value[model.value.length - 1]);
        return;
    }

    const optionCount = filteredOptions.value.length + (showCustomOption.value ? 1 : 0);
    if (!optionCount) {
        if (event.key === 'Enter' && props.allowCustom && query.value.trim()) {
            event.preventDefault();
            addCustom();
        }
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        openPanel();
        activeIndex.value = (activeIndex.value + 1) % optionCount;
        return;
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        openPanel();
        activeIndex.value = (activeIndex.value - 1 + optionCount) % optionCount;
        return;
    }
    if (event.key === 'Enter') {
        event.preventDefault();
        if (activeIndex.value < filteredOptions.value.length) {
            add(filteredOptions.value[activeIndex.value]);
        } else if (showCustomOption.value) {
            addCustom();
        }
    }
};

const onPointerDown = (event) => {
    const target = event.target;
    if (rootRef.value?.contains(target) || panelRef.value?.contains(target)) {
        return;
    }
    closePanel();
};

watch(query, () => {
    activeIndex.value = 0;
    if (query.value.trim()) {
        openPanel();
    }
});

onMounted(() => {
    document.addEventListener('pointerdown', onPointerDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onPointerDown);
});
</script>
